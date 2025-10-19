<?php

namespace App\Services;

use App\Support\Backoff;
use App\Support\Capture;
use App\Support\Fixture;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class ActivitiesService
 */
class ActivitiesService {
    public function nearby(float $lat, float $lon, int $radiusM, string $type = 'all'): array {
        // Mock fixtures if enabled (unchanged)

        if (config('app.mock_mode')) {
            $set = Fixture::set(request('mock'));
            $data = Fixture::load($set, 'activities', ['items' => []]);
            $items = $data['items'] ?? [];

            $t = strtolower($type ?? 'all');
            if ($t === 'indoor') {
                $items = array_values(array_filter($items, fn ($p) => ! empty($p['indoor'])));
            }
            if ($t === 'outdoor') {
                $items = array_values(array_filter($items, fn ($p) => empty($p['indoor'])));
            }

            return $items; // controller wraps
        }
        $prov = config('services.poi.provider', 'geoapify');
        $key = sprintf('poi:%s:%s:%d:%s:%s', round($lat, 3), round($lon, 3), $radiusM, $type, $prov);

        return Cache::remember($key, now()->addMinutes(2), function () use ($lat, $lon, $radiusM, $type, $prov) {
            return match ($prov) {
                'geoapify' => $this->nearbyGeoapify($lat, $lon, $radiusM, $type),
                'opentripmap' => $this->nearbyOpenTripMap($lat, $lon, $radiusM, $type), // optional legacy
                default => $this->nearbyGeoapify($lat, $lon, $radiusM, $type),
            };
        });
    }

    private function geoapifyCategoriesFor(string $type): array {
        $cfg = config('services.geoapify');

        return match ($type) {
            'indoor' => $cfg['indoor'] ?: $cfg['all'],
            'outdoor' => $cfg['outdoor'] ?: $cfg['all'],
            default => $cfg['all'],
        };
    }

    private function isIndoorGeoapify(array $poiCats): ?bool {
        $indoor = config('services.geoapify.indoor', []);
        $outdoor = config('services.geoapify.outdoor', []);
        if (! $indoor && ! $outdoor) {
            return null;
        } // unknown

        // Any POI category starting with one of our prefixes counts
        $has = function (array $prefixes) use ($poiCats) {
            foreach ($poiCats as $c) {
                foreach ($prefixes as $p) {
                    if ($p !== '' && str_starts_with($c, $p)) {
                        return true;
                    }
                }
            }

            return false;
        };

        if ($has($indoor)) {
            return true;
        }
        if ($has($outdoor)) {
            return false;
        }

        return null; // not in either list → unknown (fallback later)
    }

