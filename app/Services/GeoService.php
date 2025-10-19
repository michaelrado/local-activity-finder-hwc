<?php

namespace App\Services;

use App\Support\Capture;
use App\Support\Fixture;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GeoService {
    private string $base;

    private string $email;

    private int $ttlMin;

    public function __construct() {
        $cfg = config('services.nominatim');
        $this->base = rtrim($cfg['base'] ?? 'https://nominatim.openstreetmap.org', '/');
        $this->email = $cfg['email'] ?? '';
        $this->ttlMin = (int) ($cfg['ttl'] ?? 10);

        if (empty($this->email)) {
            // Nominatim requires a contact in UA or email param. Fail fast if not set.
            throw new \RuntimeException('NOMINATIM_EMAIL is required (see .env).');
        }
    }

    /**
     * Forward geocode a free-form query string to candidates.
     *
     * @return array<int, array{lat: float, lon: float, display_name: string, bbox: array<float>, type?: string, importance?: float}>
     */
    public function forward(string $query, int $limit = 5): array {
        if (config('app.mock_mode')) {
            // naive mapping: if the query contains "Times Square", use that fixture; else empty
            $set = Fixture::set(request('mock'));
            if (stripos($query, 'Times Square') !== false) {
                $data = Fixture::load($set, 'geocode_search_times_square', ['items' => []]);

                return $data['items'] ?? [];
            }

            return [];
        }
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $key = sprintf('geo:fwd:%s:%d', Str::lower($query), $limit);

        return Cache::remember($key, now()->addMinutes($this->ttlMin), function () use ($query, $limit) {
            $this->throttle('nominatim:fwd');
            $resp = $this->client()->get($this->base . '/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'limit' => max(1, min($limit, 10)),
                'email' => $this->email, // per policy
            ]);

            if (! $resp->ok()) {
                throw new HttpException(502, 'Upstream geocode error');
            }

            $raw = $resp->json() ?? [];

            $items = array_map(function ($i) {
                return [
                    'lat' => isset($i['lat']) ? (float) $i['lat'] : null,
                    'lon' => isset($i['lon']) ? (float) $i['lon'] : null,
                    'display_name' => $i['display_name'] ?? '',
                    'bbox' => array_map('floatval', $i['boundingbox'] ?? []),
                    'type' => $i['type'] ?? null,
                    'importance' => isset($i['importance']) ? (float) $i['importance'] : null,
                ];
            }, $raw);
            Capture::save('geocode', 'nominatim_search', $raw, Capture::withRequestMeta(['q' => $query, 'limit' => $limit]));
            Capture::save('geocode', 'normalized_search', $items, ['count' => count($items)]);

            return $items;
        });
    }

    /**
     * Reverse geocode coordinates to an address summary.
     *
     * @return array{lat: float, lon: float, display_name: string, address: array<string,string>}
     */
    public function reverse(float $lat, float $lon): array {
        if (config('app.mock_mode')) {
            return Fixture::load(Fixture::set(request('mock')), 'geocode_reverse', [
                'lat' => $lat, 'lon' => $lon, 'display_name' => 'Mock Location', 'address' => [],
            ]);
        }
        $lat = round($lat, 6);
        $lon = round($lon, 6);
        $key = sprintf('geo:rev:%s:%s', $lat, $lon);

        return Cache::remember($key, now()->addMinutes($this->ttlMin), function () use ($lat, $lon) {
            $this->throttle('nominatim:rev');
            $resp = $this->client()->get($this->base . '/reverse', [
                'lat' => $lat,
                'lon' => $lon,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'zoom' => 14,
                'email' => $this->email,
            ]);

            if (! $resp->ok()) {
                throw new HttpException(502, 'Upstream reverse geocode error');
            }
            $j = $resp->json();

            $result = [
                'lat' => isset($j['lat']) ? (float) $j['lat'] : $lat,
                'lon' => isset($j['lon']) ? (float) $j['lon'] : $lon,
                'display_name' => $j['display_name'] ?? '',
                'address' => $j['address'] ?? [],
            ];
            Capture::save('geocode', 'nominatim_reverse', $resp->json(), Capture::withRequestMeta(['lat' => $lat, 'lon' => $lon]));
            Capture::save('geocode', 'normalized_reverse', $result);

            return $result;

        });
    }

    /** Shared HTTP client with retry & UA */
    private function client() {
        $ua = sprintf(
            'Highwater-Challenge/1.0 (+https://example.com) contact:%s',
            $this->email,
        );

        return Http::withHeaders(['User-Agent' => $ua])
            ->retry(3, 250, function ($exception, $request) {
                // Retry on transient network or common upstream errors
                $status = optional($exception->response)->status();
                usleep(random_int(50, 150) * 1000); // jitter

                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || in_array($status, [429, 500, 502, 503, 504], true);
            })
            ->timeout(10);
    }

    /**
     * Nominatim asks for <= 1 req/sec per client. Enforce a simple app-level throttle.
     */
    private function throttle(string $bucket): void {
        $key = $bucket; // could add IP if doing per-user
        $ok = RateLimiter::attempt($key, $perMinute = 60, function () {
            // noop; attempt reserves a token
        });

        if (! $ok) {
            // Back off a bit and proceed (or you could throw 429)
            usleep(300 * 1000);
        }
    }
}
