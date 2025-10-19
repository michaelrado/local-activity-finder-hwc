#!/usr/bin/env bash
set -euo pipefail
SET="${1:-nyc}"
ROOT="storage/app"
SRC="${ROOT}/private/capture/${SET}"
DST="${ROOT}/private/fixtures/${SET}"
mkdir -p "${DST}"

copy_latest () {
  local svc="$1" prefix="$2" target="$3"
  local file
  file=$(ls -1t "${SRC}/${svc}/${prefix}_"*.json 2>/dev/null | head -n1 || true)
  if [[ -z "${file}" ]]; then
    echo "No ${prefix} for ${svc}"
    return
  fi
  # strip the capture wrapper: .data if present, else identity
  jq '.data // .' "${file}" > "${DST}/${target}"
  echo "Promoted ${file} -> ${DST}/${target}"
}

# weather
copy_latest weather normalized weather.json

# activities
copy_latest activities normalized activities.json

# geocode (search & reverse)
copy_latest geocode normalized_search geocode_search.json
copy_latest geocode normalized_reverse geocode_reverse.json

# optional: copy latest raw per service too
if [[ "${INCLUDE_RAW:-0}" == "1" ]]; then
  for svc in weather activities geocode; do
    file=$(ls -1t "${SRC}/${svc}/"*raw*.json 2>/dev/null | head -n1 || true)
    if [[ -n "${file}" ]]; then
      jq '.data // .' "${file}" > "${DST}/${svc}__raw.json"
      echo "Promoted RAW ${file} -> ${DST}/${svc}__raw.json"
    fi
  done
fi

