// tests/Feature/RetryTest.php
<?php

use Illuminate\Support\Facades\Http;

it('retries on 500 then succeeds', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::sequence()->pushStatus(500)->push(['ok' => true], 200),
    ]);

    $res = $this->getJson('/api/weather?lat=40&lon=-73')->assertOk()->json();
    expect($res)->toHaveKey('tempC'); // assuming your normalizer fills it
});
