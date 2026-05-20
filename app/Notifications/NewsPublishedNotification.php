<?php

namespace App\Notifications;

use App\Models\NewsPost;
use App\Notifications\Channels\FirebasePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewsPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly NewsPost $post)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FirebasePushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->post->is_breaking
            ? 'Breaking news update'
            : 'New story available';

        $body = Str::limit(
            trim((string) ($this->post->excerpt ?: strip_tags((string) $this->post->body))),
            160,
            '...'
        );

        if ($body === '') {
            $body = sprintf(
                '"%s" is now live%s. Open the app to read the full story.',
                trim((string) $this->post->title),
                $this->post->county?->name ? ' in '.$this->post->county->name : '',
            );
        }

        return [
            'type' => 'news_published',
            'category' => 'news',
            'icon' => 'newspaper',
            'title' => $title,
            'body' => $body,
            'action' => [
                'screen' => 'post_detail',
                'post_id' => (string) $this->post->id,
                'slug' => (string) $this->post->slug,
            ],
            'payload' => [
                'post_id' => $this->post->id,
                'slug' => $this->post->slug,
                'county_id' => $this->post->county_id,
                'is_breaking' => (bool) $this->post->is_breaking,
                'source_type' => $this->post->source_type,
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
                'screen' => 'post_detail',
                'post_id' => (string) $this->post->id,
                'slug' => (string) $this->post->slug,
                'county_id' => (string) $this->post->county_id,
            ],
        ];
    }
}
