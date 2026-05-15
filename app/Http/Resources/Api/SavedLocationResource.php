<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'is_default' => $this->is_default,
            'source' => $this->source,
            'country' => new CountryResource($this->whenLoaded('country')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'county' => new CountyResource($this->whenLoaded('county')),
        ];
    }
}
