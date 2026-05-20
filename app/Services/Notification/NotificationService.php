<?php

namespace App\Services\Notification;

use App\Enums\NewsPostStatus;
use App\Enums\UserSubmissionStatus;
use App\Enums\UserStatus;
use App\Jobs\BroadcastPublishedNewsJob;
use App\Models\NewsPost;
use App\Models\User;
use App\Models\UserNotificationDevice;
use App\Models\UserSubmission;
use App\Notifications\NewsPublishedNotification;
use App\Notifications\SubmissionApprovedNotification;
use App\Notifications\SubmissionRejectedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $defaultPerPage = (int) config('community_will.notifications.default_per_page', 20);
        $maxPerPage = (int) config('community_will.notifications.max_per_page', 50);
        $perPage = max(1, min((int) ($filters['per_page'] ?? $defaultPerPage), $maxPerPage));

        return $user->notifications()
            ->when(
                (bool) ($filters['unread_only'] ?? false),
                fn (Builder $query) => $query->whereNull('read_at')
            )
            ->latest()
            ->paginate($perPage);
    }

    public function registerDevice(User $user, array $attributes): UserNotificationDevice
    {
        $token = trim((string) $attributes['token']);

        return UserNotificationDevice::query()->updateOrCreate(
            [
                'token_hash' => hash('sha256', $token),
            ],
            [
                'user_id' => $user->id,
                'token' => $token,
                'platform' => (string) $attributes['platform'],
                'device_name' => $attributes['device_name'] ?? null,
                'app_version' => $attributes['app_version'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
            ],
        );
    }

    public function deactivateDevice(User $user, string $token): void
    {
        UserNotificationDevice::query()
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', trim($token)))
            ->update([
                'is_active' => false,
            ]);
    }

    public function findForUser(User $user, string $notificationId): ?DatabaseNotification
    {
        return $user->notifications()->whereKey($notificationId)->first();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    public function sendSubmissionDecisionNotification(int|UserSubmission $submission): void
    {
        $submissionModel = $submission instanceof UserSubmission
            ? $submission->loadMissing(['user.notificationDevices', 'county', 'approvedPost'])
            : UserSubmission::query()
                ->with(['user.notificationDevices', 'county', 'approvedPost'])
                ->find($submission);

        if (! $submissionModel?->user) {
            return;
        }

        if ($submissionModel->status === UserSubmissionStatus::Approved) {
            $submissionModel->user->notify(new SubmissionApprovedNotification($submissionModel));

            return;
        }

        if ($submissionModel->status === UserSubmissionStatus::Rejected) {
            $submissionModel->user->notify(new SubmissionRejectedNotification($submissionModel));
        }
    }

    public function queuePublishedPostBroadcast(NewsPost $post): void
    {
        BroadcastPublishedNewsJob::dispatch($post->id)->afterCommit();
    }

    public function broadcastPublishedPost(int|NewsPost $post): void
    {
        $postModel = $post instanceof NewsPost
            ? $post->loadMissing('county')
            : NewsPost::query()->with('county')->find($post);

        if (! $postModel || $postModel->status !== NewsPostStatus::Published) {
            return;
        }

        $chunkSize = max(1, (int) config('community_will.notifications.broadcast_chunk_size', 100));
        $notification = new NewsPublishedNotification($postModel);

        User::query()
            ->with('notificationDevices')
            ->where('status', UserStatus::Active->value)
            ->when(
                $postModel->source_type === 'subscriber_submission' && filled($postModel->author_id),
                fn (Builder $query) => $query->whereKeyNot($postModel->author_id)
            )
            ->orderBy('id')
            ->chunkById($chunkSize, function ($users) use ($notification): void {
                foreach ($users as $user) {
                    $user->notify($notification);
                }
            });
    }
}
