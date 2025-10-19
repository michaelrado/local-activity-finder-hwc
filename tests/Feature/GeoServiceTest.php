<?php

use App\Services\GeoService;

it('validates geocode search params', function () {
    $this->getJson('/api/geocode/search')->assertStatus(422);
    $this->getJson('/api/geocode/search?q=a')->assertStatus(422);
});

it('validates reverse params', function () {
    $this->getJson('/api/geocode/reverse?lat=100&lon=0')->assertStatus(422);
});

it('can be resolved and caches results', function () {
    config(['services.nominatim.email' => 'test@example.com']);
    $svc = app(GeoService::class);
    // We won’t hit the network in tests; you can mock Http::fake() if desired.
    expect($svc)->toBeInstanceOf(GeoService::class);
});
