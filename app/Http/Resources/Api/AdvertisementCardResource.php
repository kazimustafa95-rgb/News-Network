<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'media_type' => $this->media_type,
            'path' => $this->path,
            'thumbnail_path' => $this->thumbnail_path,
            'destination_url' => $this->destination_url,
            'slot_interval' => $this->slot_interval,
            'priority' => $this->priority,
        ];
    }
}
