<?php

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GoogleMapsReverseGeocodingService
{
    public function detect(float $latitude, float $longitude): array
    {
        $apiKey = (string) config('services.google_maps.api_key');

        if (blank($apiKey)) {
            throw new HttpException(503, 'Automatic location detection is currently unavailable.');
        }

        $response = Http::acceptJson()
            ->connectTimeout((int) config('services.google_maps.connect_timeout', 5))
            ->timeout((int) config('services.google_maps.request_timeout', 10))
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => sprintf('%.8F,%.8F', $latitude, $longitude),
                'key' => $apiKey,
            ]);

        if ($response->failed()) {
            throw new HttpException(503, 'Automatic location detection is currently unavailable.');
        }

        $payload = $response->json();
        $status = $payload['status'] ?? null;

        if ($status === 'ZERO_RESULTS') {
            throw ValidationException::withMessages([
                'coordinates' => ['We could not detect a location for the provided coordinates.'],
            ]);
        }

        if ($status !== 'OK' || empty($payload['results'])) {
            throw new HttpException(503, 'Automatic location detection is currently unavailable.');
        }

        $results = $payload['results'];

        return [
            'formatted_address' => $results[0]['formatted_address'] ?? null,
            'place_id' => $results[0]['place_id'] ?? null,
            'country_label' => $this->findComponentValue($results, 'country', 'long_name'),
            'country_code' => $this->findComponentValue($results, 'country', 'short_name'),
            'region_label' => $this->findComponentValue($results, 'administrative_area_level_1', 'long_name'),
            'region_code' => $this->findComponentValue($results, 'administrative_area_level_1', 'short_name'),
            'county_label' => $this->findComponentValue($results, 'administrative_area_level_2', 'long_name'),
            'locality_label' => $this->findComponentValue($results, 'locality', 'long_name')
                ?? $this->findComponentValue($results, 'administrative_area_level_3', 'long_name')
                ?? $this->findComponentValue($results, 'sublocality_level_1', 'long_name')
                ?? $this->findComponentValue($results, 'sublocality', 'long_name'),
        ];
    }

    protected function findComponentValue(array $results, string $type, string $field): ?string
    {
        foreach ($results as $result) {
            foreach (($result['address_components'] ?? []) as $component) {
                if (in_array($type, $component['types'] ?? [], true)) {
                    return $component[$field] ?? null;
                }
            }
        }

        return null;
    }
}
