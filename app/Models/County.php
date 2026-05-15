<?php

namespace App\Models;

use App\Enums\CountyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class County extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'region_id',
        'name',
        'slug',
        'status',
        'launch_date',
        'timezone',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => CountyStatus::class,
            'launch_date' => 'date',
            'is_featured' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class);
    }

    public function advertisements(): BelongsToMany
    {
        return $this->belongsToMany(Advertisement::class, 'advertisement_counties')->withTimestamps();
    }

    public function userLocations(): HasMany
    {
        return $this->hasMany(UserLocation::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(UserSubmission::class);
    }

    public function archives(): HasMany
    {
        return $this->hasMany(PostArchive::class);
    }
}
