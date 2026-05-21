<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\NormalizesBooleanInputs;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserLocationRequest extends FormRequest
{
    use NormalizesBooleanInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function booleanFields(): array
    {
        return ['is_default'];
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
