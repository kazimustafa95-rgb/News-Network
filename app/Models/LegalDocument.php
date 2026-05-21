<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getPublicUrl(): ?string
    {
        return match ($this->slug) {
            'terms-and-conditions' => route('legal.terms'),
            'privacy-policy' => route('legal.privacy'),
            default => null,
        };
    }
}
