<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Marketplace\Models\PosSale;
use Webkul\Marketplace\Models\PosSaleItem;
use Webkul\Marketplace\Repositories\SellerProductRepository;

class SellerPosController extends Controller
{
    public function __construct(protected SellerProductRepository $sellerProductRepository) {}

    public function index(Request $request): View
    {
        $seller = auth()->guard('seller')->user();

        $query = $seller->products()->with('product')->where('is_active', true);

        if ($search = $request->query('q')) {
            $query->whereHas('product', function ($productQuery) use ($search) {
                $productQuery->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('attribute_values', function ($valueQuery) use ($search) {
                        $valueQuery->where('text_value', 'like', "%{$search}%");
                    });
            });
        }

        $offers = $query->latest()->get();

        $recentSales = PosSale::where('seller_id', $seller->id)->latest()->take(5)->get();

        $todaysSales = PosSale::where('seller_id', $seller->id)->whereDate('created_at', now())->sum('total');

        return view('marketplace::seller.pos', [
            'offers' => $offers,
            'search' => $search ?? '',
            'recentSales' => $recentSales,
            'todaysSales' => $todaysSales,
        ]);
    }

    public function charge(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.offer_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,card,transfer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $seller = auth()->guard('seller')->user();

        $sale = DB::transaction(function () use ($request, $seller) {
            $total = 0;
            $lines = [];

            foreach ($request->input('items') as $item) {
                $offer = $this->sellerProductRepository->findOrFail($item['offer_id']);

                if ($offer->seller_id !== $seller->id) {
                    abort(403);
                }

                if (! $offer->is_active || $offer->quantity < $item['quantity']) {
                    abort(422, "Not enough stock for {$offer->product?->name}.");
                }

                $lineTotal = $offer->price * $item['quantity'];
                $total += $lineTotal;

                $lines[] = [
                    'offer' => $offer,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $sale = PosSale::create([
                'seller_id' => $seller->id,
                'reference' => 'POS-'.strtoupper(Str::random(8)),
                'total' => $total,
                'payment_method' => $request->input('payment_method'),
                'customer_name' => $request->input('customer_name'),
            ]);

            foreach ($lines as $line) {
                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'seller_product_id' => $line['offer']->id,
                    'product_name' => $line['offer']->product?->name ?? $line['offer']->product?->sku,
                    'price' => $line['offer']->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $this->sellerProductRepository->update([
                    'quantity' => $line['offer']->quantity - $line['quantity'],
                ], $line['offer']->id);
            }

            return $sale;
        });

        return response()->json([
            'reference' => $sale->reference,
            'total' => core()->formatPrice($sale->total),
        ]);
    }
}
