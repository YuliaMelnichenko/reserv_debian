#!/usr/bin/env bash
set -euo pipefail

stage_url="${1:-${TORI_STAGE_SMOKE_URL:-}}"

if [[ -z "$stage_url" ]]; then
  echo 'Pass the stage URL as the first argument or set TORI_STAGE_SMOKE_URL.' >&2
  exit 2
fi

if [[ -z "${TORI_STAGE_HEALTH_TOKEN:-}" ]]; then
  echo 'Set TORI_STAGE_HEALTH_TOKEN before running the smoke check.' >&2
  exit 2
fi

base_url="${stage_url%/}"
health_url="${TORI_STAGE_HEALTH_URL:-${base_url}/health.php}"

health_response="$(curl --fail --silent --show-error --max-time 15 \
  --header "X-Tori-Health-Token: ${TORI_STAGE_HEALTH_TOKEN}" \
  "$health_url")"

if [[ "$health_response" != *'"status":"ok"'* ]]; then
  echo "Unexpected health response: ${health_response}" >&2
  exit 1
fi

auth_status="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' --max-time 15 "${base_url}/auth.php")"

if [[ "$auth_status" != '200' ]]; then
  echo "Authentication page returned HTTP ${auth_status}." >&2
  exit 1
fi

echo 'Stage smoke checks passed: health endpoint and authentication page are available.'
