<?php

namespace App\Notifications;

use App\Enums\NewsPostStatus;
use App\Models\UserSubmission;
use App\Notifications\Channels\FirebasePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly UserSubmission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FirebasePushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $isPublished = $this->submission->approvedPost?->status === NewsPostStatus::Published;
        $title = $isPublished ? 'Your story is now live' : 'Your submission was approved';
        $body = $isPublished
            ? sprintf(
                '"%s" has been approved and published%s. Open the app to view it.',
                $this->submissionTitle(),
                $this->countySuffix(),
            )
            : sprintf(
                '"%s" has been approved by our editorial team%s and is being prepared for publication.',
                $this->submissionTitle(),
                $this->countySuffix(),
            );

        return [
            'type' => 'submission_approved',
            'category' => 'submission',
            'icon' => 'check-circle',
            'title' => $title,
            'body' => $body,
            'action' => [
                'screen' => 'submission_history',
                'submission_id' => (string) $this->submission->id,
                'post_id' => (string) ($this->submission->approved_post_id ?? ''),
            ],
            'payload' => [
                'submission_id' => $this->submission->id,
                'approved_post_id' => $this->submission->approved_post_id,
                'is_published' => $isPublished,
                'review_notes' => $this->submission->review_notes,
            ],
        ];
    }

    public function toFirebase(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => [
                'type' => $data['type'],
                'screen' => 'submission_history',
                'submission_id' => (string) $this->submission->id,
                'post_id' => (string) ($this->submission->approved_post_id ?? ''),
            ],
        ];
    }

    protected function submissionTitle(): string
    {
        return trim((string) ($this->submission->title ?: 'Your submission'));
    }

    protected function countySuffix(): string
    {
        return $this->submission->county?->name
            ? ' in '.$this->submission->county->name
            : '';
    }
}
