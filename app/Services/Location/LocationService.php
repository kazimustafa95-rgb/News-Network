<?php

namespace App\Services\Location;

use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LocationService
{
    public function __construct(
        private readonly LocationRepositoryInterface $locations,
        private readonly GoogleMapsReverseGeocodingService $googleMaps,
    ) {
    }

    public function countries(): Collection
    {
        return $this->locations->getActiveCountries();
    }

    public function regions(Country $country): Collection
    {
        return $this->locations->getRegionsForCountry($country);
    }

    public function counties(Region $region): Collection
    {
        return $this->locations->getCountiesForRegion($region);
    }

    public function autoDetect(array $coordinates): array
    {
        $latitude = (float) $coordinates['latitude'];
        $longitude = (float) $coordinates['longitude'];
        $detected = $this->googleMaps->detect($latitude, $longitude);

        if (blank($detected['country_label']) || blank($detected['region_label'])) {
            throw ValidationException::withMessages([
                'coordinates' => ['We could not fully detect a country and region from the provided coordinates.'],
            ]);
        }

        $country = $this->locations->findCountryForDetection(
            $detected['country_label'],
            $detected['country_code'],
        );

        $region = $country
            ? $this->locations->findRegionForDetection($country, $detected['region_label'], $detected['region_code'])
            : null;

        $county = $region
            ? $this->locations->findCountyForDetection($region, [
                $detected['county_label'],
                $detected['locality_label'],
            ])
            : null;

        $countryLabel = $country?->name ?? $detected['country_label'];
        $regionLabel = $region?->name ?? $detected['region_label'];
        $countyLabel = $county?->name ?? $detected['county_label'] ?? $detected['locality_label'];

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'label' => collect([$countyLabel, $regionLabel, $countryLabel])
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => trim((string) $value))
                ->unique()
                ->implode(', '),
            'formatted_address' => $detected['formatted_address'],
            'place_id' => $detected['place_id'],
            'country_label' => $countryLabel,
            'region_label' => $regionLabel,
            'county_label' => $countyLabel,
            'country' => $country,
            'region' => $region,
            'county' => $county,
            'matched' => [
                'country' => $country !== null,
                'region' => $region !== null,
                'county' => $county !== null,
            ],
            'can_save' => $country !== null && $region !== null && $county !== null,
        ];
    }

    public function saveUserLocation(User $user, array $attributes)
    {
        $county = $this->locations->findCountyById((int) $attributes['county_id']);

        if (! $county || $county->region_id !== (int) $attributes['region_id']) {
            throw ValidationException::withMessages([
                'county_id' => ['The selected county does not belong to the selected region.'],
            ]);
        }

        if ($county->region->country_id !== (int) $attributes['country_id']) {
            throw ValidationException::withMessages([
                'country_id' => ['The selected country does not match the selected county.'],
            ]);
        }

        return $this->locations->saveUserLocation($user, $attributes);
    }
}
