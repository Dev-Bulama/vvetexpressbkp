<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Requests\SellerLoginRequest;
use Webkul\Marketplace\Models\Seller;

class SellerSessionController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->guard('seller')->check()) {
            return redirect()->route('marketplace.seller.dashboard.index');
        }

        return view('marketplace::seller.sign-in');
    }

    public function store(SellerLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! auth()->guard('seller')->attempt($credentials)) {
            session()->flash('error', 'These credentials do not match our records.');

            return redirect()->back()->withInput($request->only('email'));
        }

        $seller = auth()->guard('seller')->user();

        if ($seller->status !== Seller::STATUS_APPROVED) {
            auth()->guard('seller')->logout();

            session()->flash('warning', match ($seller->status) {
                Seller::STATUS_PENDING => 'Your seller account is pending admin approval.',
                Seller::STATUS_SUSPENDED => 'Your seller account has been suspended.',
                default => 'Your seller account cannot log in right now.',
            });

            return redirect()->back();
        }

        return redirect()->route('marketplace.seller.dashboard.index');
    }

    public function destroy(): RedirectResponse
    {
        auth()->guard('seller')->logout();

        return redirect()->route('marketplace.seller.session.index');
    }
}
