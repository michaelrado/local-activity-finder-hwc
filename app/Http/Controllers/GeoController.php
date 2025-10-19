<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeocodeReverseRequest;
use App\Http\Requests\GeocodeSearchRequest;
use App\Services\GeoService;

class GeoController extends Controller {
    public function __construct(private GeoService $geo) {}

    public function search(GeocodeSearchRequest $r) {
        return response()->json(['items' => $this->geo->forward($r->string('q'), $r->integer('limit', 5))]);
    }

    public function reverse(GeocodeReverseRequest $r) {
        return response()->json($this->geo->reverse($r->float('lat'), $r->float('lon')));
    }
}
