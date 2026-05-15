<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchivePurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'county_id' => $this->county_id,
            'archive_date' => optional($this->archive_date)->toDateString(),
            'provider' => $this->provider,
            'status' => $this->status?->value ?? $this->status,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'purchased_at' => optional($this->purchased_at)->toIso8601String(),
            'verified_at' => optional($this->verified_at)->toIso8601String(),
        ];
    }
}
