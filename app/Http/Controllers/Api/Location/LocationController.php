<?php

namespace App\Http\Controllers\Api\Location;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AutoDetectLocationRequest;
use App\Http\Requests\Api\StoreUserLocationRequest;
use App\Http\Resources\Api\CountyResource;
use App\Http\Resources\Api\CountryResource;
use App\Http\Resources\Api\RegionResource;
use App\Http\Resources\Api\SavedLocationResource;
use App\Models\Country;
use App\Models\Region;
use App\Services\Location\LocationService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LocationService $locations)
    {
    }

    public function countries(): JsonResponse
    {
        return $this->successResponse(
            CountryResource::collection($this->locations->countries())->resolve(),
            'Countries fetched successfully.'
        );
    }

    public function regions(Country $country): JsonResponse
    {
        return $this->successResponse(
            RegionResource::collection($this->locations->regions($country))->resolve(),
            'Regions fetched successfully.'
        );
    }

    public function counties(Region $region): JsonResponse
    {
        return $this->successResponse(
            CountyResource::collection($this->locations->counties($region))->resolve(),
            'Counties fetched successfully.'
        );
    }

    public function autoDetect(AutoDetectLocationRequest $request): JsonResponse
    {
        $payload = $this->locations->autoDetect($request->validated());

        return $this->successResponse([
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
            'label' => $payload['label'],
            'formatted_address' => $payload['formatted_address'],
            'place_id' => $payload['place_id'],
            'country_id' => $payload['country']?->id,
            'region_id' => $payload['region']?->id,
            'county_id' => $payload['county']?->id,
            'country_label' => $payload['country_label'],
            'region_label' => $payload['region_label'],
            'county_label' => $payload['county_label'],
            'matched' => $payload['matched'],
            'can_save' => $payload['can_save'],
            'country' => $payload['country'] ? (new CountryResource($payload['country']))->resolve() : null,
            'region' => $payload['region'] ? (new RegionResource($payload['region']))->resolve() : null,
            'county' => $payload['county'] ? (new CountyResource($payload['county']))->resolve() : null,
        ], 'Location detected successfully.');
    }

    public function storeUserLocation(StoreUserLocationRequest $request): JsonResponse
    {
        $location = $this->locations->saveUserLocation($request->user(), $request->validated());
        $location->load(['country', 'region', 'county']);

        return $this->resourceResponse(new SavedLocationResource($location), 'Location saved successfully.', 201);
    }
}
