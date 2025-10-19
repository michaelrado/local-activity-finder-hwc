<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'nominatim' => [
        'base' => env('NOMINATIM_BASE', 'https://nominatim.openstreetmap.org'),
        'email' => env('NOMINATIM_EMAIL'),
        'ttl' => (int) env('GEOCODE_CACHE_MIN', 10),
    ],

    'opentripmap' => [
        'key' => env('OPENTRIPMAP_KEY', 'ERROR_LOADING_KEY'),
        'kinds' => env('OPENTRIPMAP_KINDS', 'interesting_places'),
    ],

    'poi' => [
        'provider' => env('POI_PROVIDER', 'overpass'),
    ],

    'overpass' => [
        'endpoint' => env('OVERPASS_ENDPOINT', 'https://overpass-api.de/api/interpreter'),
        'tags' => env('OVERPASS_TAGS', 'tourism,amenity,leisure,natural'),
    ],

    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
        'endpoint' => env('GEOAPIFY_ENDPOINT', 'https://api.geoapify.com/v2/places'),
        'indoor' => array_filter(array_map('trim', explode(',', (string) env('GEOAPIFY_INDOOR_CATS', '')))),
        'outdoor' => array_filter(array_map('trim', explode(',', (string) env('GEOAPIFY_OUTDOOR_CATS', '')))),
        'all' => array_filter(array_map('trim', explode(',', (string) env('GEOAPIFY_ALL_CATS', '')))),

    ],

];
