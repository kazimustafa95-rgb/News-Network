<?php

namespace App\Notifications;

use App\Models\UserSubmission;
use App\Notifications\Channels\FirebasePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SubmissionRejectedNotification extends Notification
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
        $reason = trim((string) $this->submission->review_notes);
        $title = 'Update on your submission';
        $body = sprintf(
            'We reviewed "%s"%s and need a few changes before publication.%s',
            $this->submissionTitle(),
            $this->countySuffix(),
            $reason !== '' ? ' Reason: '.Str::limit($reason, 140) : '',
        );

        return [
            'type' => 'submission_rejected',
            'category' => 'submission',
            'icon' => 'x-circle',
            'title' => $title,
            'body' => $body,
            'action' => [
                'screen' => 'submission_history',
                'submission_id' => (string) $this->submission->id,
            ],
            'payload' => [
                'submission_id' => $this->submission->id,
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
            ],
        ];
    }

    protected function submissionTitle(): string
    {
        return trim((string) ($this->submission->title ?: 'your submission'));
    }

    protected function countySuffix(): string
    {
        return $this->submission->county?->name
            ? ' for '.$this->submission->county->name
            : '';
    }
}
