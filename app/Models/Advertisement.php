<?php

namespace App\Models;

use App\Enums\AdvertisementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'media_type',
        'disk',
        'path',
        'thumbnail_path',
        'destination_url',
        'status',
        'starts_at',
        'ends_at',
        'priority',
        'slot_interval',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdvertisementStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function counties(): BelongsToMany
    {
        return $this->belongsToMany(County::class, 'advertisement_counties')->withTimestamps();
    }
}
