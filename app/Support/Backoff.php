<?php

// app/Support/Backoff.php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Backoff {
    public static function get(string $url, array $query, array $headers = [], int $maxAttempts = 4): Response {
        $attempt = 0;
        $delay = 200; // ms base
        do {
            $resp = Http::withHeaders($headers)->timeout(10)->get($url, $query);

            if ($resp->ok()) {
                return $resp;
            }

            $status = $resp->status();
            $transient = in_array($status, [429, 500, 502, 503, 504], true);
            if (! $transient || ++$attempt >= $maxAttempts) {
                return $resp;
            }

            // exponential backoff with jitter
            usleep(intval(($delay + random_int(0, 150)) * 1000));
            $delay = min($delay * 2, 2000);
        } while (true);
    }
}
