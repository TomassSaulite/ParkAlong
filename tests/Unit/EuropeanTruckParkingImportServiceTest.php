<?php

namespace Tests\Unit;

use App\Services\EuropeanTruckParkingImportService;
use App\Services\FallbackParkingRepository;
use Tests\TestCase;

class EuropeanTruckParkingImportServiceTest extends TestCase
{
    public function test_import_converts_semicolon_csv_into_normalized_dataset(): void
    {
        $sourcePath = storage_path('framework/testing-import-source.csv');
        $outputPath = storage_path('framework/testing-import-output.json');

        file_put_contents($sourcePath, <<<CSV
;name;lat;lon;totalArea_m2;truckParkingConfidence;country;clc;distance_TenTcore_km;distance_TenTcomp_km;truckFlowCount_nearest;truckFlowCount_max
0;Fueling / Truck Stop;48.1577;15.9414;6034;High;AT;243;0.0733;;4.28472;4.28472
1;Parking;48.4582;14.0313;5815;Medium;AT;242;;;;
CSV);

        $service = new EuropeanTruckParkingImportService(new FallbackParkingRepository());
        $summary = $service->importFromCsv($sourcePath, $outputPath);
        $data = json_decode((string) file_get_contents($outputPath), true);

        $this->assertGreaterThan(2, $summary['imported_count']);
        $this->assertIsArray($data);
        $this->assertContains('Austria', array_column($data, 'country'));
        $this->assertTrue(collect($data)->contains(fn (array $parking) => ($parking['source'] ?? null) === 'fraunhofer europe dataset'));
    }
}
