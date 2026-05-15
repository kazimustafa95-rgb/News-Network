<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ArchivePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'county_id' => ['required', 'integer', 'exists:counties,id'],
            'archive_date' => ['required', 'date'],
            'provider' => ['required', 'string', 'max:50'],
            'provider_transaction_id' => ['required', 'string', 'max:255'],
            'purchase_token' => ['nullable', 'string'],
            'amount_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
