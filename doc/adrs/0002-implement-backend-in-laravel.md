# 2. Implement backend in Laravel

Date: 2025-10-14

## Status

Accepted

## Context

We need a lightweight API to aggregate geocoding, weather, and activities, expose a small REST surface, support mock/fixture mode, caching, retries, and validation. Team skill set is PHP/Laravel; deployment target is a mobile app.

## Decision

Use Laravel for the backend (validation via Form Requests, caching, HTTP client with retry/backoff, config-driven mock mode that serves fixtures from storage/app/..., Pest for tests, Pint for style).

## Consequences

* Fast developer velocity, batteries-included stack (routing, validation, cache, queue, HTTP).
* Easy testability with Pest and fixtures.