    /** -------- Provider: Geoapify Places -------- */
    private function nearbyGeoapify(float $lat, float $lon, int $radiusM, string $type): array {
        $endpoint = config('services.geoapify.endpoint');
        $apiKey = config('services.geoapify.key');
        // $categories = trim((string)config('services.geoapify.categories', ''));
        $catsArr = $this->geoapifyCategoriesFor($type);
        $catsStr = implode(',', $catsArr);
        $radiusM = max(100, min($radiusM, 20000)); // sensible cap for perf

        // filter=circle:lon,lat,radius  (note lon,lat order!)
        $query = [
            'filter' => sprintf('circle:%f,%f,%d', $lon, $lat, $radiusM),
            'limit' => 80,
            'bias' => sprintf('proximity:%f,%f', $lon, $lat),
            'lang' => 'en',
            'apiKey' => $apiKey,
        ];
        // if ($categories !== '') $query['categories'] = $categories;
        if ($catsStr !== '') {
            $query['categories'] = $catsStr;
        }

        $resp = Backoff::get($endpoint, $query, ['User-Agent' => 'Highwater-Challenge/1.0']);
        if (! $resp->ok()) {
            if (config('app.debug')) {
                Log::warning('Geoapify error', ['status' => $resp->status(), 'body' => $resp->body(), 'q' => $query]);
            }
            abort(502, 'Upstream POI error');
        }

        $raw = $resp->json();
        Capture::save('activities', 'geoapify_raw', $raw, Capture::withRequestMeta(compact('lat', 'lon', 'radiusM')));

        $features = (array) ($raw['features'] ?? []);
        $norm = [];
        foreach ($features as $f) {
            $props = $f['properties'] ?? [];
            $geom = $f['geometry']['coordinates'] ?? null; // [lon, lat]
            if (! is_array($geom) || count($geom) < 2) {
                continue;
            }
            $ilon = (float) $geom[0];
            $ilat = (float) $geom[1];

            $cats = (array) ($props['categories'] ?? []);

            $category = implode(',', array_slice(array_map('strval', $cats), 0, 4));

            $poiCats = array_values(array_map('strval', (array) ($props['categories'] ?? [])));
            $indoorFlag = $this->isIndoorGeoapify($poiCats);

            // fallback: if unknown, simple heuristic: museums/cinema/theatre → indoor
            if ($indoorFlag === null) {
                $indoorFlag = false;  // If we don't know, assume no roof.
            }

            $humanCat = (function (array $cats) {
                $map = [
                    'tourism.attraction.viewpoint' => 'Viewpoint',
                    'tourism.attraction.fountain' => 'Fountain',
                    'tourism.attraction.clock' => 'Clock',
                    'tourism.sights.bridge' => 'Bridge',
                    'tourism.sights.tower' => 'Tower',
                    'tourism.sights.castle' => 'Castle',
                    'tourism.sights.fort' => 'Fort',
                    'tourism.sights.lighthouse' => 'Lighthouse',
                    'tourism.sights.square' => 'Square',
                    'tourism.sights.battlefield' => 'Battlefield',
                    'tourism.sights.archaeological_site' => 'Archaeological Site',
                    'tourism.sights.city_gate' => 'City Gate',
                    'tourism.sights.ruines' => 'Ruins',
                    'beach' => 'Beach',
                    'natural' => 'Natural Feature',
                    'natural.forest' => 'Forest',
                    'natural.water' => 'Water',
                    // indoor ones in case they slip through:
                    'entertainment.museum' => 'Museum',
                    'entertainment.cinema' => 'Cinema',
                    'entertainment.culture.theatre' => 'Theatre',
                    'sport.sports_centre' => 'Sports Centre',
                ];
                foreach ($cats as $c) {
                    if (isset($map[$c])) {
                        return $map[$c];
                    }
                    // allow prefix match (e.g. tourism.sights.memorial.*)
                    foreach ($map as $k => $label) {
                        if (str_starts_with($c, $k)) {
                            return $label;
                        }
                    }
                }
                // fallback to first category segment capitalized
                $first = $cats[0] ?? 'Place';

                return ucwords(str_replace(['_', '.'], [' ', ' '], $first));
            })($poiCats);

            // robust fallback order for name
            $name = $props['name']
                ?? ($props['datasource']['raw']['name'] ?? null)
                ?? null;

            if (! $name) {
                $near = $props['street']
                     ?? $props['address_line1']
                     ?? $props['suburb']
                     ?? $props['city']
                     ?? null;

                $name = $near ? "{$humanCat} near {$near}" : $humanCat;
            }

            $norm[] = [
                'id' => (string) ($props['place_id'] ?? $props['datasource']['raw']['id'] ?? md5($ilat . ',' . $ilon)),
                // 'name'      => (string)($props['name'] ?? $props['street'] ?? 'Unnamed'),
                'name' => (string) $name,
                'category' => $category,
                'lat' => $ilat,
                'lon' => $ilon,
                'distanceM' => (int) round($this->haversineM($lat, $lon, $ilat, $ilon)),
                'indoor' => (bool) $indoorFlag,
            ];
        }

        // Type filter (indoor/outdoor/all)
        $norm = array_values(array_filter($norm, function ($p) use ($type) {
            if ($type === 'indoor') {
                return $p['indoor'] === true;
            }
            if ($type === 'outdoor') {
                return $p['indoor'] === false;
            }

            return true;
        }));

        $normalized = ['items' => $norm];
        Capture::save('activities', 'normalized', $normalized, ['count' => count($norm)]);

        return $norm;
    }

