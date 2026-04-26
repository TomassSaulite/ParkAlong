<?php

namespace App\Http\Controllers;

use App\Services\LocationSearchService;
use App\Services\TripPlanningService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class TruckStopPlannerController extends Controller
{
    public function index(): View
    {
        return view('planner', [
            'form' => $this->defaults(),
            'result' => null,
        ]);
    }

    public function about(): View
    {
        return view('about');
    }

    public function plan(Request $request, TripPlanningService $planner): View
    {
        $validated = $request->validate([
            'origin' => ['required', 'string', 'max:120'],
            'destination' => ['required', 'string', 'max:120'],
            'operation_mode' => ['required', Rule::in(['single', 'crew'])],
            'driving_hours' => ['required', 'integer', 'min:0', 'max:21'],
            'driving_minutes_part' => ['required', 'integer', 'min:0', 'max:59'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'parking_cost' => ['required', 'in:any,free,paid'],
            'safety_min' => ['required', 'integer', 'min:1', 'max:5'],
            'ranking_focus' => ['required', Rule::in(['balanced', 'max_drive', 'min_detour', 'max_safety'])],
            'preplanned_route' => ['nullable', 'string', 'max:50000'],
            'needs_shower' => ['nullable', 'boolean'],
            'needs_food' => ['nullable', 'boolean'],
            'needs_toilets' => ['nullable', 'boolean'],
            'needs_lighting' => ['nullable', 'boolean'],
            'needs_security' => ['nullable', 'boolean'],
        ]);

        $drivingMinutes = $this->calculateDrivingMinutes(
            (int) $validated['driving_hours'],
            (int) $validated['driving_minutes_part'],
        );

        $maxDrivingMinutes = $this->maxDrivingMinutesForOperation($validated['operation_mode']);

        if ($drivingMinutes < 30 || $drivingMinutes > $maxDrivingMinutes) {
            return back()
                ->withErrors(['driving_hours' => 'Driving time left exceeds the selected driving mode limit.'])
                ->withInput();
        }

        $form = array_merge($this->defaults(), $validated, [
            'driving_minutes' => $drivingMinutes,
            'needs_shower' => $request->boolean('needs_shower'),
            'needs_food' => $request->boolean('needs_food'),
            'needs_toilets' => $request->boolean('needs_toilets'),
            'needs_lighting' => $request->boolean('needs_lighting'),
            'needs_security' => $request->boolean('needs_security'),
            'preplanned_route' => trim((string) ($validated['preplanned_route'] ?? '')),
        ]);

        try {
            $result = $planner->buildPlan($form);
        } catch (Throwable $exception) {
            report($exception);

            return view('planner', [
                'form' => $form,
                'result' => [
                    'route' => null,
                    'recommendations' => [],
                    'summary' => [
                        'parking_count' => 0,
                        'candidate_count' => 0,
                        'dataset_count' => 0,
                        'reachable_count' => 0,
                    ],
                    'warnings' => [
                        'We could not build the trip with live data right now. Check the route inputs or switch to a different origin and destination for the demo.',
                    ],
                    'error' => $exception->getMessage(),
                ],
            ]);
        }

        return view('planner', [
            'form' => $form,
            'result' => $result,
        ]);
    }

    public function suggestLocations(Request $request, LocationSearchService $locationSearchService): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'suggestions' => $locationSearchService->suggest($query),
        ]);
    }

    private function defaults(): array
    {
        return [
            'origin' => 'Rotterdam',
            'destination' => 'Berlin',
            'operation_mode' => 'single',
            'driving_hours' => 4,
            'driving_minutes_part' => 30,
            'driving_minutes' => 270,
            'buffer_minutes' => 15,
            'parking_cost' => 'any',
            'safety_min' => 3,
            'ranking_focus' => 'balanced',
            'preplanned_route' => '',
            'needs_shower' => true,
            'needs_food' => true,
            'needs_toilets' => true,
            'needs_lighting' => false,
            'needs_security' => false,
        ];
    }

    private function calculateDrivingMinutes(int $hours, int $minutes): int
    {
        return ($hours * 60) + $minutes;
    }

    private function maxDrivingMinutesForOperation(string $operationMode): int
    {
        return match ($operationMode) {
            'crew' => 21 * 60,
            default => 10 * 60,
        };
    }
}
