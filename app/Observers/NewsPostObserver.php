<?php

namespace App\Observers;

use App\Enums\NewsPostStatus;
use App\Models\NewsPost;
use App\Services\Notification\NotificationService;

class NewsPostObserver
{
    public function created(NewsPost $post): void
    {
        if ($this->shouldSkipObserver()) {
            return;
        }

        if ($post->status === NewsPostStatus::Published) {
            app(NotificationService::class)->queuePublishedPostBroadcast($post);
        }
    }

    public function updated(NewsPost $post): void
    {
        if ($this->shouldSkipObserver()) {
            return;
        }

        if ($post->status === NewsPostStatus::Published && $post->wasChanged('status')) {
            app(NotificationService::class)->queuePublishedPostBroadcast($post);
        }
    }

    protected function shouldSkipObserver(): bool
    {
        return app()->runningInConsole() && ! app()->runningUnitTests();
    }
}
