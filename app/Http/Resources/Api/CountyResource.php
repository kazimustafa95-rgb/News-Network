<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'region_id' => $this->region_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status?->value ?? $this->status,
            'launch_date' => optional($this->launch_date)->toDateString(),
            'timezone' => $this->timezone,
            'is_featured' => $this->is_featured,
        ];
    }
}
