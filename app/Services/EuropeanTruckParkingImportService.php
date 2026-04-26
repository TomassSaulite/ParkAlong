<?php

namespace App\Services;

use App\Support\CountryNames;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use SplFileObject;

class EuropeanTruckParkingImportService
{
    public function __construct(private FallbackParkingRepository $fallbackParkingRepository)
    {
    }

    public function importFromCsv(string $sourcePath, string $outputPath): array
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException("Source file not found: {$sourcePath}");
        }

        $rows = $this->readRows($sourcePath);
        $curated = collect($this->fallbackParkingRepository->all())
            ->filter(fn (array $parking) => str_starts_with((string) ($parking['id'] ?? ''), 'fallback-'))
            ->map(function (array $parking) {
                $parking['source'] = 'curated fallback';

                return $parking;
            });

        $imported = collect($rows)
            ->map(fn (array $row) => $this->mapRow($row))
            ->filter()
            ->values();

        $merged = $curated
            ->concat($imported)
            ->unique(fn (array $parking) => strtolower(($parking['name'] ?? 'parking').'|'.round($parking['lat'], 3).'|'.round($parking['lon'], 3)))
            ->sortBy([
                ['country', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        file_put_contents(
            $outputPath,
            json_encode($merged->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        Cache::forget('fallback-parkings:v1');

        return [
            'imported_count' => $merged->count(),
            'country_count' => $merged->pluck('country')->filter()->unique()->count(),
            'output_path' => $outputPath,
        ];
    }

    private function readRows(string $sourcePath): array
    {
        $file = new SplFileObject($sourcePath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(';');

        $headers = null;
        $rows = [];

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);
                continue;
            }

            $data = [];

            foreach ($headers as $index => $header) {
                if ($header === null) {
                    continue;
                }

                $data[$header] = $row[$index] ?? null;
            }

            if (($data['lat'] ?? null) === null || ($data['lon'] ?? null) === null) {
                continue;
            }

            $rows[] = $data;
        }

        return $rows;
    }

    private function normalizeHeaders(array $row): array
    {
        return collect($row)->map(function ($header, int $index) {
            $header = trim((string) $header);

            if ($index === 0 && $header === '') {
                return 'row_id';
            }

            return $header !== '' ? $header : null;
        })->all();
    }

    private function mapRow(array $row): ?array
    {
        $lat = isset($row['lat']) ? (float) $row['lat'] : null;
        $lon = isset($row['lon']) ? (float) $row['lon'] : null;

        if (! $lat || ! $lon) {
            return null;
        }

        $name = trim((string) ($row['name'] ?? 'Truck parking'));
        $category = strtolower($name);
        $confidence = strtolower((string) ($row['truckParkingConfidence'] ?? 'medium'));
        $countryCode = strtoupper((string) ($row['country'] ?? ''));
        $distanceCore = $this->toFloat($row['distance_TenTcore_km'] ?? null);
        $area = $this->toFloat($row['totalArea_m2'] ?? null) ?? 0;

        $amenities = [
            'showers' => str_contains($category, 'truck stop'),
            'food' => str_contains($category, 'fueling') || str_contains($category, 'truck stop'),
            'toilets' => str_contains($category, 'rest area') || str_contains($category, 'fueling') || str_contains($category, 'truck stop'),
            'lighting' => $confidence === 'high' || str_contains($category, 'truck stop'),
            'security' => $confidence === 'high' && (str_contains($category, 'truck stop') || $area >= 15000),
        ];

        $safetyScore = 54
            + ($confidence === 'high' ? 14 : 6)
            + (str_contains($category, 'truck stop') ? 10 : 0)
            + (str_contains($category, 'rest area') ? 6 : 0)
            + (($distanceCore !== null && $distanceCore <= 1.0) ? 5 : 0)
            + ($area >= 15000 ? 5 : 0);

        return [
            'id' => 'dataset-'.($row['row_id'] ?? md5(json_encode($row))),
            'name' => $name !== '' ? $name : 'Truck parking',
            'country' => CountryNames::fromAlpha2($countryCode) ?? $countryCode,
            'location' => '',
            'lat' => $lat,
            'lon' => $lon,
            'paid' => false,
            'amenities' => $amenities,
            'safety_score' => min(95, $safetyScore),
            'capacity_hgv' => $area > 0 ? max(8, min(240, (int) round($area / 220))) : null,
            'source' => 'fraunhofer europe dataset',
            'category' => $row['name'] ?? null,
            'confidence' => $row['truckParkingConfidence'] ?? null,
            'area_m2' => $area ?: null,
            'distance_tent_core_km' => $distanceCore,
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : (float) $value;
    }
}
