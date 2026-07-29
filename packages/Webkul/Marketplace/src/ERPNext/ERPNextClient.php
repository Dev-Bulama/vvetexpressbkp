<?php

namespace Webkul\Marketplace\ERPNext;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * One page of ERPNext's Item Group doctype - the category equivalent
     * products are grouped under via their own `item_group` field. `name`
     * is Frappe's real primary key for the doctype (stable even if
     * `item_group_name` is later renamed), so it's the only safe
     * synchronization identifier - never match on the display name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchItemGroups(int $limitStart = 0, int $limitPageLength = 100): array
    {
        $response = $this->client()->get('/api/resource/Item Group', [
            'fields' => json_encode([
                'name', 'item_group_name', 'parent_item_group', 'is_group',
            ]),
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
     * (e.g. "/files/dog-food.jpg" - but ERPNext's Image field is a free-text
     * value, and not every item's was necessarily uploaded the same way, so
     * a path missing its leading slash is joined safely here rather than
     * naively concatenated onto the base URL, which would silently produce
     * a malformed URL (e.g. "https://erp.example.comfiles/x.jpg") that
     * fails to download - looking identical to "ERPNext has no image" even
     * though ERPNext genuinely has one). Returns null rather than throwing
     * so a single missing/unreachable image never aborts the whole sync.
     */
    public function downloadImage(string $path): ?string
    {
        try {
            $url = str_starts_with($path, 'http')
                ? $path
                : rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

            $response = $this->client()->get($url);

            if (! $response->successful()) {
                Log::warning('ERPNext image download failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning('ERPNext image download threw an exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

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
