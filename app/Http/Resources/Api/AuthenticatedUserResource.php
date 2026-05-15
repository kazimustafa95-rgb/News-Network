<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status?->value ?? $this->status,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('slug')->values()),
            'profile' => $this->whenLoaded('profile', fn () => [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'phone' => $this->profile?->phone,
                'avatar_path' => $this->profile?->avatar_path,
            ]),
            'locations' => SavedLocationResource::collection($this->whenLoaded('locations')),
            'subscription' => new SubscriptionStatusResource($this->whenLoaded('subscriptions', fn () => $this->activeSubscription())),
            'archive_purchase_summary' => $this->whenLoaded('archivePurchases', fn () => [
                'count' => $this->archivePurchases->count(),
                'latest_purchase_at' => optional($this->archivePurchases->sortByDesc('purchased_at')->first()?->purchased_at)->toIso8601String(),
                'latest_archive_date' => optional($this->archivePurchases->sortByDesc('purchased_at')->first()?->archive_date)->toDateString(),
            ]),
        ];
    }
}
