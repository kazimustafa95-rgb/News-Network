<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'county_id' => ['required', 'integer', 'exists:counties,id'],
            'title' => ['required', 'string', 'max:255'],
            'location_label' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'media' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,image/jpeg,image/png,image/webp', 'max:102400'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska', 'max:102400'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasFile('media') && ! $this->hasFile('video')) {
                    $validator->errors()->add('media', 'A video or image file is required.');
                }
            },
        ];
    }
}
