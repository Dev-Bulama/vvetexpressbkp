<?php

namespace Webkul\Marketplace\ERPNext;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around ERPNext/Frappe's REST API (https://docs.erpnext.com).
 * Credentials come from env only (ERPNEXT_BASE_URL, ERPNEXT_API_KEY,
 * ERPNEXT_API_SECRET) - never hardcoded, so this integration simply stays
 * dormant (isConfigured() === false) on any environment where they aren't
 * set, rather than failing loudly.
 */
class ERPNextClient
{
    protected ?string $baseUrl;

    protected ?string $apiKey;

    protected ?string $apiSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.erpnext.base_url'), '/') ?: null;
        $this->apiKey = config('services.erpnext.api_key') ?: null;
        $this->apiSecret = config('services.erpnext.api_secret') ?: null;
    }

    public function isConfigured(): bool
    {
        return (bool) ($this->baseUrl && $this->apiKey && $this->apiSecret);
    }

    /**
     * One page of enabled, sellable Items from ERPNext's Item doctype.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchItems(int $limitStart = 0, int $limitPageLength = 50): array
    {
        $response = $this->client()->get('/api/resource/Item', [
            'fields' => json_encode([
                'name', 'item_code', 'item_name', 'description',
                'standard_rate', 'image', 'item_group', 'disabled', 'weight_per_unit',
            ]),
            'filters' => json_encode([['disabled', '=', 0]]),
            'limit_start' => $limitStart,
            'limit_page_length' => $limitPageLength,
        ]);

        $response->throw();

        return $response->json('data') ?? [];
    }

    /**
     * Total on-hand stock per item code, summed across every ERPNext
     * warehouse (the Bin doctype tracks stock per-warehouse, not per-item).
     *
     * @return array<string, float>
     */
    public function fetchStockLevels(): array
    {
        $response = $this->client()->get('/api/resource/Bin', [
            'fields' => json_encode(['item_code', 'actual_qty']),
            'limit_page_length' => 0,
        ]);

        $response->throw();

        $levels = [];

        foreach ($response->json('data') ?? [] as $bin) {
            $itemCode = $bin['item_code'] ?? null;

            if (! $itemCode) {
                continue;
            }

            $levels[$itemCode] = ($levels[$itemCode] ?? 0) + (float) ($bin['actual_qty'] ?? 0);
        }

        return $levels;
    }

    /**
     * Downloads a product image ERPNext returned as a site-relative path
     * (e.g. "/files/dog-food.jpg"). Returns null rather than throwing so a
     * single missing/unreachable image never aborts the whole sync.
     */
    public function downloadImage(string $path): ?string
    {
        try {
            $url = str_starts_with($path, 'http') ? $path : $this->baseUrl.$path;

            $response = $this->client()->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['Authorization' => "token {$this->apiKey}:{$this->apiSecret}"])
            ->timeout(30)
            ->retry(2, 500);
    }
}
