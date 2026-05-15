<?php

namespace App\Repositories\Contracts;

use App\Models\County;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Support\Collection;

interface LocationRepositoryInterface
{
    public function getActiveCountries(): Collection;

    public function getRegionsForCountry(Country $country): Collection;

    public function getCountiesForRegion(Region $region): Collection;

    public function saveUserLocation(User $user, array $attributes): UserLocation;

    public function findCountyById(int $countyId): ?County;
}
