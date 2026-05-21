<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\Country;
use App\Models\County;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_maps.api_key', 'test-google-maps-key');
        $this->seed(GeographySeeder::class);
    }

    public function test_auto_detect_location_returns_matched_country_region_and_county(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Greenville, Butler County, Alabama, USA',
                    'place_id' => 'google-place-id-123',
                    'address_components' => [
                        [
                            'long_name' => 'Greenville',
                            'short_name' => 'Greenville',
                            'types' => ['locality', 'political'],
                        ],
                        [
                            'long_name' => 'Butler County',
                            'short_name' => 'Butler County',
                            'types' => ['administrative_area_level_2', 'political'],
                        ],
                        [
                            'long_name' => 'Alabama',
                            'short_name' => 'AL',
                            'types' => ['administrative_area_level_1', 'political'],
                        ],
                        [
                            'long_name' => 'United States',
                            'short_name' => 'US',
                            'types' => ['country', 'political'],
                        ],
                    ],
                ]],
            ]),
        ]);

        $response = $this->postJson('/api/locations/auto-detect', [
            'latitude' => 31.8296,
            'longitude' => -86.6178,
        ]);

        $country = Country::query()->where('iso2', 'US')->firstOrFail();
        $region = Region::query()->where('country_id', $country->id)->where('code', 'AL')->firstOrFail();
        $county = County::query()->where('region_id', $region->id)->where('slug', 'butler-county')->firstOrFail();

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Location detected successfully.')
            ->assertJsonPath('data.country_id', $country->id)
            ->assertJsonPath('data.region_id', $region->id)
            ->assertJsonPath('data.county_id', $county->id)
            ->assertJsonPath('data.country_label', 'United States')
            ->assertJsonPath('data.region_label', 'Alabama')
            ->assertJsonPath('data.county_label', 'Butler County')
            ->assertJsonPath('data.label', 'Butler County, Alabama, United States')
            ->assertJsonPath('data.can_save', true)
            ->assertJsonPath('data.matched.country', true)
            ->assertJsonPath('data.matched.region', true)
            ->assertJsonPath('data.matched.county', true);
    }

    public function test_auto_detect_location_returns_labels_even_when_county_is_not_matched(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Birmingham, Alabama, USA',
                    'place_id' => 'google-place-id-456',
                    'address_components' => [
                        [
                            'long_name' => 'Birmingham',
                            'short_name' => 'Birmingham',
                            'types' => ['locality', 'political'],
                        ],
                        [
                            'long_name' => 'Jefferson County',
                            'short_name' => 'Jefferson County',
                            'types' => ['administrative_area_level_2', 'political'],
                        ],
                        [
                            'long_name' => 'Alabama',
                            'short_name' => 'AL',
                            'types' => ['administrative_area_level_1', 'political'],
                        ],
                        [
                            'long_name' => 'United States',
                            'short_name' => 'US',
                            'types' => ['country', 'political'],
                        ],
                    ],
                ]],
            ]),
        ]);

        County::query()->where('slug', 'jefferson-county')->delete();

        $response = $this->postJson('/api/locations/auto-detect', [
            'latitude' => 33.5186,
            'longitude' => -86.8104,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.country_label', 'United States')
            ->assertJsonPath('data.region_label', 'Alabama')
            ->assertJsonPath('data.county_label', 'Jefferson County')
            ->assertJsonPath('data.county_id', null)
            ->assertJsonPath('data.can_save', false)
            ->assertJsonPath('data.matched.country', true)
            ->assertJsonPath('data.matched.region', true)
            ->assertJsonPath('data.matched.county', false);
    }

    public function test_auto_detect_location_returns_validation_error_when_google_finds_no_results(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'ZERO_RESULTS',
                'results' => [],
            ]),
        ]);

        $response = $this->postJson('/api/locations/auto-detect', [
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonPath('errors.coordinates.0', 'We could not detect a location for the provided coordinates.');
    }

    public function test_save_user_location_accepts_string_boolean_value(): void
    {
        $country = Country::query()->where('iso2', 'US')->firstOrFail();
        $region = Region::query()->where('country_id', $country->id)->where('code', 'AL')->firstOrFail();
        $county = County::query()->where('region_id', $region->id)->where('slug', 'butler-county')->firstOrFail();

        $user = User::factory()->create([
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, [], 'api');

        $response = $this->post('/api/user/location', [
            'country_id' => $country->id,
            'region_id' => $region->id,
            'county_id' => $county->id,
            'label' => 'Detected Home',
            'is_default' => 'true',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_default', true);
    }
}
