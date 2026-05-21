<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\NormalizesBooleanInputs;
use Illuminate\Foundation\Http\FormRequest;

class IndexNotificationsRequest extends FormRequest
{
    use NormalizesBooleanInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function booleanFields(): array
    {
        return ['unread_only'];
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'unread_only' => ['nullable', 'boolean'],
        ];
    }
}
