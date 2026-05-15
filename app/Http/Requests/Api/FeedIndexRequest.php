<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'topic' => ['nullable', Rule::in(['general', 'politics', 'sports', 'weather', 'community', 'traffic'])],
            'category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:post_subcategories,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
