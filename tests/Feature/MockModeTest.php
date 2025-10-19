<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Storage::setDefaultDriver('local');
    config(['app.mock_mode' => true, 'app.mock_fixture' => 'default']);
    // ensure files are there in your test env; you can also Storage::fake() and seed JSON
});
it('serves weather from fixtures when mock mode is on', function () {
    $res = $this->getJson('/api/weather?lat=40.7&lon=-74.0')
        ->assertOk()
        ->assertHeader('X-Source', 'mock')
        ->json();

    expect($res)->toHaveKeys(['tempC', 'windKph', 'precipMm', 'precipProb', 'hourly']);
});

it('serves activities from fixtures and filters by type', function () {
    $this->getJson('/api/activities?lat=40.7&lon=-74.0&type=indoor')
        ->assertOk()
        ->assertJson(
            fn ($j) => $j
                ->has('items')                  // don’t force size === 1
                ->where('items.0.indoor', true), // first item is indoor
        );
});
