<?php

namespace App\Repositories\Eloquent;

use App\Models\County;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use App\Models\UserLocation;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

    public function findCountryForDetection(?string $countryName, ?string $countryIso2): ?Country
    {
        if (blank($countryName) && blank($countryIso2)) {
            return null;
        }

        return Country::query()
            ->where('is_active', true)
            ->where(function ($query) use ($countryName, $countryIso2): void {
                if (filled($countryIso2)) {
                    $query->orWhereRaw('UPPER(iso2) = ?', [Str::upper($countryIso2)]);
                }

                if (filled($countryName)) {
                    $query->orWhereRaw('LOWER(name) = ?', [Str::lower(trim($countryName))]);
                }
            })
            ->first();
    }

    public function findRegionForDetection(Country $country, ?string $regionName, ?string $regionCode): ?Region
    {
        if (blank($regionName) && blank($regionCode)) {
            return null;
        }

        return Region::query()
            ->where('country_id', $country->id)
            ->where('is_active', true)
            ->where(function ($query) use ($regionName, $regionCode): void {
                if (filled($regionCode)) {
                    $query->orWhereRaw('UPPER(code) = ?', [Str::upper($regionCode)]);
                }

                if (filled($regionName)) {
                    $query->orWhereRaw('LOWER(name) = ?', [Str::lower(trim($regionName))]);
                }
            })
            ->first();
    }

    public function findCountyForDetection(Region $region, array $candidateNames): ?County
    {
        $normalizedNames = collect($candidateNames)
            ->filter(fn ($name) => filled($name))
            ->map(fn ($name) => Str::lower(trim((string) $name)))
            ->unique()
            ->values();

        if ($normalizedNames->isEmpty()) {
            return null;
        }

        return County::query()
            ->where('region_id', $region->id)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($normalizedNames): void {
                foreach ($normalizedNames as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [$name]);
                }
            })
            ->first();
    }
}
