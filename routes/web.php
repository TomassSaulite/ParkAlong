<?php

use App\Http\Controllers\ParkAlongPlannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ParkAlongPlannerController::class, 'index'])->name('planner.index');
Route::get('/about', [ParkAlongPlannerController::class, 'about'])->name('planner.about');
Route::post('/plan', [ParkAlongPlannerController::class, 'plan'])->name('planner.plan');
Route::get('/locations/suggestions', [ParkAlongPlannerController::class, 'suggestLocations'])->name('planner.suggestions');
