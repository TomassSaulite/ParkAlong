# ParkAlong

[![CI](https://github.com/TomassSaulite/ParkAlong/actions/workflows/ci.yml/badge.svg)](https://github.com/TomassSaulite/ParkAlong/actions/workflows/ci.yml) [![Live demo](https://img.shields.io/badge/demo-live-2ea44f)](https://parkalong.free.laravel.cloud)

**[Open the live demo →](https://parkalong.free.laravel.cloud)**

ParkAlong is a Laravel prototype for European cargo drivers. It recommends truck parking along a live route based on remaining legal driving time, proximity to the active route, safety, cost, and amenities.

The product goal is simple: help drivers stop safely before they run out of time, while giving fleets a foundation for trip planning and driver wellbeing.

## How it works

1. Enter an origin, destination, available driving time, and required amenities.
2. Geocode the route and use truck-aware routing when an OpenRouteService key is available.
3. Discover truck parking through OpenStreetMap / Overpass.
4. Rank stops by reachability, time use, detour, safety, amenities, and the selected focus.
5. Fall back to bundled Europe-wide coverage if live parking lookup fails.

## Data and integrations

- OpenRouteService enables truck-aware `driving-hgv` route planning.
- Nominatim provides place-name geocoding.
- Overpass provides live OpenStreetMap parking data.
- A bundled dataset keeps the demo usable when live lookup is unavailable.

Without an OpenRouteService key, geocoding and parking discovery still work; routing falls back to OSRM driving.

## Local run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Then open `http://127.0.0.1:8000`.

## Demo walkthrough

For the quickest tour, open the live demo and try **Rotterdam → Berlin** with a few hours of driving time. Compare the balanced, lowest-detour, and highest-safety ranking focuses. The demo is designed to remain useful without API keys by falling back to bundled parking coverage.

## Useful demo routes

- `Rotterdam → Berlin`
- `Antwerp → Hanover`
- `Lille → Cologne`
- `Vienna → Bratislava`

## Bigger data import

```bash
php artisan parkalong:import-european-dataset /path/to/truckParkingLocationsEurope_MediumHigh_v03.csv
```

This converts the CSV into the JSON structure used by the app and merges it with the curated demo parkings.

## Tests

```bash
php artisan test
```

## Current MVP limits

- Live OSM amenity tags can be incomplete depending on the parking entry.
- Without `OPENROUTESERVICE_API_KEY`, the route is not fully truck-specific.
- Live parking quality depends on the underlying public data source.
