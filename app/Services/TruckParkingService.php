<?php

namespace App\Services;

use App\Support\Geo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TruckParkingService
{
    public function __construct(private FallbackParkingRepository $fallbackParkingRepository)
    {
    }

    public function searchAlongRoute(array $route): array
    {
        $liveParkings = [];
        $fallbackParkings = $this->restrictToCorridor(
            parkings: $this->fallbackParkingRepository->all(),
            route: $route,
        );

        if (config('services.overpass.enabled')) {
            try {
                $liveParkings = Cache::remember(
                    'parkings:'.md5(json_encode(Geo::sampleRoute($route['geometry'], 18))),
                    now()->addMinutes(30),
                    fn () => $this->fetchFromOverpass($route['geometry'])
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $parkings = $this->merge($liveParkings, $fallbackParkings);

        return [
            'source' => $liveParkings === [] ? 'Bundled Europe dataset' : 'OpenStreetMap + bundled Europe dataset',
            'parkings' => $parkings,
            'warnings' => [],
        ];
    }

    private function fetchFromOverpass(array $geometry): array
    {
        $query = $this->buildOverpassQuery($geometry);

        $response = Http::timeout(28)
            ->acceptJson()
            ->retry(2, 400)
            ->asForm()
            ->post(config('services.overpass.base_url'), [
                'data' => $query,
            ])
            ->throw()
            ->json();

        $elements = $response['elements'] ?? [];

        return collect($elements)
            ->map(fn (array $element) => $this->mapOverpassElement($element))
            ->filter()
            ->values()
            ->all();
    }

    private function buildOverpassQuery(array $geometry): string
    {
        $points = Geo::sampleRoute($geometry, 18);
        $blocks = collect($points)->map(function (array $point) {
            $lat = number_format($point['lat'], 5, '.', '');
            $lon = number_format($point['lon'], 5, '.', '');

            return <<<BLOCK
  nwr["amenity"="parking"]["parking"="truck"](around:12000,{$lat},{$lon});
  nwr["amenity"="parking"]["hgv"~"yes|designated|official"](around:12000,{$lat},{$lon});
  nwr["highway"="rest_area"]["hgv"~"yes|designated|official"](around:12000,{$lat},{$lon});
  nwr["highway"="services"]["hgv"~"yes|designated|official"](around:12000,{$lat},{$lon});
BLOCK;
        })->implode("\n");

        return <<<OVERPASS
[out:json][timeout:20];
(
{$blocks}
);
out center tags;
OVERPASS;
    }

    private function mapOverpassElement(array $element): ?array
    {
        $tags = $element['tags'] ?? [];
        $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
        $lon = $element['lon'] ?? $element['center']['lon'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lon)) {
            return null;
        }

        $name = $tags['name']
            ?? $tags['official_name']
            ?? (($tags['highway'] ?? null) === 'rest_area' ? 'Rest area' : null)
            ?? (($tags['highway'] ?? null) === 'services' ? 'Service area' : null)
            ?? 'Truck parking';

        $amenities = [
            'showers' => $this->tagIsYes($tags, 'shower') || $this->tagIsYes($tags, 'showers'),
            'food' => $this->tagIsYes($tags, 'restaurant')
                || $this->tagIsYes($tags, 'food')
                || $this->tagIsYes($tags, 'fast_food')
                || $this->tagIsYes($tags, 'cafe')
                || $this->tagIsYes($tags, 'fuel'),
            'toilets' => $this->tagIsYes($tags, 'toilets'),
            'lighting' => $this->tagIsYes($tags, 'lit'),
            'security' => $this->tagIsYes($tags, 'surveillance')
                || $this->tagIsYes($tags, 'guard')
                || $this->tagIsYes($tags, 'security'),
        ];

        $safetyScore = 45
            + ($amenities['lighting'] ? 15 : 0)
            + ($amenities['security'] ? 20 : 0)
            + ($this->tagIsYes($tags, 'access_control') ? 10 : 0)
            + ($this->tagIsYes($tags, 'toilets') ? 5 : 0);

        return [
            'id' => ($element['type'] ?? 'node').'-'.($element['id'] ?? uniqid()),
            'name' => $name,
            'country' => $tags['addr:country'] ?? '',
            'location' => $tags['addr:city'] ?? $tags['addr:place'] ?? '',
            'lat' => (float) $lat,
            'lon' => (float) $lon,
            'paid' => ($tags['fee'] ?? 'no') === 'yes',
            'amenities' => $amenities,
            'safety_score' => min(100, $safetyScore),
            'capacity_hgv' => $tags['capacity:hgv'] ?? $tags['capacity'] ?? null,
            'source' => 'openstreetmap live',
        ];
    }

    private function restrictToCorridor(array $parkings, array $route): array
    {
        $sampledRoute = Geo::sampleRoute($route['geometry'], 26);
        $bounds = Geo::expandBounds($route['bounds'], 0.55);

        return collect($parkings)
            ->filter(function (array $parking) use ($sampledRoute, $bounds) {
                if (! Geo::pointInBounds($parking['lat'], $parking['lon'], $bounds)) {
                    return false;
                }

                return Geo::nearestDistanceToPolyline($parking['lat'], $parking['lon'], $sampledRoute) <= 35;
            })
            ->values()
            ->all();
    }

    private function merge(array $liveParkings, array $fallbackParkings): array
    {
        $unique = [];

        foreach (array_merge($liveParkings, $fallbackParkings) as $parking) {
            $key = strtolower(($parking['name'] ?? 'parking').'|'.round($parking['lat'], 3).'|'.round($parking['lon'], 3));

            if (! isset($unique[$key])) {
                $unique[$key] = $parking;
                continue;
            }

            $unique[$key] = $this->mergeParkingRecords($unique[$key], $parking);
        }

        return Collection::make($unique)->values()->all();
    }

    private function mergeParkingRecords(array $first, array $second): array
    {
        return [
            'id' => $first['id'] ?? $second['id'],
            'name' => $first['name'] ?? $second['name'],
            'country' => $first['country'] ?: $second['country'],
            'location' => $first['location'] ?: $second['location'],
            'lat' => $first['lat'],
            'lon' => $first['lon'],
            'paid' => $first['paid'] || $second['paid'],
            'amenities' => [
                'showers' => ($first['amenities']['showers'] ?? false) || ($second['amenities']['showers'] ?? false),
                'food' => ($first['amenities']['food'] ?? false) || ($second['amenities']['food'] ?? false),
                'toilets' => ($first['amenities']['toilets'] ?? false) || ($second['amenities']['toilets'] ?? false),
                'lighting' => ($first['amenities']['lighting'] ?? false) || ($second['amenities']['lighting'] ?? false),
                'security' => ($first['amenities']['security'] ?? false) || ($second['amenities']['security'] ?? false),
            ],
            'safety_score' => max($first['safety_score'] ?? 50, $second['safety_score'] ?? 50),
            'capacity_hgv' => $first['capacity_hgv'] ?? $second['capacity_hgv'],
            'source' => $first['source'] === 'openstreetmap live' ? $first['source'] : $second['source'],
        ];
    }

    private function tagIsYes(array $tags, string $key): bool
    {
        return in_array(strtolower((string) ($tags[$key] ?? '')), ['yes', '24/7', 'customers', 'public'], true);
    }
}
