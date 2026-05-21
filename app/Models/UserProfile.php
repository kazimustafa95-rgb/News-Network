<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'avatar_path',
        'default_country_id',
        'default_region_id',
        'default_county_id',
        'onboarding_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk(config('community_will.media.profile_disk'))->url($this->avatar_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'default_country_id');
    }

    public function defaultRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'default_region_id');
    }

    public function defaultCounty(): BelongsTo
    {
        return $this->belongsTo(County::class, 'default_county_id');
    }
}
