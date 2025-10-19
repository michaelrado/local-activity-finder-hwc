<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Fixture {
    public static function load(string $set, string $name, array $fallback = []): array {
        $path = "fixtures/{$set}/{$name}.json";
        if (! Storage::exists($path)) {
            return $fallback;
        }
        $raw = Storage::get($path);
        $data = json_decode($raw, true);

        return is_array($data) ? $data : $fallback;
    }

    public static function set(?string $override = null): string {
        return $override ?: config('app.mock_fixture', 'default');
    }

    public static function key(string $set, string $name): string {
        // Keys are relative to the default disk root. For FILESYSTEM_DISK=local, this is storage/app
        return "private/fixtures/{$set}/{$name}.json";
    }
}
