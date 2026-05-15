<?php

namespace App\Services\Submission;

use App\Enums\UserSubmissionStatus;
use App\Models\User;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    public function __construct(private readonly SubmissionRepositoryInterface $submissions)
    {
    }

    public function store(User $user, array $attributes)
    {
        return DB::transaction(function () use ($user, $attributes) {
            $submission = $this->submissions->create([
                'user_id' => $user->id,
                'county_id' => $attributes['county_id'],
                'title' => $attributes['title'],
                'location_label' => $attributes['location_label'],
                'description' => $attributes['description'],
                'status' => UserSubmissionStatus::Pending->value,
            ]);

            $uploadedMedia = $attributes['media'] ?? $attributes['video'] ?? null;

            if ($uploadedMedia instanceof UploadedFile) {
                $path = $uploadedMedia->store('posts/originals', config('community_will.media.submission_disk'));
                $mimeType = $uploadedMedia->getClientMimeType() ?: $uploadedMedia->getMimeType();
                $isImage = str_starts_with((string) $mimeType, 'image/');

                $submission->videos()->create([
                    'disk' => config('community_will.media.submission_disk'),
                    'path' => $path,
                    'thumbnail_path' => $isImage ? $path : null,
                    'mime_type' => $mimeType,
                    'file_size' => $uploadedMedia->getSize(),
                    'is_primary' => true,
                    'processing_status' => $isImage ? 'ready' : 'pending',
                    'processed_at' => $isImage ? now() : null,
                ]);
            }

            return $submission->load(['county', 'videos']);
        });
    }

    public function paginateForUser(User $user, array $filters = [])
    {
        return $this->submissions->paginateForUser($user, $filters);
    }
}
