<?php

// tests/Feature/OpenApiSpecTest.php

it('has a valid openapi.json with required paths and schemas', function () {
    $path = base_path('public/openapi.json');
    expect(file_exists($path))->toBeTrue('public/openapi.json does not exist');

    $json = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    // Minimal OpenAPI contract checks
    expect($json)->toHaveKeys(['openapi', 'info', 'paths', 'components']);
    expect($json['openapi'])->toStartWith('3.0.');

    // Required paths
    $mustPaths = [
        '/api/weather',
        '/api/activities',
        '/api/recommendations',
        '/api/geocode/search',
        '/api/geocode/reverse',
    ];
    foreach ($mustPaths as $p) {
        expect($json['paths'])->toHaveKey($p);
        expect($json['paths'][$p])->toHaveKey('get');
    }

    // Required schemas
    $mustSchemas = [
        'Weather', 'Activity', 'ActivitiesResponse',
        'RecommendationItem', 'RecommendationsResponse',
        'GeocodeCandidate', 'GeocodeSearchResponse', 'GeocodeReverseResponse',
        'Error',
    ];
    foreach ($mustSchemas as $s) {
        expect($json['components']['schemas'])->toHaveKey($s);
    }

    // Check key parameters exist
    foreach (['lat', 'lon', 'radius', 'type', 'q', 'limit'] as $param) {
        expect($json['components']['parameters'])->toHaveKey($param);
    }

    // Spot-check Weather required fields
    $weatherReq = $json['components']['schemas']['Weather']['required'] ?? [];
    foreach (['tempC', 'windKph', 'precipMm', 'precipProb', 'hourly'] as $field) {
        expect($weatherReq)->toContain($field);
    }
});

it('examples in openapi.json are well-formed JSON', function () {
    $json = json_decode(file_get_contents(base_path('public/openapi.json')), true, flags: JSON_THROW_ON_ERROR);

    $examples = [
        data_get($json, 'paths./api/weather.get.responses.200.content.application/json.examples.default.value'),
        data_get($json, 'paths./api/activities.get.responses.200.content.application/json.examples.default.value'),
        data_get($json, 'paths./api/recommendations.get.responses.200.content.application/json.examples.default.value'),
        data_get($json, 'paths./api/geocode/search.get.responses.200.content.application/json.examples.default.value'),
        data_get($json, 'paths./api/geocode/reverse.get.responses.200.content.application/json.examples.default.value'),
    ];

    foreach ($examples as $i => $ex) {
        if ($ex !== null) {
            expect(json_encode($ex, JSON_THROW_ON_ERROR))->not->toBeFalse("Example #$i is not JSON-serializable");
        }
    }
});
