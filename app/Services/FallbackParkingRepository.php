<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class FallbackParkingRepository
{
    public function all(): array
    {
        return Cache::remember('fallback-parkings:v1', now()->addHours(6), function () {
            $path = storage_path('app/private/data/europe-truck-parking.json');
            $data = json_decode((string) file_get_contents($path), true);

            if (! is_array($data)) {
                return [];
            }

            return array_map(function (array $parking) {
                return [
                    'id' => Arr::get($parking, 'id'),
                    'name' => Arr::get($parking, 'name', 'Unnamed truck parking'),
                    'country' => Arr::get($parking, 'country', ''),
                    'location' => Arr::get($parking, 'location', ''),
                    'lat' => (float) Arr::get($parking, 'lat'),
                    'lon' => (float) Arr::get($parking, 'lon'),
                    'paid' => (bool) Arr::get($parking, 'paid', false),
                    'amenities' => [
                        'showers' => (bool) Arr::get($parking, 'amenities.showers', false),
                        'food' => (bool) Arr::get($parking, 'amenities.food', false),
                        'toilets' => (bool) Arr::get($parking, 'amenities.toilets', false),
                        'lighting' => (bool) Arr::get($parking, 'amenities.lighting', false),
                        'security' => (bool) Arr::get($parking, 'amenities.security', false),
                    ],
                    'safety_score' => (int) Arr::get($parking, 'safety_score', 50),
                    'capacity_hgv' => Arr::get($parking, 'capacity_hgv'),
                    'source' => (string) Arr::get($parking, 'source', 'fallback dataset'),
                ];
            }, $data);
        });
    }

    public function count(): int
    {
        return count($this->all());
    }
}
