<?php

namespace Webkul\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'shop_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:marketplace_sellers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            // A vendor with no pickup coordinates can never actually be
            // assigned a delivery quote - the sign-up form already offers
            // both auto-detect and a manual map pin, so this is always
            // satisfiable without depending solely on browser geolocation.
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],

            /**
             * Optional live-recorded shop walkthrough (see sign-up.blade.php
             * - captured with getUserMedia/MediaRecorder, never a file
             * picker). Restricted to the mime types browsers actually
             * produce from MediaRecorder so this can't be used as a
             * general-purpose file upload endpoint.
             */
            'verification_video' => ['nullable', 'file', 'mimetypes:video/webm,video/mp4,video/quicktime', 'max:25000'],
        ];
    }
}
