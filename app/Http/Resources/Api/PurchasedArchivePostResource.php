<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PurchasedArchivePostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'purchase_date' => filled($this->entitlement_purchased_at)
                ? Carbon::parse($this->entitlement_purchased_at)->toIso8601String()
                : null,
            'archive_date' => optional($this->archive?->archive_date)->toDateString(),
            'post' => (new FeedPostCardResource($this->resource))->resolve(),
        ];
    }
}
