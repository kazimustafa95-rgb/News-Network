<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostVideo extends Model
{
    protected $fillable = [
        'news_post_id',
        'user_submission_id',
        'disk',
        'path',
        'thumbnail_path',
        'mime_type',
        'file_size',
        'duration_seconds',
        'width',
        'height',
        'is_primary',
        'processing_status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(UserSubmission::class, 'user_submission_id');
    }

    public function resolveMediaUrl(?string $path = null, int $expiresInMinutes = 20): ?string
    {
        $path ??= $this->path;

        if (blank($path)) {
            return null;
        }

        $disk = $this->disk ?: config('community_will.media.post_disk');
        $filesystem = Storage::disk($disk);

        try {
            return $filesystem->temporaryUrl($path, now()->addMinutes($expiresInMinutes));
        } catch (\Throwable) {
            try {
                return $filesystem->url($path);
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
