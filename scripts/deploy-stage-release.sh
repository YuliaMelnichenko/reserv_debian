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
release_path="${TORI_STAGE_RELEASES_DIR%/}/${release_stamp}-${release_suffix:0:12}"
next_link="${TORI_STAGE_CURRENT_LINK}.next"
previous_release=""

mkdir -p "$TORI_STAGE_RELEASES_DIR"
mkdir "$release_path"

if [[ -e "$next_link" || -L "$next_link" ]]; then
  echo "Temporary deployment link already exists: $next_link" >&2
  exit 1
fi

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

ln -s "$TORI_STAGE_SHARED_ENV" "$release_path/.env"

ln -s "$release_path" "$next_link"
mv -Tf "$next_link" "$TORI_STAGE_CURRENT_LINK"

if ! curl --fail --silent --show-error --max-time 15 "$TORI_STAGE_HEALTH_URL" >/dev/null; then
  if [[ -n "$previous_release" ]]; then
    ln -s "$previous_release" "$next_link"
    mv -Tf "$next_link" "$TORI_STAGE_CURRENT_LINK"
    echo "Stage health check failed; restored the previous release." >&2
  fi
  exit 1
fi

echo "Stage release activated: $release_path"
