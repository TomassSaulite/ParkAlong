<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About ParkAlong</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        :root {
            --bg: #f4efe4;
            --panel: rgba(255, 250, 240, 0.95);
            --line: rgba(19, 36, 36, 0.1);
            --ink: #102127;
            --muted: #5b6f73;
            --accent: #0d7c66;
            --accent-3: #123f63;
            --shadow: 0 24px 80px rgba(16, 33, 39, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Instrument Sans", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(13, 124, 102, 0.16), transparent 28%),
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.14), transparent 24%),
                linear-gradient(180deg, #f4efe4 0%, #e8efe8 100%);
        }

        h1, h2, h3 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
        }

        p {
            margin: 0;
            line-height: 1.65;
            color: var(--muted);
        }

        .shell {
            width: min(1080px, calc(100vw - 24px));
            margin: 0 auto;
            padding: 18px 0 36px;
        }

        .hero,
        .section {
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: var(--shadow);
        }

        .hero {
            padding: 24px;
            color: #f8faf8;
            background:
                linear-gradient(135deg, rgba(18, 63, 99, 0.96), rgba(13, 124, 102, 0.9)),
                #123f63;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .brand {
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
        }

        .nav-links,
        .stack,
        .source-list {
            display: grid;
            gap: 14px;
        }

        .nav-links {
            grid-auto-flow: column;
            justify-content: start;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            color: #f8faf8;
            text-decoration: none;
            font-weight: 600;
        }

        .hero h1 {
            margin-top: 18px;
            font-size: clamp(2.2rem, 5vw, 4rem);
            max-width: 10ch;
            line-height: 0.95;
        }

        .hero p {
            margin-top: 14px;
            max-width: 60ch;
            color: rgba(248, 250, 248, 0.9);
        }

        .grid {
            display: grid;
            gap: 18px;
            grid-template-columns: 1.05fr 0.95fr;
            margin-top: 18px;
        }

        .section {
            padding: 22px;
            background: var(--panel);
        }

        .section h2 {
            margin-bottom: 10px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(16, 33, 39, 0.06);
            font-size: 0.88rem;
            margin-bottom: 12px;
        }

        ul {
            margin: 0;
            padding-left: 18px;
            color: var(--muted);
            line-height: 1.7;
        }

        li + li {
            margin-top: 8px;
        }

        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.95em;
        }

        @media (max-width: 860px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                grid-auto-flow: row;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="hero">
        <div class="nav">
            <div class="brand">ParkAlong</div>
            <div class="nav-links">
                <a class="nav-link" href="{{ route('planner.index') }}">Back to planner</a>
                <a class="nav-link" href="{{ route('planner.index') }}#plan">Plan a route</a>
            </div>
        </div>

        <h1>How the app works</h1>
        <p>ParkAlong helps drivers and dispatchers find truck parking that fits the real remaining legal drive window, stays close to the route corridor, and still surfaces the things drivers actually care about: safety, showers, food, toilets, cost, and practical map links.</p>
    </section>

    <div class="grid">
        <section class="section">
            <span class="pill">Planning flow</span>
            <h2>What happens during a search</h2>
            <ul>
                <li>The driver enters origin, destination, drive time left, and whether the trip is for a single driver or a crew.</li>
                <li>If dispatch has already planned the exact route, the user can paste coordinates or GeoJSON so the search follows that exact corridor.</li>
                <li>The app builds the route, searches for truck parking along that corridor, and ranks options by reachability, detour distance, safety, amenities, and fit to the remaining drive window.</li>
                <li>The shortlist shows a top recommendation first, then a cleaner list of other options with map actions and exact coordinates.</li>
            </ul>
        </section>

        <section class="section">
            <span class="pill">Frameworks and tech</span>
            <h2>What the prototype uses</h2>
            <ul>
                <li><code>Laravel</code> and <code>PHP</code> for routing, validation, services, and the import command.</li>
                <li><code>Blade</code> templates for the UI and lightweight client-side JavaScript for autocomplete, copy actions, and dynamic form behavior.</li>
                <li><code>Leaflet</code> for the route and parking map.</li>
                <li><code>Laravel HTTP client</code> and caching for external lookups and faster repeated searches.</li>
            </ul>
        </section>

        <section class="section">
            <span class="pill">Data sources</span>
            <h2>Where the information comes from</h2>
            <ul>
                <li><code>Nominatim</code> is used for town and city search suggestions plus geocoding of origin and destination.</li>
                <li><code>OpenRouteService</code> is used for truck-aware routing when an API key is configured.</li>
                <li><code>OSRM</code> is the routing fallback when truck-specific routing is not available.</li>
                <li><code>Overpass / OpenStreetMap</code> is used for live truck parking discovery along the route corridor.</li>
                <li>A bundled Europe-wide fallback dataset keeps the app usable even when live public sources are incomplete or rate-limited.</li>
            </ul>
        </section>

        <section class="section">
            <span class="pill">Why this matters</span>
            <h2>Social good and commercial value</h2>
            <ul>
                <li>Safer parking choices can reduce stress, fatigue, and unsafe end-of-shift scrambling for drivers.</li>
                <li>Route-aware stop planning can help fleets reduce detours, empty time, and poor parking decisions.</li>
                <li>The product can grow into a fleet planning and driver support tool with better parking coverage, occupancy, and dispatch integrations.</li>
            </ul>
        </section>
    </div>
</div>
</body>
</html>
