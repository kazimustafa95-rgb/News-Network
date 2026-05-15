<?php

namespace App\Services\Submission;

use App\Enums\NewsPostStatus;
use App\Enums\UserSubmissionStatus;
use App\Models\AdminAction;
use App\Models\NewsPost;
use App\Models\PostVideo;
use App\Models\User;
use App\Models\UserSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmissionReviewService
{
    public function approve(UserSubmission $submission, User $admin, array $attributes): UserSubmission
    {
        return DB::transaction(function () use ($submission, $admin, $attributes): UserSubmission {
            $beforeState = $submission->toArray();
            $approvedStatus = ($attributes['publish_now'] ?? true)
                ? NewsPostStatus::Published
                : NewsPostStatus::Draft;
            $publishedAt = $approvedStatus === NewsPostStatus::Published ? now() : null;
            $title = trim((string) ($attributes['title'] ?? $submission->title ?? ''));
            $slug = NewsPost::ensureUniqueSlug(
                trim((string) ($attributes['slug'] ?? '')) !== ''
                    ? (string) $attributes['slug']
                    : Str::slug($title).'-'.$submission->id,
            );

            $post = NewsPost::query()->create([
                'county_id' => $submission->county_id,
                'author_id' => $submission->user_id,
                'user_submission_id' => $submission->id,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $attributes['excerpt'] ?? $submission->description,
                'body' => $attributes['body'] ?? $submission->description,
                'post_category_id' => $attributes['post_category_id'] ?? null,
                'post_subcategory_id' => $attributes['post_subcategory_id'] ?? null,
                'topic' => NewsPost::resolveLegacyTopicFromTaxonomyIds(
                    isset($attributes['post_category_id']) ? (int) $attributes['post_category_id'] : null,
                    isset($attributes['post_subcategory_id']) ? (int) $attributes['post_subcategory_id'] : null,
                ),
                'source_type' => 'subscriber_submission',
                'status' => $approvedStatus->value,
                'is_featured' => (bool) ($attributes['is_featured'] ?? false),
                'is_breaking' => (bool) ($attributes['is_breaking'] ?? false),
                'published_at' => $publishedAt,
                'archive_at' => $publishedAt?->copy()->addDays(config('community_will.archive.days_visible', 7)),
                'meta' => [
                    'submission_location_label' => $submission->location_label,
                ],
            ]);

            $this->promoteSubmissionMediaToPostDisk($submission, $post);

            $submission->fill([
                'status' => UserSubmissionStatus::Approved->value,
                'review_notes' => $attributes['review_notes'] ?? null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'approved_post_id' => $post->id,
            ])->save();

            AdminAction::query()->create([
                'admin_id' => $admin->id,
                'action' => 'submission.approved',
                'target_type' => UserSubmission::class,
                'target_id' => $submission->id,
                'before_state' => $beforeState,
                'after_state' => $submission->fresh()->toArray(),
                'notes' => $attributes['review_notes'] ?? null,
            ]);

            return $submission->fresh(['user.profile', 'county', 'videos', 'reviewer', 'approvedPost']);
        });
    }

    protected function promoteSubmissionMediaToPostDisk(UserSubmission $submission, NewsPost $post): void
    {
        $targetDisk = config('community_will.media.post_disk');

        $submission->videos()
            ->whereNull('news_post_id')
            ->get()
            ->each(function (PostVideo $media) use ($post, $targetDisk): void {
                $currentDisk = $media->disk ?: config('community_will.media.submission_disk');
                $newPath = $this->transferMediaFile($currentDisk, $media->path, $targetDisk, 'posts/originals');
                $mediaMovedToTargetDisk = $currentDisk === $targetDisk
                    || ($media->path && $newPath && $newPath !== $media->path);
                $newThumbnailPath = $this->transferThumbnailFile(
                    $media,
                    $currentDisk,
                    $targetDisk,
                    $newPath,
                    $mediaMovedToTargetDisk,
                );

                $media->update([
                    'news_post_id' => $post->id,
                    'disk' => $mediaMovedToTargetDisk ? $targetDisk : $currentDisk,
                    'path' => $newPath,
                    'thumbnail_path' => $newThumbnailPath,
                ]);
            });
    }

    protected function transferThumbnailFile(
        PostVideo $media,
        string $currentDisk,
        string $targetDisk,
        ?string $newPath,
        bool $mediaMovedToTargetDisk,
    ): ?string
    {
        if (! $media->thumbnail_path) {
            return null;
        }

        if ($media->thumbnail_path === $media->path) {
            return $newPath;
        }

        $newThumbnailPath = $this->transferMediaFile($currentDisk, $media->thumbnail_path, $targetDisk, 'posts/thumbnails');

        if (! $mediaMovedToTargetDisk) {
            return $newThumbnailPath;
        }

        return $newThumbnailPath !== $media->thumbnail_path
            ? $newThumbnailPath
            : null;
    }

    protected function transferMediaFile(string $sourceDisk, ?string $sourcePath, string $targetDisk, string $targetDirectory): ?string
    {
        if (blank($sourcePath)) {
            return null;
        }

        $normalizedTargetPrefix = trim($targetDirectory, '/').'/';

        if ($sourceDisk === $targetDisk && str_starts_with($sourcePath, $normalizedTargetPrefix)) {
            return $sourcePath;
        }

        if (! Storage::disk($sourceDisk)->exists($sourcePath)) {
            return $sourcePath;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetPath = trim($targetDirectory.'/'.Str::ulid().($extension !== '' ? '.'.$extension : ''), '/');

        if ($sourceDisk === $targetDisk) {
            Storage::disk($sourceDisk)->move($sourcePath, $targetPath);

            return $targetPath;
        }

        $stream = Storage::disk($sourceDisk)->readStream($sourcePath);

        if ($stream === false) {
            return $sourcePath;
        }

        try {
            Storage::disk($targetDisk)->writeStream($targetPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        Storage::disk($sourceDisk)->delete($sourcePath);

        return $targetPath;
    }

    public function reject(UserSubmission $submission, User $admin, string $reason): UserSubmission
    {
        return DB::transaction(function () use ($submission, $admin, $reason): UserSubmission {
            $beforeState = $submission->toArray();

            $submission->fill([
                'status' => UserSubmissionStatus::Rejected->value,
                'review_notes' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            AdminAction::query()->create([
                'admin_id' => $admin->id,
                'action' => 'submission.rejected',
                'target_type' => UserSubmission::class,
                'target_id' => $submission->id,
                'before_state' => $beforeState,
                'after_state' => $submission->fresh()->toArray(),
                'notes' => $reason,
            ]);

            return $submission->fresh(['user.profile', 'county', 'videos', 'reviewer', 'approvedPost']);
        });
    }
}
