# 7. implement nominatim for geocode data

Date: 2025-10-18

## Status

Accepted

## Context

We need free forward and reverse geocoding with good global coverage and permissive usage for a demo.

## Decision

Use Nominatim (OpenStreetMap) for geocoding. Implement:
* /api/geocode/search: normalized list of candidates.
* /api/geocode/reverse: normalized address string + coordinates.
Add caching and request validation (query, lat/lon ranges, limit). Provide mock-mode fixtures for both search and reverse.

## Consequences

* Free and widely available.
* Works well with our OSM-based activity sources.
* Local database should be created for production systems.
* Usage policy requires identifying User-Agent and rate-friendly behavior; we add caching and backoff.
* Data varies by region; keep normalization tolerant to missing fields.
