<?php

namespace Webkul\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
