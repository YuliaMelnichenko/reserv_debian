#!/usr/bin/env bash
set -euo pipefail

forbidden_paths=(
  '.env'
  '.env.local'
  '.env.production'
)

for path in "${forbidden_paths[@]}"; do
  if git ls-files --error-unmatch "$path" >/dev/null 2>&1; then
    echo "Sensitive configuration file is tracked: $path" >&2
    exit 1
  fi
done

if git ls-files | grep -Eiq '(^|/)(id_rsa|id_ed25519|.*\.pem|.*\.key)$'; then
  echo 'Private key file is tracked in the repository.' >&2
  exit 1
fi

echo 'No tracked secrets or local environment files found.'
