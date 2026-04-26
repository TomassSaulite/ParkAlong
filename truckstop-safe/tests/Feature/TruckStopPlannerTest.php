<?php

namespace Tests\Feature;

use App\Services\LocationSearchService;
use App\Services\TripPlanningService;
use Mockery;
use Tests\TestCase;

class TruckStopPlannerTest extends TestCase
{
    public function test_home_page_renders(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('TruckStop Safe');
        $response->assertSee('Find the best truck stops');
    }

    public function test_location_suggestions_endpoint_returns_service_data(): void
    {
        $mock = Mockery::mock(LocationSearchService::class);
        $mock->shouldReceive('suggest')
            ->once()
            ->with('Rot')
            ->andReturn([
                ['value' => 'Rotterdam, Netherlands', 'label' => 'Rotterdam', 'subtitle' => 'Netherlands'],
            ]);

        $this->app->instance(LocationSearchService::class, $mock);

        $response = $this->getJson('/locations/suggestions?q=Rot');

        $response->assertOk();
        $response->assertJsonPath('suggestions.0.value', 'Rotterdam, Netherlands');
    }

    public function test_about_page_renders(): void
    {
        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('How the app works');
        $response->assertSee('Back to planner');
    }

    public function test_plan_page_renders_results_from_planner_service(): void
    {
        $mock = Mockery::mock(TripPlanningService::class);
        $mock->shouldReceive('buildPlan')
            ->once()
            ->andReturn([
                'route' => [
                    'distance_km' => 611.2,
                    'duration_minutes' => 396,
                    'source' => 'OSRM driving',
                    'warnings' => [],
                    'bounds' => ['south' => 51.0, 'west' => 4.0, 'north' => 52.0, 'east' => 13.0],
                    'origin' => ['label' => 'Rotterdam', 'lat' => 51.92, 'lon' => 4.47],
                    'destination' => ['label' => 'Berlin', 'lat' => 52.52, 'lon' => 13.40],
                    'geometry' => [
                        ['lat' => 51.92, 'lon' => 4.47],
                        ['lat' => 52.10, 'lon' => 6.90],
                        ['lat' => 52.52, 'lon' => 13.40],
                    ],
                ],
                'recommendations' => [
                    [
                        'id' => 'fallback-hanover',
                        'name' => 'Autohof Lehrte',
                        'location' => 'Lehrte',
                        'country' => 'Germany',
                        'lat' => 52.37,
                        'lon' => 9.96,
                        'score' => 83.4,
                        'fit_label' => 'Best end-of-shift timing',
                        'reasons' => ['Uses almost all remaining legal drive time'],
                        'paid' => true,
                        'safety_stars' => 5,
                        'safety_score' => 90,
                        'source' => 'curated fallback',
                        'focus_label' => 'Balanced',
                        'eta_minutes' => 265,
                        'remaining_drive_minutes' => 5,
                        'distance_to_route_km' => 1.9,
                        'distance_along_route_km' => 410.1,
                        'is_reachable' => true,
                        'amenities' => [
                            'showers' => true,
                            'food' => true,
                            'toilets' => true,
                            'lighting' => true,
                            'security' => true,
                        ],
                    ],
                ],
                'summary' => [
                    'parking_count' => 1,
                    'candidate_count' => 6,
                    'dataset_count' => 19731,
                    'reachable_count' => 1,
                    'route_source' => 'OSRM driving',
                    'parking_source' => 'Bundled Europe dataset',
                    'available_drive_label' => '4h 30m',
                    'buffer_label' => '15m',
                    'operation_mode_label' => 'Single driver',
                    'using_preplanned_route' => false,
                ],
                'insights' => [
                    ['title' => 'Best match', 'value' => 'Autohof Lehrte', 'note' => 'Best end-of-shift timing'],
                ],
                'warnings' => [],
            ]);

        $this->app->instance(TripPlanningService::class, $mock);

        $response = $this->post('/plan', [
            'origin' => 'Rotterdam',
            'destination' => 'Berlin',
            'operation_mode' => 'single',
            'driving_hours' => 4,
            'driving_minutes_part' => 30,
            'buffer_minutes' => 15,
            'parking_cost' => 'any',
            'safety_min' => 3,
            'ranking_focus' => 'balanced',
            'preplanned_route' => '',
            'needs_shower' => 1,
            'needs_food' => 1,
            'needs_toilets' => 1,
        ]);

        $response->assertOk();
        $response->assertSee('Autohof Lehrte');
        $response->assertSee('Best end-of-shift timing');
        $response->assertSee('Uses almost all remaining legal drive time');
        $response->assertSee('52.37000, 9.96000');
        $response->assertSee('Open in Google Maps');
    }

    public function test_plan_accepts_crew_mode_and_preplanned_route(): void
    {
        $mock = Mockery::mock(TripPlanningService::class);
        $mock->shouldReceive('buildPlan')
            ->once()
            ->withArgs(function (array $form) {
                return $form['operation_mode'] === 'crew'
                    && $form['driving_minutes'] === 1260
                    && str_contains($form['preplanned_route'], '52.5200,13.4050');
            })
            ->andReturn([
                'route' => null,
                'recommendations' => [],
                'summary' => [
                    'parking_count' => 0,
                    'candidate_count' => 0,
                    'dataset_count' => 19731,
                    'reachable_count' => 0,
                    'route_source' => 'Preplanned route',
                    'parking_source' => 'Bundled Europe dataset',
                    'available_drive_label' => '21h',
                    'buffer_label' => '15m',
                    'operation_mode_label' => 'Crew',
                    'using_preplanned_route' => true,
                ],
                'insights' => [],
                'warnings' => [],
            ]);

        $this->app->instance(TripPlanningService::class, $mock);

        $response = $this->post('/plan', [
            'origin' => 'Berlin',
            'destination' => 'Poznan',
            'operation_mode' => 'crew',
            'driving_hours' => 21,
            'driving_minutes_part' => 0,
            'buffer_minutes' => 15,
            'parking_cost' => 'any',
            'safety_min' => 3,
            'ranking_focus' => 'balanced',
            'preplanned_route' => "52.5200,13.4050\n52.2000,14.6000",
        ]);

        $response->assertOk();
    }
}
