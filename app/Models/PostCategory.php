<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(PostSubcategory::class)->orderBy('sort_order')->orderBy('name');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class)->orderByDesc('published_at');
    }
}
