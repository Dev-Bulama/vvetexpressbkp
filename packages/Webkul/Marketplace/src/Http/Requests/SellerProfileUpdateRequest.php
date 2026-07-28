<?php

namespace Webkul\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sellerId = $this->user('seller')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'shop_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('marketplace_sellers', 'email')->ignore($sellerId)],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'current_password' => ['nullable', 'required_with:password', 'current_password:seller'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
