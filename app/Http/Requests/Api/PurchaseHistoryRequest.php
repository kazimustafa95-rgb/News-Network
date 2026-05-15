<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'archive_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
