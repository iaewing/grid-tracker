<?php

use App\Http\Controllers\PowerGridController;
use App\Http\Controllers\RegionObservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', PowerGridController::class)->name('home');
Route::get('/regions/{region}', [RegionObservationController::class, 'show'])->name('regions.show');
