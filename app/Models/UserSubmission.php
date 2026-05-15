<?php

namespace App\Models;

use App\Enums\UserSubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'county_id',
        'title',
        'location_label',
        'description',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'approved_post_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserSubmissionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedPost(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class, 'approved_post_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(PostVideo::class);
    }

    public function media(): HasMany
    {
        return $this->videos();
    }
}
