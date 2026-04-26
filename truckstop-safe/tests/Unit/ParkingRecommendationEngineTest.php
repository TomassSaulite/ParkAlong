<?php

namespace Tests\Unit;

use App\Services\ParkingRecommendationEngine;
use Tests\TestCase;

class ParkingRecommendationEngineTest extends TestCase
{
    public function test_rank_prefers_reachable_route_aligned_parking(): void
    {
        $engine = new ParkingRecommendationEngine();

        $route = [
            'distance_km' => 300,
            'duration_minutes' => 240,
            'geometry' => [
                ['lat' => 51.0, 'lon' => 4.0],
                ['lat' => 51.3, 'lon' => 5.0],
                ['lat' => 51.7, 'lon' => 7.0],
            ],
            'cumulative_km' => [0.0, 90.0, 300.0],
        ];

        $filters = [
            'driving_minutes' => 180,
            'buffer_minutes' => 15,
            'parking_cost' => 'any',
            'safety_min' => 1,
            'needs_shower' => true,
            'needs_food' => true,
            'needs_toilets' => true,
            'needs_lighting' => false,
            'needs_security' => false,
        ];

        $ranked = $engine->rank([
            [
                'id' => 'best',
                'name' => 'Best',
                'lat' => 51.31,
                'lon' => 5.02,
                'paid' => true,
                'amenities' => [
                    'showers' => true,
                    'food' => true,
                    'toilets' => true,
                    'lighting' => true,
                    'security' => true,
                ],
                'safety_score' => 90,
                'source' => 'test',
            ],
            [
                'id' => 'early',
                'name' => 'Early',
                'lat' => 51.02,
                'lon' => 4.02,
                'paid' => false,
                'amenities' => [
                    'showers' => true,
                    'food' => true,
                    'toilets' => true,
                    'lighting' => false,
                    'security' => false,
                ],
                'safety_score' => 68,
                'source' => 'test',
            ],
        ], $route, $filters);

        $this->assertSame('Best', $ranked[0]['name']);
        $this->assertTrue($ranked[0]['is_reachable']);
    }
}
