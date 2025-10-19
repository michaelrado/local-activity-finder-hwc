# 5. Implement open meteo for weather data

Date: 2025-10-14

## Status

Accepted

## Context

We need current conditions + hourly to drive recommendations (indoor vs outdoor). Must be free to use and reliable.

## Decision

Use Open-Meteo API for weather. Normalize to:

{ tempC, windKph, precipMm, precipProb, hourly: [...] }

Implement conservative retry/backoff and cache results short-term.

## Consequences

* Free, no API key, good reliability.
* Simple normalization for recommendation rules.
* Must handle rate limits via caching and backoff.
* Geographic quirks (mountain/coast interpolation) require testing.