    /** ---------- Provider: Overpass OSM ---------- */
    private function nearbyOverpass(float $lat, float $lon, int $radiusM, string $type): array {
        $radiusM = max(100, min($radiusM, 20000));
        // Build Overpass QL query:
        // we request nodes/ways/relations with tags in the configured set, then get center coords.
        $tags = array_filter(array_map('trim', explode(',', (string) config('services.overpass.tags', 'tourism,amenity,leisure,natural'))));
        // Each entry might be "key" or "key=value"
        $tagFilters = array_map(function ($t) {
            return str_contains($t, '=') ? "[{$t}]" : "[{$t}]";
        }, $tags);

        $filter = implode('', $tagFilters); // e.g., [tourism][amenity][leisure][natural]
        if ($filter === '') {
            $filter = '[tourism][amenity][leisure][natural]';
        }

        // Limit results for responsiveness
        $max = 80;
        $ql = <<<OVERPASS
[out:json][timeout:25];
(
  node$filter(around:$radiusM,$lat,$lon);
  way$filter(around:$radiusM,$lat,$lon);
  rel$filter(around:$radiusM,$lat,$lon);
);
out center $max;
OVERPASS;

        $resp = Backoff::get(
            config('services.overpass.endpoint'),
            ['data' => $ql],
            ['User-Agent' => 'Highwater-Challenge/1.0 (+email)'],
        );

        if (! $resp->ok()) {
            if (config('app.debug')) {
                Log::warning('Overpass upstream error', ['status' => $resp->status(), 'body' => $resp->body()]);
            }
            abort(502, 'Upstream POI error');
        }

        $raw = $resp->json();
        Capture::save('activities', 'overpass', $raw, Capture::withRequestMeta(['lat' => $lat, 'lon' => $lon, 'radius' => $radiusM, 'tags' => $tags]));

        $elements = (array) ($raw['elements'] ?? []);
        $norm = [];
        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];
            // Coordinates: nodes have lat/lon; ways/relations have center.lat/center.lon
            $ilat = $el['lat'] ?? $el['center']['lat'] ?? null;
            $ilon = $el['lon'] ?? $el['center']['lon'] ?? null;
            if (! is_finite((float) $ilat) || ! is_finite((float) $ilon)) {
                continue;
            }

            // Name & category
            $name = $tags['name'] ?? ($tags['brand'] ?? 'Unnamed');
            // Build a simple “category” string from common tag keys
            $catParts = [];
            foreach (['tourism', 'amenity', 'leisure', 'natural', 'shop'] as $k) {
                if (! empty($tags[$k])) {
                    $catParts[] = $k . '=' . $tags[$k];
                }
            }
            $category = implode(',', $catParts);

            $dist = (int) round($this->haversineM($lat, $lon, (float) $ilat, (float) $ilon));

