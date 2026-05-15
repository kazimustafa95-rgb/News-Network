<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostArchive extends Model
{
    protected $fillable = [
        'news_post_id',
        'county_id',
        'archive_date',
        'price_cents',
        'currency',
        'access_scope',
    ];

    protected function casts(): array
    {
        return [
            'archive_date' => 'date',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }
}
