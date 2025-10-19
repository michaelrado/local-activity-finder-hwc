<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

final class Capture {
    /**
     * Save a JSON blob to storage/app/capture/{set}/{service}/...
     *
     * @param  string  $service  logical name e.g. 'weather', 'activities', 'geocode'
     * @param  string  $kind  provider or phase e.g. 'open-meteo', 'opentripmap', 'nominatim', 'normalized'
     * @param  mixed  $payload  array or scalar to json_encode
     * @param  array  $meta  optional metadata (request params, coords, etc)
     */
    public static function save(string $service, string $kind, $payload, array $meta = []): void {
        // guard: local/test only + flag on
        if (! config('app.debug_capture')) {
            return;
        }
        if (App::environment('production')) {
            return;
        }

        $set = (string) config('app.capture_set', 'default');
        $dir = "capture/{$set}/{$service}";
        $ts = now()->format('Ymd_His');
        $file = "{$dir}/{$kind}_{$ts}.json";

        $out = ['_meta' => ['captured_at' => now()->toIso8601String(), 'service' => $service, 'kind' => $kind] + $meta,
            'data' => $payload];

        Storage::put($file, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** Convenience helper to include request params if enabled */
    public static function withRequestMeta(array $extra = []): array {
        return config('app.capture_include_request')
            ? ['request' => request()->query()] + $extra
            : $extra;
    }
}
