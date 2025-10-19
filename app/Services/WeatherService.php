<?php

namespace App\Services;

use App\Support\Backoff;
use App\Support\Capture;
use App\Support\Fixture;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Class WeatherService
 */
class WeatherService {
    public function current(float $lat, float $lon): array {
        if (config('app.mock_mode')) {
            $wx = Fixture::load(
                Fixture::set(request('mock')),
                'weather',
                ['tempC' => 20, 'windKph' => 10, 'precipMm' => 0, 'precipProb' => 5, 'hourly' => []],
            );

            // guarantee required keys for the test
            $wx += ['tempC' => 20, 'windKph' => 10, 'precipMm' => 0, 'precipProb' => 5, 'hourly' => []];

            return $wx;
        }
        $key = 'weather:' . round($lat, 3) . ':' . round($lon, 3);

        return Cache::remember($key, now()->addMinutes(1), function () use ($lat, $lon) {
            // $resp = Http::retry(3, 250, throw: false)->get(
            $resp = Backoff::get(
                'https://api.open-meteo.com/v1/forecast',
                [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current_weather' => true,
                    'hourly' => 'temperature_2m,precipitation,precipitation_probability,windspeed_10m',
                    // Make sure we have enough horizon for a day+:
                    'forecast_days' => 2,
                    'past_days' => 0,
                    'timezone' => 'auto',         // align times to local tz
                ],
            );
            abort_unless($resp->ok(), 502, 'Upstream weather error');
            Capture::save('weather', 'open-meteo', $resp->json(), Capture::withRequestMeta(['lat' => $lat, 'lon' => $lon]));

            $j = $resp->json();

            // Current
            $tempC = data_get($j, 'current_weather.temperature');
            $windKph = data_get($j, 'current_weather.windspeed');
            $precipMmCurrent = data_get($j, 'current_weather.precipitation');
            $precipProbCurrent = data_get($j, 'current_weather.precipProb');

            // Some locations don’t provide precip for “current”—fallback to first hour if present:
            $hourly = data_get($j, 'hourly', []);
            $times = Arr::get($hourly, 'time', []);
            $temps = Arr::get($hourly, 'temperature_2m', []);
            $precip = Arr::get($hourly, 'precipitation', []);
            $prob = Arr::get($hourly, 'precipitation_probability', []);
            $winds = Arr::get($hourly, 'windspeed_10m', []);

            // Build detailed rows (align by index)
            $len = min(count($times), count($temps), count($precip), count($prob), count($winds));
            $detail = [];
            for ($i = 0; $i < $len; $i++) {
                $detail[] = [
                    'time' => $times[$i],
                    'tempC' => $temps[$i],
                    'precipMm' => $precip[$i],
                    'precipProb' => $prob[$i],
                    'windKph' => $winds[$i],
                ];
            }

            // index of first hour >= "now" (rounded down to hour) in provider's timezone
            $tz = Arr::get($j, 'timezone', 'UTC'); // Open-Meteo echoes this
            $nowT = CarbonImmutable::now($tz)->minute(0)->second(0);
            $startI = 0;
            foreach ($times as $i => $t) {
                if ($nowT->lessThanOrEqualTo(CarbonImmutable::parse($t, $tz))) {
                    $startI = $i;
                    break;
                }
            }

            $detail = array_slice($detail, $startI, 12);

            // Derive “current” precip from the first hour if missing
            $precipMmCurrent = is_null(data_get($j, 'current_weather.precipitation')) ? ($detail[0]['precipMm'] ?? 0) : data_get($j, 'current_weather.precipitation');
            $precipProbCurrent = $detail[0]['precipProb'] ?? 0;

            $result = [
                'tempC' => $tempC,
                'windKph' => $windKph,
                'precipMm' => $precipMmCurrent,
                'precipProb' => $precipProbCurrent,
                'hourlyDetail' => $detail,  // array of { time, tempC, precipMm, precipProb, windKph }
            ];
            Capture::save('weather', 'normalized', $result, ['lat' => $lat, 'lon' => $lon]);

            return $result;
        });
    }
}
