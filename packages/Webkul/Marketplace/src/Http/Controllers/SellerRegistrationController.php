<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Webkul\Marketplace\Http\Requests\SellerRegistrationRequest;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Repositories\SellerRepository;

class SellerRegistrationController extends Controller
{
    public function __construct(protected SellerRepository $sellerRepository) {}

    public function index(): View
    {
        return view('marketplace::seller.sign-up');
    }

    public function store(SellerRegistrationRequest $request): RedirectResponse
    {
        $this->sellerRepository->create([
            ...$request->validated(),
            'status' => Seller::STATUS_PENDING,
        ]);

        session()->flash('success', 'Your seller account has been created and is pending admin approval.');

        return redirect()->route('marketplace.seller.session.index');
    }
}
