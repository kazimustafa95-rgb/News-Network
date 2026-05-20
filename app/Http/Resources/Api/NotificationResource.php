<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => data_get($this->data, 'type'),
            'category' => data_get($this->data, 'category'),
            'icon' => data_get($this->data, 'icon'),
            'title' => data_get($this->data, 'title'),
            'body' => data_get($this->data, 'body'),
            'action' => data_get($this->data, 'action', []),
            'payload' => data_get($this->data, 'payload', []),
            'is_read' => filled($this->read_at),
            'read_at' => optional($this->read_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'created_at_human' => optional($this->created_at)->diffForHumans(),
            'date_group' => $this->dateGroupLabel(),
        ];
    }

    protected function dateGroupLabel(): ?string
    {
        if (! $this->created_at) {
            return null;
        }

        if ($this->created_at->isToday()) {
            return 'Today';
        }

        if ($this->created_at->isYesterday()) {
            return 'Yesterday';
        }

        return $this->created_at->format('F j, Y');
    }
}
