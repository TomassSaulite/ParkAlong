# ParkAlong

ParkAlong is a Laravel hackathon prototype for European cargo drivers. It recommends truck parking along a live route based on:

- remaining legal driving time
- proximity to the active route
- safety level
- paid vs free preference
- amenities like showers, food, toilets, lighting, and security

The app now combines:

- live route-aware truck parking discovery through OpenStreetMap / Overpass
- bundled Europe-wide fallback coverage from a public truck parking dataset
- town and city autocomplete for faster trip setup

The product angle is simple: help drivers stop safely before they run out of time, while giving fleets a tool they could eventually use for trip planning and driver wellbeing.

## How It Works

1. The driver enters origin, destination, available driving hours and minutes, and required amenities.
2. The app geocodes the route, then tries truck-aware routing with OpenRouteService when an API key is present.
3. It looks up truck parking along the corridor using OpenStreetMap / Overpass data.
4. It ranks stops by:
   - whether the stop can be reached within the current drive window
   - how well it uses the remaining time
   - how close it is to the route
   - safety score
   - amenity match
   - selected ranking focus like balanced, lowest detour, highest safety, or maximum drive time
5. If a live parking lookup fails, the app falls back to a bundled Europe-wide dataset so the demo still works.

## Bigger Data Import

To bulk-load a larger Europe-wide fallback dataset:

```bash
php artisan parkalong:import-european-dataset /path/to/truckParkingLocationsEurope_MediumHigh_v03.csv
```

This command converts the CSV into the JSON structure used by the app and merges it with the curated demo parkings.

## API Support

The app is usable without keys, but it gets better when you add them.

- `OPENROUTESERVICE_API_KEY`
  Enables truck-aware `driving-hgv` route planning.
- `NOMINATIM_BASE_URL`
  Used for geocoding place names.
- `OVERPASS_API_URL`
  Used to fetch truck parking from OpenStreetMap.

Default behavior without an OpenRouteService key:

- geocoding still works
- routing falls back to OSRM driving
- parking still tries OpenStreetMap live data
- fallback parkings are always available for the demo

## Local Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Then open `http://127.0.0.1:8000`.

## Useful Demo Routes

- `Rotterdam -> Berlin`
- `Antwerp -> Hanover`
- `Lille -> Cologne`
- `Vienna -> Bratislava`

## Tests

```bash
php artisan test
```

## Submission Framing

Social good:

- reduces unsafe fatigue-related parking decisions
- improves driver wellbeing
- helps drivers find safer stops with needed facilities

Commercial viability:

- can become a fleet trip-planning subscription
- can support premium parking listings
- can sell dispatch dashboards and route analytics later

## Current MVP Limits

- live OSM amenity tags can be incomplete depending on the parking entry
- without `OPENROUTESERVICE_API_KEY`, the route is not fully truck-specific
- live parking amenity quality still depends on the underlying public data source
