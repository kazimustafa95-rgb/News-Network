<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedPostCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = $this->whenLoaded('videos', fn () => $this->videos->first());
        $kind = $media ? (str_starts_with((string) $media->mime_type, 'image/') ? 'image' : 'video') : null;

        return [
            'id' => $this->id,
            'county_id' => $this->county_id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
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
            'is_featured' => $this->is_featured,
            'is_breaking' => $this->is_breaking,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
            'county' => [
                'id' => $this->county?->id,
                'name' => $this->county?->name,
            ],
            'archive' => $this->whenLoaded('archive', fn () => [
                'archive_date' => optional($this->archive?->archive_date)->toDateString(),
                'price_cents' => $this->archive?->price_cents,
                'currency' => $this->archive?->currency,
            ]),
            'media' => $media ? [
                'path' => $media->path,
                'thumbnail_path' => $media->thumbnail_path,
                'mime_type' => $media->mime_type,
                'kind' => $kind,
                'processing_status' => $media->processing_status,
            ] : null,
            'video' => $media ? [
                'thumbnail_path' => $media->thumbnail_path,
                'processing_status' => $media->processing_status,
            ] : null,
        ];
    }
}
