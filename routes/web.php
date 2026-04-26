<?php

use App\Http\Controllers\TruckStopPlannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TruckStopPlannerController::class, 'index'])->name('planner.index');
Route::get('/about', [TruckStopPlannerController::class, 'about'])->name('planner.about');
Route::post('/plan', [TruckStopPlannerController::class, 'plan'])->name('planner.plan');
Route::get('/locations/suggestions', [TruckStopPlannerController::class, 'suggestLocations'])->name('planner.suggestions');
