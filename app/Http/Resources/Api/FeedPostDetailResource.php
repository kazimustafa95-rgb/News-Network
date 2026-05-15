<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedPostDetailResource extends JsonResource
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
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'topic' => $this->topic?->value ?? $this->topic,
            'category' => $this->postCategory ? [
                'id' => $this->postCategory->id,
                'name' => $this->postCategory->name,
                'slug' => $this->postCategory->slug,
            ] : null,
            'subcategory' => $this->postSubcategory ? [
                'id' => $this->postSubcategory->id,
                'name' => $this->postSubcategory->name,
                'slug' => $this->postSubcategory->slug,
            ] : null,
            'status' => $this->status?->value ?? $this->status,
            'source_type' => $this->source_type,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'archived_at' => optional($this->archived_at)->toIso8601String(),
            'archive' => $this->whenLoaded('archive', fn () => [
                'archive_date' => optional($this->archive?->archive_date)->toDateString(),
                'price_cents' => $this->archive?->price_cents,
                'currency' => $this->archive?->currency,
                'access_scope' => $this->archive?->access_scope,
            ]),
            'county' => new CountyResource($this->whenLoaded('county')),
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
            'media' => $media,
            'videos' => $media,
        ];
    }
}
