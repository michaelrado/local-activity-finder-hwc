<?php

namespace App\Http\Middleware;

use Closure;

class MockModeHeader {
    public function handle($request, Closure $next) {
        $response = $next($request);
        if (config('app.mock_mode')) {
            $response->headers->set('X-Source', 'mock');
        } else {
            $response->headers->set('X-Source', 'live');
        }

        return $response;
    }
}
