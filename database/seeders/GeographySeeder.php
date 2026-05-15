<?php

namespace Database\Seeders;

use App\Enums\CountyStatus;
use App\Models\Country;
use App\Models\County;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nnjeim\World\Models\City as WorldCity;
use Nnjeim\World\Models\Country as WorldCountry;
use Nnjeim\World\Models\State as WorldState;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->runningUnitTests()) {
            $this->seedMinimalUnitedStates();

            return;
        }

        if (! $this->hasWorldTables()) {
            $this->seedMinimalUnitedStates();

            return;
        }

        if (! WorldCountry::query()->where('iso2', 'US')->exists()) {
            $this->call(WorldSeeder::class);
        }

        if (! WorldCountry::query()->where('iso2', 'US')->exists()) {
            $this->seedMinimalUnitedStates();

            return;
        }

        $this->seedUnitedStatesFromWorld();
    }

    protected function seedUnitedStatesFromWorld(): void
    {
        $today = now()->toDateString();
        $usa = Country::query()->updateOrCreate(
            ['iso2' => 'US'],
            ['name' => 'United States', 'iso3' => 'USA', 'is_active' => true],
        );

        $worldUsa = WorldCountry::query()->where('iso2', 'US')->firstOrFail();
        $regionLookup = [];

        WorldState::query()
            ->where('country_id', $worldUsa->id)
            ->orderBy('name')
            ->chunkById(100, function ($states) use ($usa, &$regionLookup): void {
                foreach ($states as $state) {
                    $region = Region::query()->updateOrCreate(
                        ['country_id' => $usa->id, 'name' => $state->name],
                        [
                            'code' => $state->state_code ?: null,
                            'type' => $state->type ?: 'state',
                            'is_active' => true,
                        ],
                    );

                    $regionLookup[$state->id] = [
                        'id' => $region->id,
                        'code' => $state->state_code ?: null,
                    ];
                }
            });

        $now = now();

        WorldCity::query()
            ->where('country_id', $worldUsa->id)
            ->orderBy('id')
            ->chunkById(1000, function ($cities) use ($regionLookup, $now, $today): void {
                $payload = [];

                foreach ($cities as $city) {
                    $region = $regionLookup[$city->state_id] ?? null;

                    if (! $region || blank($city->name)) {
                        continue;
                    }

                    $payload[] = [
                        'region_id' => $region['id'],
                        'name' => $city->name,
                        'slug' => $this->worldCitySlug($city->name, (int) $city->id),
                        'status' => CountyStatus::Active->value,
                        'launch_date' => $today,
                        'timezone' => $this->timezoneForState($region['code']),
                        'is_featured' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                }

                if ($payload !== []) {
                    County::query()->upsert(
                        $payload,
                        ['region_id', 'slug'],
                        ['name', 'status', 'launch_date', 'timezone', 'is_featured', 'updated_at', 'deleted_at'],
                    );
                }
            });

        $alabama = Region::query()
            ->where('country_id', $usa->id)
            ->where('code', 'AL')
            ->firstOrFail();

        County::query()->updateOrCreate(
            ['region_id' => $alabama->id, 'slug' => 'butler-county'],
            [
                'name' => 'Butler County',
                'status' => CountyStatus::Active->value,
                'launch_date' => $today,
                'timezone' => 'America/Chicago',
                'is_featured' => true,
            ],
        );

        County::query()->whereIn('slug', ['jefferson-county', 'manhattan-area'])->update([
            'status' => CountyStatus::Active->value,
            'launch_date' => $today,
            'is_featured' => true,
        ]);
    }

    protected function seedMinimalUnitedStates(): void
    {
        $today = now()->toDateString();
        $usa = Country::query()->updateOrCreate(
            ['iso2' => 'US'],
            ['name' => 'United States', 'iso3' => 'USA', 'is_active' => true],
        );

        $alabama = Region::query()->updateOrCreate(
            ['country_id' => $usa->id, 'name' => 'Alabama'],
            ['code' => 'AL', 'type' => 'state', 'is_active' => true],
        );

        $newYork = Region::query()->updateOrCreate(
            ['country_id' => $usa->id, 'name' => 'New York'],
            ['code' => 'NY', 'type' => 'state', 'is_active' => true],
        );

        County::query()->updateOrCreate(
            ['region_id' => $alabama->id, 'slug' => 'butler-county'],
            [
                'name' => 'Butler County',
                'status' => CountyStatus::Active->value,
                'launch_date' => $today,
                'timezone' => 'America/Chicago',
                'is_featured' => true,
            ],
        );

        County::query()->updateOrCreate(
            ['region_id' => $alabama->id, 'slug' => 'jefferson-county'],
            [
                'name' => 'Jefferson County',
                'status' => CountyStatus::Active->value,
                'launch_date' => $today,
                'timezone' => 'America/Chicago',
                'is_featured' => true,
            ],
        );

        County::query()->updateOrCreate(
            ['region_id' => $newYork->id, 'slug' => 'manhattan-area'],
            [
                'name' => 'Manhattan Area',
                'status' => CountyStatus::Active->value,
                'launch_date' => $today,
                'timezone' => 'America/New_York',
                'is_featured' => true,
            ],
        );
    }

    protected function hasWorldTables(): bool
    {
        return Schema::hasTable(config('world.migrations.countries.table_name'))
            && Schema::hasTable(config('world.migrations.states.table_name'))
            && Schema::hasTable(config('world.migrations.cities.table_name'));
    }

    protected function worldCitySlug(string $name, int $worldCityId): string
    {
        return Str::slug($name).'-'.$worldCityId;
    }

    protected function timezoneForState(?string $stateCode): string
    {
        return match ($stateCode) {
            'AL', 'AR', 'IA', 'IL', 'KS', 'LA', 'MN', 'MO', 'MS', 'OK', 'SD', 'TX', 'WI' => 'America/Chicago',
            'AK' => 'America/Anchorage',
            'AZ' => 'America/Phoenix',
            'CA', 'NV', 'WA', 'OR' => 'America/Los_Angeles',
            'CO', 'MT', 'NM', 'UT', 'WY' => 'America/Denver',
            'CT', 'DC', 'DE', 'FL', 'GA', 'IN', 'KY', 'MA', 'MD', 'ME', 'MI', 'NC', 'NH', 'NJ', 'NY', 'OH', 'PA', 'RI', 'SC', 'TN', 'VA', 'VT', 'WV' => 'America/New_York',
            'HI' => 'Pacific/Honolulu',
            'ID' => 'America/Boise',
            'ND', 'NE' => 'America/Chicago',
            'PR', 'VI' => 'America/Puerto_Rico',
            default => 'America/New_York',
        };
    }
}
