<?php

use App\Services\EuropeanTruckParkingImportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('parkalong:import-european-dataset {source} {--output=storage/app/private/data/europe-truck-parking.json}', function (string $source, EuropeanTruckParkingImportService $importer) {
    $summary = $importer->importFromCsv(
        sourcePath: $source,
        outputPath: base_path((string) $this->option('output')),
    );

    $this->info("Imported {$summary['imported_count']} parkings into {$summary['output_path']}");
    $this->line("Countries covered: {$summary['country_count']}");
})->purpose('Import a Europe-wide truck parking CSV into the ParkAlong fallback dataset');
