<?php

namespace App\Notifications\Channels;

use App\Services\Notification\FirebaseMessagingService;
use Illuminate\Notifications\Notification;

class FirebasePushChannel
{
    public function __construct(private readonly FirebaseMessagingService $messaging)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFirebase') || ! method_exists($notifiable, 'notificationDevices')) {
            return;
        }

        $payload = $notification->toFirebase($notifiable);

        if (! is_array($payload)) {
            return;
        }

        $devices = $notifiable->relationLoaded('notificationDevices')
            ? $notifiable->notificationDevices
            : $notifiable->notificationDevices()->active()->get();

        $this->messaging->sendToDevices($devices, $payload);
    }
}
