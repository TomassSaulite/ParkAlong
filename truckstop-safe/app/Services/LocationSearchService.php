<?php

namespace App\Services;

use Throwable;

class LocationSearchService
{
    public function __construct(private RoutePlanningService $routePlanningService)
    {
    }

    public function suggest(string $query): array
    {
        $query = trim($query);

        $fallback = collect($this->popularPlaces())
            ->filter(function (array $place) use ($query) {
                if ($query === '') {
                    return true;
                }

                return str_contains(strtolower($place['value']), strtolower($query));
            })
            ->take(6)
            ->values()
            ->all();

        if (mb_strlen($query) < 2) {
            return $fallback;
        }

        try {
            $live = $this->routePlanningService->suggestLocations($query);

            return $live !== [] ? $live : $fallback;
        } catch (Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }

    private function popularPlaces(): array
    {
        return [
            ['value' => 'Rotterdam, Netherlands', 'label' => 'Rotterdam', 'subtitle' => 'Netherlands'],
            ['value' => 'Antwerp, Belgium', 'label' => 'Antwerp', 'subtitle' => 'Belgium'],
            ['value' => 'Berlin, Germany', 'label' => 'Berlin', 'subtitle' => 'Germany'],
            ['value' => 'Hanover, Germany', 'label' => 'Hanover', 'subtitle' => 'Germany'],
            ['value' => 'Cologne, Germany', 'label' => 'Cologne', 'subtitle' => 'Germany'],
            ['value' => 'Lille, France', 'label' => 'Lille', 'subtitle' => 'France'],
            ['value' => 'Vienna, Austria', 'label' => 'Vienna', 'subtitle' => 'Austria'],
            ['value' => 'Bratislava, Slovakia', 'label' => 'Bratislava', 'subtitle' => 'Slovakia'],
            ['value' => 'Warsaw, Poland', 'label' => 'Warsaw', 'subtitle' => 'Poland'],
            ['value' => 'Kaunas, Lithuania', 'label' => 'Kaunas', 'subtitle' => 'Lithuania'],
        ];
    }
}
