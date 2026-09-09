#!/usr/bin/env bash
set -euo pipefail

# This script is intended for a dedicated, manually started stage-deploy runner.
# It never reads database credentials from the repository and never runs SQL migrations.

workspace_path="${1:?Pass the checked-out workspace path as the first argument}"

required_variables=(
  TORI_STAGE_RELEASES_DIR
  TORI_STAGE_CURRENT_LINK
  TORI_STAGE_SHARED_ENV
  TORI_STAGE_HEALTH_URL
  TORI_STAGE_HEALTH_TOKEN
)

for variable_name in "${required_variables[@]}"; do
  if [[ -z "${!variable_name:-}" ]]; then
    echo "Missing required deployment setting: ${variable_name}" >&2
    exit 1
  fi
done

if [[ ! -d "$workspace_path" ]]; then
  echo "Workspace does not exist: $workspace_path" >&2
  exit 1
fi

if [[ ! -f "$TORI_STAGE_SHARED_ENV" ]]; then
  echo "Shared stage .env does not exist: $TORI_STAGE_SHARED_ENV" >&2
  exit 1
fi

command -v rsync >/dev/null 2>&1 || { echo 'rsync is required for deployment' >&2; exit 1; }
command -v curl >/dev/null 2>&1 || { echo 'curl is required for the health check' >&2; exit 1; }

release_stamp="$(date -u +%Y%m%d%H%M%S)"
release_suffix="${GITEA_SHA:-manual}"
release_suffix="${release_suffix//[^[:alnum:]._-]/_}"
release_path="${TORI_STAGE_RELEASES_DIR%/}/${release_stamp}-${release_suffix:0:12}"
next_link="${TORI_STAGE_CURRENT_LINK}.next"
previous_release=""
deployment_complete=0
release_created=0
next_link_created=0

if [[ -e "$next_link" || -L "$next_link" ]]; then
  echo "Temporary deployment link already exists: $next_link" >&2
  exit 1
fi

cleanup_failed_release() {
  if [[ "$next_link_created" -eq 1 ]]; then
    rm -f -- "$next_link"
  fi

  if [[ "$deployment_complete" -eq 1 || "$release_created" -eq 0 || ! -d "$release_path" ]]; then
    return
  fi

  local active_release=""
  if [[ -L "$TORI_STAGE_CURRENT_LINK" ]]; then
    active_release="$(readlink -f "$TORI_STAGE_CURRENT_LINK" 2>/dev/null || true)"
  fi

  if [[ "$active_release" != "$release_path" ]]; then
    rm -rf -- "$release_path"
  fi
}

trap cleanup_failed_release EXIT

mkdir -p "$TORI_STAGE_RELEASES_DIR"
mkdir "$release_path"
release_created=1

if [[ -L "$TORI_STAGE_CURRENT_LINK" ]]; then
  previous_release="$(readlink "$TORI_STAGE_CURRENT_LINK")"
fi

rsync -a \
  --exclude='.env' \
  --exclude='.git' \
  --exclude='.gitea' \
  --exclude='cache' \
  --exclude='logs' \
  --exclude='tmp' \
  --exclude='temp' \
  "${workspace_path%/}/" "${release_path%/}/"

# The runner uses a restrictive umask. The release directories inherit group
# www-data from the setgid release root, so PHP-FPM can read only this release.
find "$release_path" -type d -exec chmod 2750 {} +
find "$release_path" -type f -exec chmod 0640 {} +

ln -s "$TORI_STAGE_SHARED_ENV" "$release_path/.env"

ln -s "$release_path" "$next_link"
next_link_created=1
mv -Tf "$next_link" "$TORI_STAGE_CURRENT_LINK"
next_link_created=0

if ! curl --noproxy '*' --fail --silent --show-error --max-time 15 \
  --header "X-Tori-Health-Token: ${TORI_STAGE_HEALTH_TOKEN}" \
  "$TORI_STAGE_HEALTH_URL" >/dev/null; then
  if [[ -n "$previous_release" ]]; then
    ln -s "$previous_release" "$next_link"
    next_link_created=1
    mv -Tf "$next_link" "$TORI_STAGE_CURRENT_LINK"
    next_link_created=0
    echo "Stage health check failed; restored the previous release." >&2
  fi
  exit 1
fi

deployment_complete=1
echo "Stage release activated: $release_path"
