<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = $this->whenLoaded('videos', fn () => $this->videos->map(fn ($video) => [
            'id' => $video->id,
            'path' => $video->path,
            'thumbnail_path' => $video->thumbnail_path,
            'mime_type' => $video->mime_type,
            'kind' => str_starts_with((string) $video->mime_type, 'image/') ? 'image' : 'video',
            'processing_status' => $video->processing_status,
        ]));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'location_label' => $this->location_label,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'review_notes' => $this->review_notes,
            'reviewed_at' => optional($this->reviewed_at)->toIso8601String(),
            'county' => new CountyResource($this->whenLoaded('county')),
            'media' => $media,
            'videos' => $media,
            'approved_post_id' => $this->approved_post_id,
        ];
    }
}
