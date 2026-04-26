<?php

namespace App\Services;

use App\Support\Duration;

class TripPlanningService
{
    public function __construct(
        private RoutePlanningService $routePlanningService,
        private TruckParkingService $truckParkingService,
        private ParkingRecommendationEngine $parkingRecommendationEngine,
        private FallbackParkingRepository $fallbackParkingRepository,
    ) {
    }

    public function buildPlan(array $filters): array
    {
        $route = $this->routePlanningService->buildRoute(
            $filters['origin'],
            $filters['destination'],
            $filters['preplanned_route'] ?: null,
        );
        $parkingLookup = $this->truckParkingService->searchAlongRoute($route);
        $recommendations = $this->parkingRecommendationEngine->rank($parkingLookup['parkings'], $route, $filters);

        $reachableCount = collect($recommendations)->where('is_reachable', true)->count();
        $datasetCount = $this->fallbackParkingRepository->count();

        return [
            'route' => $route,
            'recommendations' => $recommendations,
            'summary' => [
                'parking_count' => count($recommendations),
                'candidate_count' => count($parkingLookup['parkings']),
                'dataset_count' => $datasetCount,
                'reachable_count' => $reachableCount,
                'route_source' => $route['source'],
                'parking_source' => $parkingLookup['source'],
                'available_drive_label' => Duration::humanize((int) $filters['driving_minutes']),
                'buffer_label' => Duration::humanize((int) $filters['buffer_minutes']),
                'operation_mode_label' => $this->operationModeLabel($filters['operation_mode'] ?? 'single'),
                'using_preplanned_route' => filled($filters['preplanned_route'] ?? ''),
            ],
            'insights' => $this->buildInsights($recommendations, $filters),
            'warnings' => array_values(array_filter(array_merge(
                $route['warnings'] ?? [],
                $parkingLookup['warnings'] ?? [],
            ))),
        ];
    }

    private function operationModeLabel(string $mode): string
    {
        return match ($mode) {
            'crew' => 'Crew',
            default => 'Single driver',
        };
    }

    private function buildInsights(array $recommendations, array $filters): array
    {
        $collection = collect($recommendations);
        $reachable = $collection->where('is_reachable', true);

        $insights = [
            [
                'title' => 'Best match',
                'value' => $collection->first()['name'] ?? 'No result yet',
                'note' => $collection->first()['fit_label'] ?? 'Waiting for route analysis',
            ],
        ];

        if ($reachable->isNotEmpty()) {
            $safest = $reachable->sortByDesc('safety_score')->first();
            $closest = $reachable->sortBy('distance_to_route_km')->first();

            if ($safest) {
                $insights[] = [
                    'title' => 'Safest reachable',
                    'value' => $safest['name'],
                    'note' => "{$safest['safety_stars']}/5 safety · ETA ".Duration::humanize((int) $safest['eta_minutes']),
                ];
            }

            if ($closest) {
                $insights[] = [
                    'title' => 'Lowest detour',
                    'value' => $closest['name'],
                    'note' => "{$closest['distance_to_route_km']} km off route · ".($closest['paid'] ? 'paid' : 'free/unknown'),
                ];
            }

            $freeStop = $reachable->firstWhere('paid', false);

            if ($freeStop) {
                $insights[] = [
                    'title' => 'Budget-friendly',
                    'value' => $freeStop['name'],
                    'note' => 'Reachable without a paid parking requirement',
                ];
            }
        }

        $insights[] = [
            'title' => 'Driver window',
            'value' => Duration::humanize((int) $filters['driving_minutes']),
            'note' => 'Current remaining legal drive time',
        ];

        return $insights;
    }
}
