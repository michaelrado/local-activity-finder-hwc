<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Ensure we use the local disk via config (no facade manager calls)
    // config(['filesystems.default' => env('FILESYSTEM_DISK', 'local')]);
    // If any previous test faked or resolved a disk, forget to rebuild with current config
    // Storage::forgetDisk('local');

    // Force mock mode during tests
    config([
        'app.mock_mode' => true,
        'app.mock_fixture' => 'default', // fallback; we’ll pass ?mock=raining
    ]);
});

it('prioritizes indoor activities when raining (mock mode)', function () {
    // Optional sanity: confirm the raining fixtures exist where Fixture::key() points
    expect(File::exists(storage_path('app/private/fixtures/raining/weather.json')))->toBeTrue();
    expect(File::exists(storage_path('app/private/fixtures/raining/activities.json')))->toBeTrue();

    $res = getJson('/api/recommendations?lat=40.7&lon=-74.0&mock=raining')
        ->assertOk()
        ->assertJson(fn ($j) => $j->has('items'));

    $items = $res->json('items');
    expect($items)->toBeArray()->not->toBeEmpty();
    expect($items[0]['indoor'] ?? null)->toBeTrue(); // raining => indoor-first
});
