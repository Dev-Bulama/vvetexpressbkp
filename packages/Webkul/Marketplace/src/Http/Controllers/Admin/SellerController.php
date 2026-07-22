<?php

namespace Webkul\Marketplace\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Controllers\Controller;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Repositories\SellerRepository;

class SellerController extends Controller
{
    public function __construct(protected SellerRepository $sellerRepository) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = $this->sellerRepository->getModel()->newQuery()->latest();

        if (in_array($status, [Seller::STATUS_PENDING, Seller::STATUS_APPROVED, Seller::STATUS_SUSPENDED], true)) {
            $query->where('status', $status);
        }

        $sellers = $query->paginate(20)->withQueryString();

        return view('marketplace::admin.sellers.index', compact('sellers', 'status'));
    }

    public function edit(int $id): View
    {
        $seller = $this->sellerRepository->findOrFail($id);

        return view('marketplace::admin.sellers.edit', compact('seller'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,approved,suspended',
        ]);

        $seller = $this->sellerRepository->findOrFail($id);

        $this->sellerRepository->update(['status' => $request->input('status')], $seller->id);

        session()->flash('success', 'Seller status updated successfully.');

        return redirect()->route('marketplace.admin.sellers.edit', $seller->id);
    }
}
