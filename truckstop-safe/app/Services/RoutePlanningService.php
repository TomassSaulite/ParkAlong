<?php

namespace App\Services;

use App\Support\Geo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class RoutePlanningService
{
    public function buildRoute(string $originQuery, string $destinationQuery, ?string $preplannedRoute = null): array
    {
        if (filled($preplannedRoute)) {
            return $this->buildRouteFromPreplannedGeometry($originQuery, $destinationQuery, $preplannedRoute);
        }

        $cacheKey = 'route:'.md5($originQuery.'|'.$destinationQuery);

        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($originQuery, $destinationQuery) {
            $origin = $this->geocode($originQuery);
            $destination = $this->geocode($destinationQuery);

            $warnings = [];
            $route = null;

            if (filled(config('services.openrouteservice.key'))) {
                try {
                    $route = $this->fetchOpenRouteServiceRoute($origin, $destination);
                } catch (Throwable $exception) {
                    report($exception);
                    $warnings[] = 'OpenRouteService truck routing was unavailable, so the app switched to OSRM driving directions for this result.';
                }
            }

            if ($route === null) {
                $route = $this->fetchOsrmRoute($origin, $destination);
            }

            return array_merge($route, [
                'origin' => $origin,
                'destination' => $destination,
                'cumulative_km' => Geo::cumulativeDistances($route['geometry']),
                'bounds' => Geo::bounds($route['geometry']),
                'warnings' => $warnings,
            ]);
        });
    }

    public function buildRouteFromPreplannedGeometry(string $originQuery, string $destinationQuery, string $preplannedRoute): array
    {
        $cacheKey = 'route-preplanned:'.md5($originQuery.'|'.$destinationQuery.'|'.$preplannedRoute);

        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($originQuery, $destinationQuery, $preplannedRoute) {
            $origin = $this->geocode($originQuery);
            $destination = $this->geocode($destinationQuery);
            $geometry = $this->parsePreplannedRoute($preplannedRoute);
            $cumulativeKm = Geo::cumulativeDistances($geometry);
            $distanceKm = round((float) end($cumulativeKm), 1);

            return [
                'source' => 'Preplanned route',
                'distance_km' => $distanceKm,
                'duration_minutes' => $this->estimateRouteDurationMinutes($distanceKm),
                'geometry' => $geometry,
                'origin' => $origin,
                'destination' => $destination,
                'cumulative_km' => $cumulativeKm,
                'bounds' => Geo::bounds($geometry),
                'warnings' => [
                    'Using the pasted preplanned route instead of live route calculation.',
                ],
            ];
        });
    }

    public function suggestLocations(string $query, int $limit = 7): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $cacheKey = 'location-suggest:'.md5(strtolower($query).'|'.$limit);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($query, $limit) {
            $response = Http::timeout(10)
                ->acceptJson()
                ->retry(2, 250)
                ->withHeaders([
                    'User-Agent' => config('services.nominatim.user_agent'),
                ])
                ->get(rtrim(config('services.nominatim.base_url'), '/').'/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => $limit,
                    'addressdetails' => 1,
                    'countrycodes' => 'al,ad,at,be,ba,bg,by,ch,cy,cz,de,dk,ee,es,fi,fr,gb,ge,gr,hr,hu,ie,is,it,li,lt,lu,lv,mc,md,me,mk,mt,nl,no,pl,pt,ro,rs,se,si,sk,sm,ua,va',
                ])
                ->throw()
                ->json();

            return collect(is_array($response) ? $response : [])
                ->map(function (array $item) {
                    $address = $item['address'] ?? [];
                    $primary = $address['city']
                        ?? $address['town']
                        ?? $address['village']
                        ?? $address['municipality']
                        ?? $address['county']
                        ?? $item['name']
                        ?? null;

                    if (! $primary) {
                        return null;
                    }

                    $country = $address['country'] ?? null;
                    $label = $country ? "{$primary}, {$country}" : $primary;

                    return [
                        'value' => $label,
                        'label' => $primary,
                        'subtitle' => $country ?? ($item['display_name'] ?? ''),
                        'lat' => isset($item['lat']) ? (float) $item['lat'] : null,
                        'lon' => isset($item['lon']) ? (float) $item['lon'] : null,
                    ];
                })
                ->filter()
                ->unique('value')
                ->values()
                ->all();
        });
    }

    private function geocode(string $query): array
    {
        $response = Http::timeout(12)
            ->acceptJson()
            ->retry(2, 250)
            ->withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
            ])
            ->get(rtrim(config('services.nominatim.base_url'), '/').'/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'addressdetails' => 1,
            ])
            ->throw()
            ->json();

        if (! is_array($response) || empty($response[0])) {
            throw new RuntimeException("We couldn't find a location for \"{$query}\".");
        }

        $first = $response[0];

        return [
            'label' => $first['display_name'] ?? $query,
            'lat' => (float) $first['lat'],
            'lon' => (float) $first['lon'],
        ];
    }

    private function fetchOpenRouteServiceRoute(array $origin, array $destination): array
    {
        $response = Http::timeout(18)
            ->acceptJson()
            ->retry(2, 300)
            ->withToken(config('services.openrouteservice.key'))
            ->post(rtrim(config('services.openrouteservice.base_url'), '/').'/v2/directions/driving-hgv/geojson', [
                'coordinates' => [
                    [$origin['lon'], $origin['lat']],
                    [$destination['lon'], $destination['lat']],
                ],
            ])
            ->throw()
            ->json();

        $feature = $response['features'][0] ?? null;
        $summary = $feature['properties']['summary'] ?? null;
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        if (! $feature || ! is_array($summary) || ! is_array($coordinates)) {
            throw new RuntimeException('OpenRouteService returned an incomplete route response.');
        }

        return [
            'source' => 'OpenRouteService HGV',
            'distance_km' => round(($summary['distance'] ?? 0) / 1000, 1),
            'duration_minutes' => (int) round(($summary['duration'] ?? 0) / 60),
            'geometry' => array_map(
                fn (array $point) => ['lat' => (float) $point[1], 'lon' => (float) $point[0]],
                $coordinates
            ),
        ];
    }

    private function fetchOsrmRoute(array $origin, array $destination): array
    {
        $response = Http::timeout(18)
            ->acceptJson()
            ->retry(2, 300)
            ->get(sprintf(
                'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s',
                $origin['lon'],
                $origin['lat'],
                $destination['lon'],
                $destination['lat']
            ), [
                'overview' => 'full',
                'geometries' => 'geojson',
            ])
            ->throw()
            ->json();

        $route = $response['routes'][0] ?? null;
        $coordinates = $route['geometry']['coordinates'] ?? null;

        if (! $route || ! is_array($coordinates)) {
            throw new RuntimeException('OSRM returned an incomplete route response.');
        }

        return [
            'source' => 'OSRM driving',
            'distance_km' => round(($route['distance'] ?? 0) / 1000, 1),
            'duration_minutes' => (int) round(($route['duration'] ?? 0) / 60),
            'geometry' => array_map(
                fn (array $point) => ['lat' => (float) $point[1], 'lon' => (float) $point[0]],
                $coordinates
            ),
        ];
    }

    private function parsePreplannedRoute(string $input): array
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            throw new RuntimeException('The preplanned route field is empty.');
        }

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            $geometry = $this->parseJsonRoute($decoded);

            if ($geometry !== []) {
                return $geometry;
            }
        }

        $geometry = collect(preg_split('/\r\n|\r|\n/', $trimmed))
            ->map(fn (?string $line) => trim((string) $line))
            ->filter()
            ->map(function (string $line) {
                $parts = preg_split('/[\s,;]+/', $line);

                if (count($parts) < 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
                    return null;
                }

                return [
                    'lat' => (float) $parts[0],
                    'lon' => (float) $parts[1],
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (count($geometry) < 2) {
            throw new RuntimeException('Preplanned route must contain at least two coordinate points. Use one "lat,lon" pair per line or paste a GeoJSON LineString.');
        }

        return $geometry;
    }

    private function parseJsonRoute(array $decoded): array
    {
        if (($decoded['type'] ?? null) === 'Feature' && isset($decoded['geometry'])) {
            return $this->parseJsonRoute($decoded['geometry']);
        }

        if (($decoded['type'] ?? null) === 'LineString' && is_array($decoded['coordinates'] ?? null)) {
            return collect($decoded['coordinates'])
                ->filter(fn ($point) => is_array($point) && count($point) >= 2)
                ->map(fn (array $point) => ['lat' => (float) $point[1], 'lon' => (float) $point[0]])
                ->values()
                ->all();
        }

        if (array_is_list($decoded) && isset($decoded[0]['lat'], $decoded[0]['lon'])) {
            return collect($decoded)
                ->map(fn (array $point) => ['lat' => (float) $point['lat'], 'lon' => (float) $point['lon']])
                ->values()
                ->all();
        }

        return [];
    }

    private function estimateRouteDurationMinutes(float $distanceKm): int
    {
        return max(1, (int) round(($distanceKm / 72) * 60));
    }
}
