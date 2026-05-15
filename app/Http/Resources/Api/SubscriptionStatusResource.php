<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionStatusResource extends JsonResource
{
    public function toArray(Request $request): ?array
    {
        if ($this->resource === null) {
            return null;
        }

        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'provider_product_id' => $this->provider_product_id,
            'plan_code' => $this->plan_code,
            'status' => $this->status?->value ?? $this->status,
            'started_at' => optional($this->started_at)->toIso8601String(),
            'ends_at' => optional($this->ends_at)->toIso8601String(),
            'auto_renew' => $this->auto_renew,
        ];
    }
}
