<?php

namespace App\Support;

class Geo
{
    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    public static function cumulativeDistances(array $geometry): array
    {
        $distances = [0.0];

        foreach ($geometry as $index => $point) {
            if ($index === 0) {
                continue;
            }

            $previous = $geometry[$index - 1];
            $distances[$index] = $distances[$index - 1] + self::haversineKm(
                $previous['lat'],
                $previous['lon'],
                $point['lat'],
                $point['lon']
            );
        }

        return $distances;
    }

    public static function bounds(array $geometry): array
    {
        $lats = array_column($geometry, 'lat');
        $lons = array_column($geometry, 'lon');

        return [
            'south' => min($lats),
            'west' => min($lons),
            'north' => max($lats),
            'east' => max($lons),
        ];
    }

    public static function expandBounds(array $bounds, float $paddingDegrees): array
    {
        return [
            'south' => $bounds['south'] - $paddingDegrees,
            'west' => $bounds['west'] - $paddingDegrees,
            'north' => $bounds['north'] + $paddingDegrees,
            'east' => $bounds['east'] + $paddingDegrees,
        ];
    }

    public static function pointInBounds(float $lat, float $lon, array $bounds): bool
    {
        return $lat >= $bounds['south']
            && $lat <= $bounds['north']
            && $lon >= $bounds['west']
            && $lon <= $bounds['east'];
    }

    public static function sampleRoute(array $geometry, int $maxPoints = 14): array
    {
        $count = count($geometry);

        if ($count <= $maxPoints) {
            return $geometry;
        }

        $step = max(1, (int) floor($count / ($maxPoints - 1)));
        $sampled = [];

        for ($index = 0; $index < $count; $index += $step) {
            $sampled[] = $geometry[$index];
        }

        $lastPoint = $geometry[$count - 1];

        if ($sampled[array_key_last($sampled)] !== $lastPoint) {
            $sampled[] = $lastPoint;
        }

        return $sampled;
    }

    public static function matchPointToRoute(float $lat, float $lon, array $geometry, array $cumulativeKm): array
    {
        $nearestIndex = 0;
        $nearestDistance = null;

        foreach ($geometry as $index => $point) {
            $distance = self::haversineKm($lat, $lon, $point['lat'], $point['lon']);

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestIndex = $index;
            }
        }

        return [
            'distance_to_route_km' => $nearestDistance ?? 0.0,
            'distance_along_km' => $cumulativeKm[$nearestIndex] ?? 0.0,
        ];
    }

    public static function nearestDistanceToPolyline(float $lat, float $lon, array $geometry): float
    {
        $nearestDistance = null;

        foreach ($geometry as $point) {
            $distance = self::haversineKm($lat, $lon, $point['lat'], $point['lon']);

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
            }
        }

        return $nearestDistance ?? INF;
    }
}
