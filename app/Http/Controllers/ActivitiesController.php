<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivitiesRequest;
use App\Services\ActivitiesService;

class ActivitiesController extends Controller {
    public function __construct(private ActivitiesService $svc) {}

    public function index(ActivitiesRequest $req, ActivitiesService $svc) {
        $items = $svc->nearby(
            (float) $req->lat,
            (float) $req->lon,
            (int) $req->get('radius', 3000),
            (string) $req->get('type', 'all'),
        );

        // ALWAYS wrap for the test/UI
        return response()->json(['items' => array_values($items)]);
    }
}
