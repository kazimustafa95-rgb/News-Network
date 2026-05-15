<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'county_id' => ['required', 'integer', 'exists:counties,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
