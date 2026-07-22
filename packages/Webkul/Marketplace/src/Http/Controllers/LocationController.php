<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Marketplace\Http\Requests\CustomerLocationRequest;

/**
 * The public "deliver to" location selector shown in the header. There is no
 * Google Maps key configured in this environment, so this deliberately does
 * not attempt reverse-geocoding - browser geolocation gives real
 * coordinates, and the customer confirms/types the human-readable address
 * themselves rather than us guessing or fabricating one. Once
 * GOOGLE_MAPS_API_KEY is set, reverse geocoding can be added as a
 * progressive enhancement in front of this same endpoint without changing
 * its contract.
 */
class LocationController extends Controller
{
    public function __construct(protected CustomerAddressRepository $customerAddressRepository) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'location' => session('marketplace.customer_location'),
        ]);
    }

    public function store(CustomerLocationRequest $request): JsonResponse
    {
        $location = [
            'label' => $request->input('label'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'landmark' => $request->input('landmark'),
            'delivery_instructions' => $request->input('delivery_instructions'),
            'lat' => $request->input('latitude'),
            'lng' => $request->input('longitude'),
        ];

        session(['marketplace.customer_location' => $location]);

        $savedAddress = null;

        if ($request->boolean('save_address') && $customer = auth()->guard('customer')->user()) {
            $savedAddress = $this->customerAddressRepository->create([
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'address' => $request->input('address'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'country' => 'NG',
                'postcode' => '',
                'phone' => $customer->phone ?? '',
                'email' => $customer->email,
                'landmark' => $request->input('landmark'),
                'delivery_instructions' => $request->input('delivery_instructions'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'default_address' => 0,
            ]);
        }

        return response()->json([
            'message' => 'Delivery location updated.',
            'location' => $location,
            'saved_address_id' => $savedAddress?->id,
        ]);
    }
}
