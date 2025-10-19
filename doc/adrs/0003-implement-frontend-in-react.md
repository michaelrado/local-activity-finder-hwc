# 3. Implement frontend in React

Date: 2025-10-14

## Status

Accepted

## Context

We need a map-centric UI with search, filters (indoor/outdoor/all), and detail views. Requirements: modern SPA, local caching, friendly DX, lint/format in CI.

## Decision

Use React with Vite, ESLint (flat config) + Prettier, and @tanstack/react-query for client caching. Place UI under ui/, with lint + format checks in CI (format runs in check mode).

## Consequences

* Fast dev server, small production bundle.
* Predictable data fetching/caching with React Query.
