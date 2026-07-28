<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Requests\SellerProfileUpdateRequest;

class SellerProfileController extends Controller
{
    public function edit(): View
    {
        return view('marketplace::seller.profile', [
            'seller' => auth()->guard('seller')->user(),
        ]);
    }

    public function update(SellerProfileUpdateRequest $request): RedirectResponse
    {
        $seller = auth()->guard('seller')->user();

        $data = $request->safe()->only(['name', 'shop_name', 'email', 'phone', 'address', 'city']);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($logo = $request->file('logo')) {
            $data['logo_path'] = $logo->store('sellers/logos', 'public');
        }

        $seller->update($data);

        session()->flash('success', 'Profile updated successfully.');

        return redirect()->route('marketplace.seller.profile.edit');
    }
}
