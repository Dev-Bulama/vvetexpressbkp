<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
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
        $data = $request->validated();

        unset($data['verification_video']);

        if ($video = $request->file('verification_video')) {
            $data['verification_video_path'] = $video->store('sellers/verification', 'public');
            $data['verification_video_recorded_at'] = now();
        }

        $this->sellerRepository->create([
            ...$data,
            'status' => Seller::STATUS_PENDING,
        ]);

        session()->flash('success', 'Your seller account has been created and is pending admin approval.');

        return redirect()->route('marketplace.seller.session.index');
    }
}
