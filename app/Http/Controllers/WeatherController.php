<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeatherRequest;
use App\Services\WeatherService;

class WeatherController extends Controller {
    public function __construct(private WeatherService $weather) {}

    public function show(WeatherRequest $req) {
        $data = $this->weather->current($req->float('lat'), $req->float('lon'));

        return response()->json($data);
    }
}
