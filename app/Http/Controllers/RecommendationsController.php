<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivitiesRequest;
use App\Services\ActivitiesService;
use App\Services\WeatherService;

class RecommendationsController extends Controller {
    public function __construct(
        private WeatherService $weather,
        private ActivitiesService $activities,
    ) {}

    public function index(ActivitiesRequest $req) {
        $lat = $req->float('lat');
        $lon = $req->float('lon');
        $wx = $this->weather->current($lat, $lon);
        $pois = $this->activities->nearby($lat, $lon, $req->integer('radius', 3000), $req->input('type', 'all'));
        $scored = collect($pois)->map(fn ($p) => $this->score($p, $wx, $req->integer('radius', 3000)))->sortByDesc('score')->values();

        return response()->json(['items' => $scored]);
    }

    private function score(array $poi, array $wx, int $maxR): array {
        $distance = $poi['distanceM'] ?? 0;
        $proximity = max(0, min(1, ($maxR - $distance) / max($maxR, 1)));
        $t = $wx['tempC'] ?? null;
        $precip = $wx['precipProb'] ?? 0;
        $mm = $wx['precipMm'] ?? 0;
        $wind = $wx['windKph'] ?? 0;
        $weatherBoost = 0;
        if ($precip > 40 || $mm > 0.2) {
            $weatherBoost += ($poi['indoor'] ?? false) ? 0.5 : -0.3;
        }
        if ($wind > 35) {
            $weatherBoost += ($poi['indoor'] ?? false) ? 0.3 : -0.1;
        }
        if ($t !== null && $t >= 15 && $t <= 25) {
            $weatherBoost += ($poi['indoor'] ?? false) ? 0.0 : 0.4;
        }

        return $poi + ['score' => round($proximity + $weatherBoost, 3), 'factors' => compact('proximity', 't', 'precip', 'mm', 'wind')];
    }
}
