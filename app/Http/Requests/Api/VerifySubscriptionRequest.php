<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifySubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:50'],
            'provider_product_id' => ['required', 'string', 'max:255'],
            'provider_transaction_id' => ['required', 'string', 'max:255'],
            'plan_code' => ['required', 'string', 'max:100'],
            'receipt' => ['nullable', 'string'],
            'platform' => ['nullable', 'string', 'max:50'],
            'ends_at' => ['nullable', 'date'],
        ];
    }
}
