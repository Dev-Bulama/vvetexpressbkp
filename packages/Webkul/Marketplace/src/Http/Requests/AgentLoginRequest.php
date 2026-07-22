<?php

namespace Webkul\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
