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
    public function __construct(private readonly LocationRepositoryInterface $locations)
    {
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
