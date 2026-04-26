<?php

namespace App\Services;

use App\Support\Geo;
use Illuminate\Support\Collection;

class ParkingRecommendationEngine
{
    public function rank(array $parkings, array $route, array $filters): array
    {
        $ranked = Collection::make($parkings)
            ->map(fn (array $parking) => $this->enrichParking($parking, $route, $filters))
            ->filter(fn (array $parking) => $this->passesFilters($parking, $filters))
            ->sortByDesc('score')
            ->values();

        $reachable = $ranked->where('is_reachable', true)->values();

        return ($reachable->isNotEmpty() ? $reachable : $ranked)
            ->take(12)
            ->all();
    }

    private function enrichParking(array $parking, array $route, array $filters): array
    {
        $match = Geo::matchPointToRoute(
            $parking['lat'],
            $parking['lon'],
            $route['geometry'],
            $route['cumulative_km']
        );

        $etaMinutes = $route['distance_km'] > 0
            ? (int) round($route['duration_minutes'] * ($match['distance_along_km'] / $route['distance_km']))
            : 0;

        $availableMinutes = (int) $filters['driving_minutes'];
        $bufferMinutes = (int) $filters['buffer_minutes'];
        $targetMinutes = max(30, $availableMinutes - $bufferMinutes);
        $rankingFocus = $filters['ranking_focus'] ?? 'balanced';

        $usageRatio = $availableMinutes > 0 ? min(1.5, $etaMinutes / $availableMinutes) : 0;
        $timingPenalty = abs($etaMinutes - $targetMinutes) / max(30, $availableMinutes);
        $timingScore = $etaMinutes <= $availableMinutes
            ? max(0, 42 - ($timingPenalty * 40))
            : max(0, 8 - (($etaMinutes - $availableMinutes) / 15));

        $routeScore = max(0, 28 - ($match['distance_to_route_km'] * 2.8));
        $amenityRichness = collect($parking['amenities'])->filter()->count();
        $matchedRequestedAmenities = collect([
            'showers' => (bool) $filters['needs_shower'],
            'food' => (bool) $filters['needs_food'],
            'toilets' => (bool) $filters['needs_toilets'],
            'lighting' => (bool) $filters['needs_lighting'],
            'security' => (bool) $filters['needs_security'],
        ])->reduce(function (int $carry, bool $needed, string $amenity) use ($parking) {
            if (! $needed) {
                return $carry;
            }

            return $carry + (($parking['amenities'][$amenity] ?? false) ? 1 : 0);
        }, 0);

        $safetyScore = (($parking['safety_score'] ?? 50) / 100) * 20;
        $costScore = match ($filters['parking_cost']) {
            'free' => $parking['paid'] ? 0 : 6,
            'paid' => $parking['paid'] ? 4 : 1,
            default => $parking['paid'] ? 2 : 4,
        };
        $focusBonus = $this->focusBonus($rankingFocus, $timingScore, $routeScore, $safetyScore, $parking);

        $score = round($timingScore + $routeScore + $safetyScore + ($amenityRichness * 1.5) + ($matchedRequestedAmenities * 4) + $costScore + $focusBonus, 1);

        $parking['distance_to_route_km'] = round($match['distance_to_route_km'], 1);
        $parking['distance_along_route_km'] = round($match['distance_along_km'], 1);
        $parking['eta_minutes'] = $etaMinutes;
        $parking['remaining_drive_minutes'] = max(0, $availableMinutes - $etaMinutes);
        $parking['time_usage_ratio'] = round($usageRatio, 2);
        $parking['score'] = $score;
        $parking['is_reachable'] = $etaMinutes <= $availableMinutes;
        $parking['safety_stars'] = max(1, (int) round(($parking['safety_score'] ?? 50) / 20));
        $parking['fit_label'] = $this->buildFitLabel($etaMinutes, $availableMinutes, $bufferMinutes);
        $parking['reasons'] = $this->buildReasons($parking, $matchedRequestedAmenities, $availableMinutes, $bufferMinutes);
        $parking['focus_label'] = $this->focusLabel($rankingFocus);

        return $parking;
    }

    private function focusBonus(string $focus, float $timingScore, float $routeScore, float $safetyScore, array $parking): float
    {
        return match ($focus) {
            'max_drive' => $timingScore * 0.35,
            'min_detour' => $routeScore * 0.45,
            'max_safety' => $safetyScore * 0.55,
            default => ($timingScore * 0.1) + ($routeScore * 0.1) + ($safetyScore * 0.1),
        };
    }

    private function passesFilters(array $parking, array $filters): bool
    {
        if (($filters['parking_cost'] ?? 'any') === 'free' && ($parking['paid'] ?? false)) {
            return false;
        }

        if (($filters['parking_cost'] ?? 'any') === 'paid' && ! ($parking['paid'] ?? false)) {
            return false;
        }

        if (($parking['safety_stars'] ?? 1) < (int) ($filters['safety_min'] ?? 1)) {
            return false;
        }

        foreach (['showers', 'food', 'toilets', 'lighting', 'security'] as $amenity) {
            $filterKey = 'needs_'.$amenity;

            if ($amenity === 'showers') {
                $filterKey = 'needs_shower';
            }

            if ($amenity === 'food') {
                $filterKey = 'needs_food';
            }

            if (($filters[$filterKey] ?? false) && ! ($parking['amenities'][$amenity] ?? false)) {
                return false;
            }
        }

        return true;
    }

    private function buildReasons(array $parking, int $matchedRequestedAmenities, int $availableMinutes, int $bufferMinutes): array
    {
        $reasons = [];

        if ($parking['is_reachable'] ?? false) {
            if (($parking['eta_minutes'] ?? 0) >= max(30, $availableMinutes - $bufferMinutes)) {
                $reasons[] = 'Uses almost all remaining legal drive time';
            } else {
                $reasons[] = 'Fits inside the current legal drive window';
            }
        }

        if (($parking['distance_to_route_km'] ?? 999) <= 5) {
            $reasons[] = 'Very close to the planned corridor';
        } elseif (($parking['distance_to_route_km'] ?? 999) <= 15) {
            $reasons[] = 'Low detour from the main route';
        }

        if (($parking['safety_stars'] ?? 0) >= 4) {
            $reasons[] = 'Strong safety profile for an overnight or end-shift stop';
        }

        if ($matchedRequestedAmenities >= 2) {
            $reasons[] = 'Matches the requested driver amenities';
        }

        if (! ($parking['paid'] ?? true)) {
            $reasons[] = 'No paid parking requirement detected';
        }

        return array_slice(array_values(array_unique($reasons)), 0, 3);
    }

    private function buildFitLabel(int $etaMinutes, int $availableMinutes, int $bufferMinutes): string
    {
        if ($etaMinutes > $availableMinutes) {
            return 'Beyond current legal drive window';
        }

        if ($etaMinutes >= max(30, $availableMinutes - $bufferMinutes)) {
            return 'Best end-of-shift timing';
        }

        if ($etaMinutes >= (int) round($availableMinutes * 0.65)) {
            return 'Good fit with some reserve time';
        }

        return 'Earlier stop with more spare minutes';
    }

    private function focusLabel(string $focus): string
    {
        return match ($focus) {
            'max_drive' => 'Max drive time',
            'min_detour' => 'Lowest detour',
            'max_safety' => 'Highest safety',
            default => 'Balanced',
        };
    }
}
