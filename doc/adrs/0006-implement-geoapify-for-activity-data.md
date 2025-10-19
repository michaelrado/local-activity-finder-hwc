# 6. implement geoapify for activity data

Date: 2025-10-18

## Status

Accepted

## Context

OpenTripMap proved unreliable in practice. We need a stable, free-access source with category coverage to support indoor/outdoor filters and better naming quality.  Geoapify proved both reliable and performant in testing.

## Decision

Switch to Geoapify Places for activities. Map provider categories to three filters: indoor, outdoor, all. Maintain a category allowlist for each and normalize results to our schema; continue to set indoor based on category mapping (data normalization).

## Consequences

* Improved reliability and data quality vs OTM.
* Clear category-driven indoor/outdoor mapping.
* Need to maintain category lists (places of worship?; include dining/shopping?).
* Some items may be unnamed; handle gracefully in API using category and location data.
