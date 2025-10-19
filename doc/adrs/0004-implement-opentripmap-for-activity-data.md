# 4. Implement opentripmap for activity data

Date: 2025-10-14

## Status

Superseded

## Context

Initial activity POI provider choice was OpenTripMap due to a free tier and category coverage.

## Decision

Integrate OpenTripMap for activities (radius search, categories), normalize into our internal shape (id, name, lat, lon, distanceM, category, indoor?).

## Consequences

* Reasonable global coverage, free access.
* Operational reliability issues encountered (timeouts/empty responses), leading to flaky UX and tests.
* Superseded by ADR-0006 switching to Geoapify for activities.