            $norm[] = [
                'id' => (string) ($el['id'] ?? md5($ilat . ',' . $ilon)),
                'name' => (string) $name,
                'category' => $category,
                'lat' => (float) $ilat,
                'lon' => (float) $ilon,
                'distanceM' => $dist,
                'indoor' => $this->isIndoorOTM($category),
            ];
        }

        // Filter by distance and type
        $norm = array_values(array_filter($norm, function ($p) use ($radiusM, $type) {
            if ($p['distanceM'] > $radiusM) {
                return false;
            }
            if ($type === 'indoor') {
                return $p['indoor'] === true;
            }
            if ($type === 'outdoor') {
                return $p['indoor'] === false;
            }

            return true;
        }));

        $normalized = ['items' => $norm];
        Capture::save('activities', 'normalized', $normalized, ['count' => count($norm)]);

        return $norm; // controller can wrap as { items }
    }

    /** ---------- Provider: OpenTripMap ---------- */
    private function nearbyOpenTripMap(float $lat, float $lon, int $radiusM, string $type): array {
        if (config('app.mock_mode')) {
            $data = Fixture::load(Fixture::set(request('mock')), 'activities', ['items' => []]);
            $items = $data['items'] ?? [];

            // apply 'type' filter even in mock mode
            return array_values(array_filter($items, function ($p) use ($type) {
                return $type === 'all' ? true : ($type === 'indoor' ? $p['indoor'] : ! $p['indoor']);
            }));
        }

        $key = sprintf('poi:%0.3f:%0.3f:%d:%s', $lat, $lon, $radiusM, $type);

        return Cache::remember($key, now()->addMinutes(2), function () use ($lat, $lon, $radiusM, $type) {
            $resp = Backoff::get('https://api.opentripmap.com/0.1/en/places/radius', [
                'lon' => $lon,
                'lat' => $lat,
                'radius' => $radiusM,
                // 'rate'=>2,
                'limit' => 5,
                // 'kinds'  => 'interesting_places,amusements,sport,adult,tourist_facilities,accomodations',
                // 'kinds'  => 'interesting_places,amusements,sport,tourist_facilities,accomodations,casino,nightclubs,alcohol,hookah',
                'format' => 'json',
                'apikey' => config('services.opentripmap.key'),
            ]);
            error_log($resp);
            abort_unless($resp->ok(), 502, 'Upstream POI error');
            $raw = $resp->json(); // array of items

            // ---- inline normalization ----
            $norm = [];
            foreach ($raw as $i) {
                $ilat = (float) ($i['point']['lat'] ?? $i['geometry']['coordinates'][1] ?? null);
                $ilon = (float) ($i['point']['lon'] ?? $i['geometry']['coordinates'][0] ?? null);
                if ($ilat === 0.0 && $ilon === 0.0) {
                    continue;
                }
                $cat = (string) ($i['kinds'] ?? $i['properties']['kinds'] ?? '');
                $norm[] = [
                    'id' => (string) ($i['xid'] ?? $i['id'] ?? md5($ilat . ',' . $ilon)),
                    'name' => (string) ($i['name'] ?? $i['properties']['name'] ?? 'Unnamed'),
                    'category' => $cat,
                    'lat' => $ilat,
                    'lon' => $ilon,
                    'distanceM' => (int) round($this->haversineM($lat, $lon, $ilat, $ilon)),
                    'indoor' => $this->isIndoorOTM($cat),
                ];
            }
            // filter by radius/type
            $norm = array_values(array_filter($norm, function ($p) use ($radiusM, $type) {
                if ($p['distanceM'] > $radiusM) {
                    return false;
                }
                if ($type === 'indoor') {
                    return $p['indoor'] === true;
                }
                if ($type === 'outdoor') {
                    return $p['indoor'] === false;
                }

                return true;
            }));

            $normalized = ['items' => $norm];

            // ---- capture (optional) ----
            Capture::save('activities', 'opentripmap', $raw, Capture::withRequestMeta(['lat' => $lat, 'lon' => $lon, 'radius' => $radiusM, 'type' => $type]));
            Capture::save('activities', 'normalized', $normalized, ['count' => count($norm)]);

            return $normalized['items']; // if your controller expects array, or return $normalized if it expects {items}
        });
    }

    private function haversineM(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        // Fractional meters as a long, no rounding.
        return 2 * $R * asin(min(1, sqrt($a)));
    }

    private function isIndoorOTM(string $cat): bool {
        $c = strtolower($cat);

        return str_contains($c, 'museum') || str_contains($c, 'gallery') || str_contains($c, 'cinema') ||
               str_contains($c, 'mall') || str_contains($c, 'theatre') || str_contains($c, 'theater') ||
               str_contains($c, 'aquarium') || str_contains($c, 'shopping') || str_contains($c, 'indoor');
    }

    private function isIndoor(string $category): bool {
        $c = strtolower($category);

        return str_contains($c, 'tourism.museum') || str_contains($c, 'entertainment.cinema')
            || str_contains($c, 'entertainment.theatre') || str_contains($c, 'leisure.sports_centre')
            || str_contains($c, 'shopping') || str_contains($c, 'mall') || str_contains($c, 'indoor');
    }
}
