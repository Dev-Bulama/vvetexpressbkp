<?php

namespace Webkul\Marketplace\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Controllers\Controller;
use Webkul\Marketplace\Http\Requests\AdminSellerCreateRequest;
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

    public function create(): View
    {
        return view('marketplace::admin.sellers.create');
    }

    /**
     * An admin-created vendor is approved immediately - unlike the public
     * sign-up flow, an admin creating the account directly is already the
     * vetting step, so there's no reason to also make it wait in the
     * pending queue it would otherwise land in.
     */
    public function store(AdminSellerCreateRequest $request): RedirectResponse
    {
        $seller = $this->sellerRepository->create([
            ...$request->validated(),
            'status' => Seller::STATUS_APPROVED,
        ]);

        session()->flash('success', 'Vendor account created and approved.');

        return redirect()->route('marketplace.admin.sellers.edit', $seller->id);
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
