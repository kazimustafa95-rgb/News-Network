<?php

namespace App\Repositories\Eloquent;

use App\Models\County;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use App\Models\UserLocation;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentLocationRepository implements LocationRepositoryInterface
{
    public function getActiveCountries(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getRegionsForCountry(Country $country): Collection
    {
        return $country->regions()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getCountiesForRegion(Region $region): Collection
    {
        return $region->counties()
            ->orderBy('name')
            ->get();
    }

    public function saveUserLocation(User $user, array $attributes): UserLocation
    {
        if (($attributes['is_default'] ?? false) === true) {
            $user->locations()->update(['is_default' => false]);
        }

        return UserLocation::updateOrCreate(
            [
                'user_id' => $user->id,
                'county_id' => $attributes['county_id'],
            ],
            $attributes + ['user_id' => $user->id],
        );
    }

    public function findCountyById(int $countyId): ?County
    {
        return County::query()
            ->with('region.country')
            ->find($countyId);
    }
}
