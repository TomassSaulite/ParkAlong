<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TruckStop Safe</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        :root {
            --bg: #f4efe4;
            --panel: rgba(255, 250, 240, 0.95);
            --panel-strong: #fff9ef;
            --line: rgba(19, 36, 36, 0.1);
            --ink: #102127;
            --muted: #5b6f73;
            --accent: #0d7c66;
            --accent-2: #f59e0b;
            --accent-3: #123f63;
            --good: #1f8f6d;
            --warn: #e57a32;
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

        h1, h2, h3, h4, summary {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
        }

        p { margin: 0; }

        .shell {
            width: min(1180px, calc(100vw - 24px));
            margin: 0 auto;
            padding: 18px 0 36px;
        }

        .hero,
        .planner,
        .metric,
        .top-pick,
        .map-card,
        .list-card,
        .empty-state {
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: var(--shadow);
        }

        .hero {
            overflow: hidden;
            position: relative;
            padding: 24px;
            color: #f8faf8;
            background:
                linear-gradient(135deg, rgba(18, 63, 99, 0.96), rgba(13, 124, 102, 0.9)),
                #123f63;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -70px;
            bottom: -70px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.18), transparent 70%);
        }

        .eyebrow,
        .chip,
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.84rem;
        }

        .eyebrow { background: rgba(255,255,255,0.12); }
        .chip, .pill { background: rgba(16, 33, 39, 0.06); color: var(--ink); }
        .chip.good { background: rgba(31,143,109,0.12); color: var(--good); }
        .chip.warn { background: rgba(229,122,50,0.12); color: var(--warn); }

        .hero-nav,
        .hero-actions,
        .field-label-line,
        .route-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-nav {
            justify-content: space-between;
        }

        .brand {
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .secondary-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #f8faf8;
            background: rgba(255,255,255,0.1);
        }

        .secondary-link.panel-link {
            border-color: rgba(16, 33, 39, 0.1);
            color: var(--ink);
            background: rgba(255,255,255,0.72);
        }

        .hero-grid,
        .planner-grid,
        .field-grid,
        .overview-grid,
        .results-layout,
        .metrics-grid,
        .stop-stats {
            display: grid;
            gap: 14px;
        }

        .hero-grid {
            grid-template-columns: 1.2fr 0.8fr;
            margin-top: 16px;
            align-items: end;
        }

        .hero h1 {
            margin-top: 14px;
            font-size: clamp(2.3rem, 5vw, 4.6rem);
            line-height: 0.95;
            max-width: 8ch;
        }

        .hero p {
            margin-top: 14px;
            max-width: 62ch;
            color: rgba(248, 250, 248, 0.92);
            line-height: 1.6;
        }

        .hero-side {
            display: grid;
            gap: 12px;
        }

        .hero-note {
            padding: 18px;
            border-radius: 20px;
            background: rgba(255,255,255,0.12);
        }

        .planner {
            margin-top: 18px;
            padding: 20px;
            background: var(--panel);
            backdrop-filter: blur(16px);
        }

        .planner-grid {
            grid-template-columns: 1.15fr 0.85fr;
            align-items: start;
        }

        .planner-copy p,
        .empty-state p,
        .top-pick p,
        .list-card p,
        .metric p {
            color: var(--muted);
            line-height: 1.55;
        }

        .planner-copy p {
            margin-top: 8px;
        }

        .field-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .field-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }

        label {
            display: grid;
            gap: 8px;
            font-size: 0.94rem;
            font-weight: 600;
        }

        .field-label-line {
            justify-content: flex-start;
        }

        .field-hint {
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 500;
        }

        .info-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1px solid rgba(16, 33, 39, 0.16);
            background: rgba(255,255,255,0.72);
            color: var(--accent-3);
            font-size: 0.78rem;
            cursor: help;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid rgba(16, 33, 39, 0.14);
            background: rgba(255, 255, 255, 0.88);
            border-radius: 16px;
            padding: 13px 14px;
            font: inherit;
            color: var(--ink);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .autocomplete {
            position: relative;
        }

        .suggestions {
            position: absolute;
            inset: calc(100% + 8px) 0 auto;
            z-index: 30;
            display: none;
            padding: 8px;
            border-radius: 18px;
            background: rgba(255, 250, 240, 0.98);
            border: 1px solid rgba(16, 33, 39, 0.12);
            box-shadow: 0 20px 50px rgba(16, 33, 39, 0.16);
        }

        .suggestions.visible {
            display: grid;
            gap: 6px;
        }

        .suggestion-item {
            width: 100%;
            border: 0;
            margin: 0;
            border-radius: 14px;
            padding: 12px 13px;
            background: rgba(16, 33, 39, 0.04);
            color: var(--ink);
            text-align: left;
            cursor: pointer;
        }

        .suggestion-item strong,
        .suggestion-item small {
            display: block;
        }

        .suggestion-item small {
            margin-top: 3px;
            color: var(--muted);
        }

        .suggestion-item.is-active,
        .suggestion-item:hover {
            background: rgba(13, 124, 102, 0.1);
        }

        .mode-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mode-option input {
            display: none;
        }

        .mode-box {
            display: block;
            padding: 14px;
            border-radius: 18px;
            border: 1px solid rgba(16, 33, 39, 0.1);
            background: rgba(255, 255, 255, 0.72);
            cursor: pointer;
        }

        .mode-box strong,
        .mode-box small {
            display: block;
        }

        .mode-box small {
            margin-top: 4px;
            color: var(--muted);
        }

        .mode-option input:checked + .mode-box {
            border-color: rgba(13, 124, 102, 0.35);
            background: rgba(13, 124, 102, 0.08);
        }

        details.advanced {
            border-radius: 18px;
            border: 1px solid rgba(16, 33, 39, 0.1);
            background: rgba(255,255,255,0.62);
            padding: 14px;
        }

        details.advanced[open] {
            background: rgba(255,255,255,0.76);
        }

        details.advanced summary {
            cursor: pointer;
            list-style: none;
            font-size: 1rem;
        }

        details.advanced summary::-webkit-details-marker {
            display: none;
        }

        .checkbox-grid {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(16, 33, 39, 0.08);
            font-weight: 500;
        }

        .check input {
            width: 18px;
            height: 18px;
            margin: 0;
        }

        .primary-button {
            width: 100%;
            border: 0;
            border-radius: 18px;
            padding: 15px 18px;
            background: linear-gradient(135deg, var(--accent), #1d9f80);
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(13,124,102,0.26);
        }

        .subtle {
            font-size: 0.88rem;
            color: var(--muted);
        }

        .errors,
        .warning {
            margin-top: 14px;
            padding: 13px 15px;
            border-radius: 16px;
        }

        .errors {
            border: 1px solid rgba(171,51,51,0.18);
            background: rgba(255,238,238,0.92);
            color: #8d3030;
        }

        .warning {
            border: 1px solid rgba(229,122,50,0.22);
            background: rgba(255,242,230,0.88);
            color: #8a4a14;
        }

        .results-layout {
            margin-top: 18px;
            grid-template-columns: 1fr;
        }

        .overview-grid {
            grid-template-columns: 1.1fr 0.9fr;
            align-items: start;
        }

        .route-layout {
            display: grid;
            gap: 14px;
            grid-template-columns: 1.28fr 0.72fr;
            align-items: start;
        }

        .top-pick,
        .map-card,
        .metric,
        .list-card,
        .empty-state {
            background: var(--panel);
        }

        .top-pick,
        .metric,
        .list-card,
        .empty-state {
            padding: 18px;
        }

        .metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .metric strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1.7rem;
        }

        .top-pick {
            display: grid;
            gap: 14px;
        }

        .top-pick h2 {
            font-size: 1.5rem;
        }

        .top-meta,
        .stop-reasons,
        .stop-amenities,
        .top-flags,
        .location-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .location-block {
            display: grid;
            gap: 8px;
            padding: 14px;
            border-radius: 16px;
            background: rgba(16, 33, 39, 0.05);
        }

        .location-row {
            display: grid;
            gap: 4px;
        }

        .location-row strong {
            font-size: 0.92rem;
        }

        .action-link,
        .copy-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid rgba(16, 33, 39, 0.1);
            background: rgba(255,255,255,0.72);
            color: var(--ink);
            text-decoration: none;
            font: inherit;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .action-link:hover,
        .copy-button:hover {
            border-color: rgba(13, 124, 102, 0.28);
            background: rgba(13, 124, 102, 0.08);
        }

        .map-card {
            overflow: hidden;
            align-self: start;
            min-height: 100%;
        }

        #map {
            width: 100%;
            height: 460px;
        }

        .list-card {
            display: grid;
            gap: 16px;
        }

        .route-side {
            display: grid;
            gap: 14px;
        }

        .stop {
            display: grid;
            gap: 12px;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(16, 33, 39, 0.08);
            background: rgba(255,255,255,0.72);
            cursor: pointer;
        }

        .stop:hover {
            border-color: rgba(13, 124, 102, 0.24);
        }

        .stop-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
        }

        .stop-score {
            font-size: 1.55rem;
            color: var(--accent-3);
            white-space: nowrap;
        }

        .stop-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .stop-stat {
            padding: 12px;
            border-radius: 14px;
            background: rgba(16,33,39,0.05);
        }

        .stop-stat strong,
        .stop-stat small {
            display: block;
        }

        .stop-stat strong {
            margin-bottom: 4px;
        }

        .empty-state {
            margin-top: 18px;
        }

        @media (max-width: 980px) {
            .hero-grid,
            .planner-grid,
            .overview-grid,
            .metrics-grid,
            .mode-grid {
                grid-template-columns: 1fr;
            }

            .route-layout {
                grid-template-columns: 1fr;
            }

            .field-grid.two,
            .field-grid.three,
            .stop-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .shell {
                width: min(100vw - 16px, 100%);
                padding-top: 10px;
                padding-bottom: 22px;
            }

            .hero,
            .planner,
            .metric,
            .top-pick,
            .map-card,
            .list-card,
            .empty-state {
                border-radius: 20px;
            }

            .hero h1 {
                max-width: none;
                font-size: clamp(2rem, 9vw, 3.2rem);
            }

            #map {
                height: 340px;
            }

            .stop-head {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
@php
    $route = $result['route'] ?? null;
    $recommendations = $result['recommendations'] ?? [];
    $summary = $result['summary'] ?? [];
    $insights = $result['insights'] ?? [];
    $topPick = $recommendations[0] ?? null;
    $locationSummary = function (array $parking): string {
        $parts = array_filter([$parking['location'] ?? null, $parking['country'] ?? null]);

        return $parts ? implode(', ', $parts) : 'Address not available in source data';
    };
    $coordinateLabel = function (array $parking): string {
        return number_format((float) $parking['lat'], 5, '.', '').', '.number_format((float) $parking['lon'], 5, '.', '');
    };
    $mapsLink = function (array $parking): string {
        return 'https://www.google.com/maps?q='.(float) $parking['lat'].','.(float) $parking['lon'];
    };
    $osmLink = function (array $parking): string {
        return 'https://www.openstreetmap.org/?mlat='.(float) $parking['lat'].'&mlon='.(float) $parking['lon'].'#map=14/'.(float) $parking['lat'].'/'.(float) $parking['lon'];
    };
    $mapPayload = $route ? [
        'route' => $route['geometry'],
        'bounds' => $route['bounds'],
        'origin' => $route['origin'],
        'destination' => $route['destination'],
        'parkings' => collect($recommendations)->map(fn (array $parking) => [
            'id' => $parking['id'],
            'name' => $parking['name'],
            'lat' => $parking['lat'],
            'lon' => $parking['lon'],
            'score' => $parking['score'],
            'fit_label' => $parking['fit_label'],
            'coordinate_label' => $coordinateLabel($parking),
        ])->values(),
    ] : null;
@endphp
<div class="shell">
    <section class="hero">
        <div class="hero-nav">
            <div class="brand">TruckStop Safe</div>
            <div class="hero-actions">
                <a class="secondary-link" href="#plan">Planner</a>
                <a class="secondary-link" href="{{ route('planner.about') }}">About</a>
            </div>
        </div>
        <span class="eyebrow">Social good x viable logistics</span>
        <div class="hero-grid">
            <div>
                <h1>TruckStop Safe</h1>
                <p>Find truck parking that fits the actual legal drive window, follows the exact route when dispatch has already planned one, and still stays readable enough to use on a phone during a fast check.</p>
            </div>
            <div class="hero-side">
                <div class="hero-note">
                    <strong>{{ number_format((int) ($summary['dataset_count'] ?? 19731)) }} bundled parkings</strong>
                    <p class="subtle" style="margin-top:8px;color:rgba(248,250,248,0.9);">Fallback Europe-wide coverage plus live OpenStreetMap truck parking when public data is available.</p>
                </div>
                <div class="hero-note">
                    <strong>Crew-ready planning</strong>
                    <p class="subtle" style="margin-top:8px;color:rgba(248,250,248,0.9);">Single-driver trips support up to 10 hours, while crew operations can plan up to 21 hours.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="planner" id="plan">
        <div class="planner-grid">
            <div class="planner-copy">
                <h2>Plan the Shift</h2>
                <p>Keep the main choices visible, and tuck the heavier routing options into an advanced section. If dispatch already gave you a route, paste it and the parking search will follow that exact corridor instead of recalculating one.</p>
            </div>
            <div class="top-meta">
                <span class="pill">Operation: {{ $summary['operation_mode_label'] ?? 'Single driver' }}</span>
                <span class="pill">Drive window: {{ $summary['available_drive_label'] ?? \App\Support\Duration::humanize($form['driving_minutes']) }}</span>
                @if (($summary['using_preplanned_route'] ?? false) === true)
                    <span class="pill">Using preplanned route</span>
                @endif
                <a class="secondary-link panel-link" href="{{ route('planner.about') }}">How it works</a>
            </div>
        </div>

        <form method="POST" action="{{ route('planner.plan') }}" style="margin-top:16px;">
            @csrf

            <div class="field-grid two">
                <label>
                    Origin
                    <div class="autocomplete" data-autocomplete>
                        <input type="text" name="origin" value="{{ $form['origin'] }}" placeholder="Rotterdam" autocomplete="off" data-autocomplete-input>
                        <div class="suggestions" data-autocomplete-list></div>
                    </div>
                </label>

                <label>
                    Destination
                    <div class="autocomplete" data-autocomplete>
                        <input type="text" name="destination" value="{{ $form['destination'] }}" placeholder="Berlin" autocomplete="off" data-autocomplete-input>
                        <div class="suggestions" data-autocomplete-list></div>
                    </div>
                </label>
            </div>

            <div style="margin-top:14px;">
                <label style="margin-bottom:8px;">Driving operation</label>
                <div class="mode-grid">
                    <label class="mode-option">
                        <input type="radio" name="operation_mode" value="single" data-mode-max-hours="10" @checked($form['operation_mode'] === 'single')>
                        <span class="mode-box">
                            <strong>Single driver</strong>
                            <small>Up to 10h legal drive time</small>
                        </span>
                    </label>
                    <label class="mode-option">
                        <input type="radio" name="operation_mode" value="crew" data-mode-max-hours="21" @checked($form['operation_mode'] === 'crew')>
                        <span class="mode-box">
                            <strong>Crew</strong>
                            <small>Up to 21h total crew drive window</small>
                        </span>
                    </label>
                </div>
            </div>

            <div class="field-grid three" style="margin-top:14px;">
                <label>
                    Drive hours left
                    <select name="driving_hours" data-driving-hours>
                        @for ($hour = 0; $hour <= 21; $hour++)
                            <option value="{{ $hour }}" @selected((int) $form['driving_hours'] === $hour)>{{ $hour }}h</option>
                        @endfor
                    </select>
                </label>

                <label>
                    Drive minutes left
                    <select name="driving_minutes_part" data-driving-minutes>
                        @foreach ([0, 15, 30, 45] as $minuteOption)
                            <option value="{{ $minuteOption }}" @selected((int) $form['driving_minutes_part'] === $minuteOption)>{{ str_pad((string) $minuteOption, 2, '0', STR_PAD_LEFT) }}m</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="field-label-line">
                        <span>Safety buffer</span>
                        <button
                            type="button"
                            class="info-button"
                            title="Extra time held in reserve so the stop is not planned exactly at the legal limit. This helps absorb traffic, queues, or fuel stops."
                            aria-label="Safety buffer information"
                        >i</button>
                    </span>
                    <select name="buffer_minutes">
                        @foreach ([0, 10, 15, 20, 30, 45, 60] as $bufferOption)
                            <option value="{{ $bufferOption }}" @selected((int) $form['buffer_minutes'] === $bufferOption)>{{ $bufferOption }}m</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <details class="advanced" style="margin-top:14px;" @if(old('preplanned_route', $form['preplanned_route'])) open @endif>
                <summary>Advanced Route And Filters</summary>

                <div class="field-grid two" style="margin-top:14px;">
                    <label>
                        Ranking focus
                        <select name="ranking_focus">
                            <option value="balanced" @selected($form['ranking_focus'] === 'balanced')>Balanced</option>
                            <option value="max_drive" @selected($form['ranking_focus'] === 'max_drive')>Maximize driving time</option>
                            <option value="min_detour" @selected($form['ranking_focus'] === 'min_detour')>Minimize detours</option>
                            <option value="max_safety" @selected($form['ranking_focus'] === 'max_safety')>Highest safety</option>
                        </select>
                    </label>

                    <label>
                        Parking cost
                        <select name="parking_cost">
                            <option value="any" @selected($form['parking_cost'] === 'any')>Any</option>
                            <option value="free" @selected($form['parking_cost'] === 'free')>Free only</option>
                            <option value="paid" @selected($form['parking_cost'] === 'paid')>Paid only</option>
                        </select>
                    </label>
                </div>

                <div class="field-grid two" style="margin-top:14px;">
                    <label>
                        Minimum safety
                        <select name="safety_min">
                            @for ($stars = 1; $stars <= 5; $stars++)
                                <option value="{{ $stars }}" @selected((int) $form['safety_min'] === $stars)>{{ $stars }} / 5</option>
                            @endfor
                        </select>
                    </label>

                    <label>
                        Preplanned route
                        <textarea name="preplanned_route" placeholder="Paste one lat,lon pair per line or a GeoJSON LineString. If filled, this exact route is used for parking search.">{{ $form['preplanned_route'] }}</textarea>
                    </label>
                </div>

                <div class="checkbox-grid">
                    <label class="check"><input type="checkbox" name="needs_shower" value="1" @checked($form['needs_shower'])> Shower</label>
                    <label class="check"><input type="checkbox" name="needs_food" value="1" @checked($form['needs_food'])> Food</label>
                    <label class="check"><input type="checkbox" name="needs_toilets" value="1" @checked($form['needs_toilets'])> Toilets</label>
                    <label class="check"><input type="checkbox" name="needs_lighting" value="1" @checked($form['needs_lighting'])> Lighting</label>
                    <label class="check"><input type="checkbox" name="needs_security" value="1" @checked($form['needs_security'])> Security or surveillance</label>
                </div>
            </details>

            <div class="field-grid two" style="margin-top:14px;">
                <button type="submit" class="primary-button">Find the best truck stops</button>
                <div class="subtle" style="align-self:center;">
                    Tip: leave the preplanned route empty for normal routing. Fill it only when dispatch already gave the driver a fixed corridor.
                </div>
            </div>
        </form>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </section>

    @if (! empty($result['error']))
        <div class="warning" style="margin-top:14px;">{{ $result['error'] }}</div>
    @endif

    @if ($route && $topPick)
        <section class="results-layout">
            <div class="overview-grid">
                <article class="top-pick">
                    <div class="top-meta">
                        <span class="chip good">Top recommendation</span>
                        <span class="pill">{{ $summary['route_source'] ?? 'Unknown route source' }}</span>
                        <span class="pill">{{ $summary['operation_mode_label'] ?? 'Single driver' }}</span>
                    </div>

                    <div>
                        <h2>{{ $topPick['name'] }}</h2>
                        <p style="margin-top:8px;">{{ $topPick['fit_label'] }} with ETA {{ \App\Support\Duration::humanize((int) $topPick['eta_minutes']) }} and {{ \App\Support\Duration::humanize((int) $topPick['remaining_drive_minutes']) }} left after arrival.</p>
                    </div>

                    <div class="top-flags">
                        <span class="chip good">{{ $topPick['safety_stars'] }}/5 safety</span>
                        <span class="chip">{{ $topPick['distance_to_route_km'] }} km off route</span>
                        <span class="chip">{{ $topPick['paid'] ? 'Paid' : 'Free or unknown' }}</span>
                        @if (! empty($topPick['capacity_hgv']))
                            <span class="chip">~{{ $topPick['capacity_hgv'] }} HGV</span>
                        @endif
                    </div>

                    @if (! empty($topPick['reasons']))
                        <div class="stop-reasons">
                            @foreach ($topPick['reasons'] as $reason)
                                <span class="chip good">{{ $reason }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="stop-amenities">
                        @foreach ($topPick['amenities'] as $label => $available)
                            <span class="chip @if($available) good @else warn @endif">{{ ucfirst($label) }}: {{ $available ? 'yes' : 'no' }}</span>
                        @endforeach
                    </div>

                    <div class="location-block">
                        <div class="location-row">
                            <strong>Location</strong>
                            <span>{{ $locationSummary($topPick) }}</span>
                        </div>
                        <div class="location-row">
                            <strong>Coordinates</strong>
                            <span>{{ $coordinateLabel($topPick) }}</span>
                        </div>
                        <div class="location-actions">
                            <button type="button" class="copy-button" data-copy-text="{{ $coordinateLabel($topPick) }}">Copy coordinates</button>
                            <a class="action-link" href="{{ $mapsLink($topPick) }}" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
                            <a class="action-link" href="{{ $osmLink($topPick) }}" target="_blank" rel="noopener noreferrer">Open in OSM</a>
                        </div>
                    </div>
                </article>

                <div class="metrics-grid">
                    <article class="metric">
                        <strong>{{ $summary['available_drive_label'] ?? \App\Support\Duration::humanize($form['driving_minutes']) }}</strong>
                        <p>Current legal drive window</p>
                    </article>
                    <article class="metric">
                        <strong>{{ \App\Support\Duration::humanize((int) $route['duration_minutes']) }}</strong>
                        <p>Estimated route duration</p>
                    </article>
                    <article class="metric">
                        <strong>{{ number_format((float) $route['distance_km'], 0) }} km</strong>
                        <p>Route distance</p>
                    </article>
                    <article class="metric">
                        <strong>{{ $summary['candidate_count'] ?? 0 }}</strong>
                        <p>Parking candidates scanned on corridor</p>
                    </article>
                </div>
            </div>

            <div class="route-layout">
                <div class="map-card">
                    <div id="map"></div>
                </div>

                <div class="route-side">
                <div class="list-card">
                    <div>
                        <h3>Route Snapshot</h3>
                        <p style="margin-top:8px;">{{ $route['origin']['label'] }} to {{ $route['destination']['label'] }}</p>
                    </div>

                    <div class="route-meta">
                        <span class="pill">Parking source: {{ $summary['parking_source'] ?? 'Unknown' }}</span>
                        <span class="pill">Buffer: {{ $summary['buffer_label'] ?? \App\Support\Duration::humanize($form['buffer_minutes']) }}</span>
                        @if (($summary['using_preplanned_route'] ?? false) === true)
                            <span class="pill">Exact dispatched route</span>
                        @endif
                    </div>

                    @if (count($insights))
                        <div style="display:grid;gap:10px;">
                            @foreach (array_slice($insights, 0, 4) as $insight)
                                <div style="padding:12px;border-radius:14px;background:rgba(16,33,39,0.05);">
                                    <strong style="display:block;margin-bottom:4px;">{{ $insight['title'] }}</strong>
                                    <div>{{ $insight['value'] }}</div>
                                    <p class="subtle" style="margin-top:4px;">{{ $insight['note'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="list-card">
                    <div>
                        <h3>Using the shortlist</h3>
                        <p style="margin-top:8px;">The ranking favors stops that the driver can still legally reach, stay close to the corridor, and match the selected amenities and safety threshold.</p>
                    </div>

                    <div style="display:grid;gap:10px;">
                        <div style="padding:12px;border-radius:14px;background:rgba(16,33,39,0.05);">
                            <strong style="display:block;margin-bottom:4px;">Tap a stop to focus the map</strong>
                            <p class="subtle">Each result card highlights its marker so the driver can quickly understand the detour.</p>
                        </div>
                        <div style="padding:12px;border-radius:14px;background:rgba(16,33,39,0.05);">
                            <strong style="display:block;margin-bottom:4px;">Copy the exact coordinates</strong>
                            <p class="subtle">Useful when the stop has limited address data or the driver needs to share the location fast.</p>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="list-card">
                <div>
                    <h3>Other Recommended Stops</h3>
                    <p style="margin-top:8px;">Tap a stop to highlight it on the map. The list is intentionally calmer: timing, detour, safety, and reasons first; everything else stays visible without shouting.</p>
                </div>

                @foreach ($recommendations as $parking)
                    <article class="stop" data-stop-id="{{ $parking['id'] }}">
                        <div class="stop-head">
                            <div>
                                <div class="top-meta">
                                    <span class="chip @if($loop->first) good @endif">{{ $loop->first ? 'Top pick' : ($parking['focus_label'] ?? 'Recommended') }}</span>
                                    <span class="pill">{{ $parking['country'] ?: 'Europe corridor' }}</span>
                                </div>
                                <h4 style="margin-top:10px;">{{ $parking['name'] }}</h4>
                                <p style="margin-top:6px;">{{ $locationSummary($parking) }}</p>
                            </div>
                            <div class="stop-score">{{ $parking['score'] }}</div>
                        </div>

                        <div class="stop-stats">
                            <div class="stop-stat">
                                <strong>{{ \App\Support\Duration::humanize((int) $parking['eta_minutes']) }}</strong>
                                <small>ETA</small>
                            </div>
                            <div class="stop-stat">
                                <strong>{{ $parking['distance_to_route_km'] }} km</strong>
                                <small>Detour</small>
                            </div>
                            <div class="stop-stat">
                                <strong>{{ \App\Support\Duration::humanize((int) $parking['remaining_drive_minutes']) }}</strong>
                                <small>Time left after arrival</small>
                            </div>
                        </div>

                        @if (! empty($parking['reasons']))
                            <div class="stop-reasons">
                                @foreach ($parking['reasons'] as $reason)
                                    <span class="chip good">{{ $reason }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="stop-amenities">
                            <span class="chip">{{ $coordinateLabel($parking) }}</span>
                            <span class="chip">{{ $parking['safety_stars'] }}/5 safety</span>
                            <span class="chip">{{ $parking['paid'] ? 'Paid' : 'Free or unknown' }}</span>
                            @if (! empty($parking['capacity_hgv']))
                                <span class="chip">~{{ $parking['capacity_hgv'] }} HGV</span>
                            @endif
                            @foreach ($parking['amenities'] as $label => $available)
                                <span class="chip @if($available) good @else warn @endif">{{ ucfirst($label) }}: {{ $available ? 'yes' : 'no' }}</span>
                            @endforeach
                        </div>

                        <div class="location-actions">
                            <button type="button" class="copy-button" data-copy-text="{{ $coordinateLabel($parking) }}">Copy coordinates</button>
                            <a class="action-link" href="{{ $mapsLink($parking) }}" target="_blank" rel="noopener noreferrer">Google Maps</a>
                            <a class="action-link" href="{{ $osmLink($parking) }}" target="_blank" rel="noopener noreferrer">OSM</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @elseif($route)
        <div class="empty-state">
            <h2>No parkings matched every filter</h2>
            <p style="margin-top:10px;">Try lowering the safety threshold, switching the ranking focus to balanced, or removing one required amenity.</p>
        </div>
    @else
        <div class="empty-state">
            <h2>Route-aware parking planning for solo and crew operations</h2>
            <p style="margin-top:10px;">Use the planner above to search normally or paste a dispatched route so the app follows the exact corridor instead of recalculating one.</p>
        </div>
    @endif
</div>

@if ($mapPayload)
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const mapPayload = @json($mapPayload);
        const map = L.map('map', { scrollWheelZoom: false });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const route = mapPayload.route.map((point) => [point.lat, point.lon]);
        L.polyline(route, { color: '#0d7c66', weight: 5, opacity: 0.92 }).addTo(map);

        const markers = {};
        mapPayload.parkings.forEach((parking, index) => {
            const marker = L.circleMarker([parking.lat, parking.lon], {
                radius: index === 0 ? 9 : 7,
                color: index === 0 ? '#f59e0b' : '#123f63',
                fillColor: index === 0 ? '#f59e0b' : '#123f63',
                fillOpacity: 0.95,
                weight: 2,
            }).addTo(map);

            marker.bindPopup(`<strong>${parking.name}</strong><br>${parking.fit_label}<br>Score: ${parking.score}`);
            markers[parking.id] = marker;
        });

        L.marker([mapPayload.origin.lat, mapPayload.origin.lon]).addTo(map).bindPopup('Origin');
        L.marker([mapPayload.destination.lat, mapPayload.destination.lon]).addTo(map).bindPopup('Destination');
        map.fitBounds([
            [mapPayload.bounds.south, mapPayload.bounds.west],
            [mapPayload.bounds.north, mapPayload.bounds.east]
        ], { padding: [24, 24] });

        document.querySelectorAll('[data-stop-id]').forEach((card) => {
            card.addEventListener('click', () => {
                const marker = markers[card.dataset.stopId];

                if (!marker) {
                    return;
                }

                map.flyTo(marker.getLatLng(), 9, { duration: 0.8 });
                marker.openPopup();
            });
        });
    </script>
@endif

<script>
    const suggestionsUrl = @json(route('planner.suggestions'));
    const debounce = (callback, delay = 220) => {
        let timeoutId;

        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => callback(...args), delay);
        };
    };

    document.querySelectorAll('[data-autocomplete]').forEach((wrapper) => {
        const input = wrapper.querySelector('[data-autocomplete-input]');
        const list = wrapper.querySelector('[data-autocomplete-list]');
        let activeIndex = -1;
        let currentItems = [];

        const closeList = () => {
            list.classList.remove('visible');
            list.innerHTML = '';
            activeIndex = -1;
        };

        const renderSuggestions = (items) => {
            currentItems = items;
            activeIndex = -1;

            if (!items.length) {
                closeList();
                return;
            }

            list.innerHTML = items.map((item, index) => `
                <button type="button" class="suggestion-item" data-index="${index}" data-value="${item.value}">
                    <strong>${item.label}</strong>
                    <small>${item.subtitle ?? ''}</small>
                </button>
            `).join('');

            list.classList.add('visible');

            list.querySelectorAll('.suggestion-item').forEach((button) => {
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    input.value = button.dataset.value;
                    closeList();
                });
            });
        };

        const loadSuggestions = debounce(async () => {
            const query = input.value.trim();
            const response = await fetch(`${suggestionsUrl}?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                closeList();
                return;
            }

            const payload = await response.json();
            renderSuggestions(payload.suggestions ?? []);
        });

        input.addEventListener('focus', () => loadSuggestions());
        input.addEventListener('input', () => loadSuggestions());
        input.addEventListener('keydown', (event) => {
            if (!currentItems.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = (activeIndex + 1) % currentItems.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = activeIndex <= 0 ? currentItems.length - 1 : activeIndex - 1;
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                input.value = currentItems[activeIndex].value;
                closeList();
                return;
            } else if (event.key === 'Escape') {
                closeList();
                return;
            } else {
                return;
            }

            list.querySelectorAll('.suggestion-item').forEach((button, index) => {
                button.classList.toggle('is-active', index === activeIndex);
            });
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                closeList();
            }
        });
    });

    document.querySelectorAll('[data-copy-text]').forEach((button) => {
        button.addEventListener('click', async () => {
            const originalText = button.textContent;

            try {
                await navigator.clipboard.writeText(button.dataset.copyText);
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = originalText;
                }, 1400);
            } catch (error) {
                button.textContent = 'Copy failed';
                window.setTimeout(() => {
                    button.textContent = originalText;
                }, 1400);
            }
        });
    });

    const hoursSelect = document.querySelector('[data-driving-hours]');
    const minuteSelect = document.querySelector('[data-driving-minutes]');
    const modeInputs = Array.from(document.querySelectorAll('input[name="operation_mode"][data-mode-max-hours]'));

    const syncDriveHourOptions = () => {
        if (!hoursSelect || !modeInputs.length) {
            return;
        }

        const activeMode = modeInputs.find((input) => input.checked) ?? modeInputs[0];
        const maxHours = Number(activeMode.dataset.modeMaxHours ?? 10);
        const previousValue = Number(hoursSelect.value || 0);

        hoursSelect.innerHTML = Array.from({ length: maxHours + 1 }, (_, hour) => {
            const selected = hour === Math.min(previousValue, maxHours) ? 'selected' : '';
            return `<option value="${hour}" ${selected}>${hour}h</option>`;
        }).join('');

        if (Number(hoursSelect.value) === maxHours && Number(minuteSelect?.value || 0) > 0) {
            minuteSelect.value = '0';
        }
    };

    modeInputs.forEach((input) => {
        input.addEventListener('change', syncDriveHourOptions);
    });

    hoursSelect?.addEventListener('change', () => {
        const activeMode = modeInputs.find((input) => input.checked) ?? modeInputs[0];
        const maxHours = Number(activeMode?.dataset.modeMaxHours ?? 10);

        if (Number(hoursSelect.value) === maxHours && minuteSelect && Number(minuteSelect.value) > 0) {
            minuteSelect.value = '0';
        }
    });

    syncDriveHourOptions();
</script>
</body>
</html>
