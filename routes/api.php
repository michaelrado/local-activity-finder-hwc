<?php

use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\RecommendationsController;
use App\Http\Controllers\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/weather', [WeatherController::class, 'show']);
Route::get('/activities', [ActivitiesController::class, 'index']);
Route::get('/recommendations', [RecommendationsController::class, 'index']);

Route::get('/geocode/search', [GeoController::class, 'search']);
Route::get('/geocode/reverse', [GeoController::class, 'reverse']);
