#!/usr/bin/env bash
set -euo pipefail

DOC_DIR="${1:-doc/architecture/diagrams}"
OUT_FMT_PNG="-tpng"
OUT_FMT_SVG="-tsvg"

echo "Rendering PlantUML diagrams in: ${DOC_DIR}"

# Create output dirs alongside each .puml (no special structure needed)
if ! command -v docker >/dev/null 2>&1; then
  echo "ERROR: Docker is required. Please install Docker and retry." >&2
  exit 1
fi

# Render PNG
docker run --rm -v "$(pwd)/${DOC_DIR}:/data" plantuml/plantuml ${OUT_FMT_PNG} /data/*.puml
# Render SVG
docker run --rm -v "$(pwd)/${DOC_DIR}:/data" plantuml/plantuml ${OUT_FMT_SVG} /data/*.puml

echo "Done. Outputs alongside the .puml files (PNG & SVG)."

