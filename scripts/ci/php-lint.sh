#!/usr/bin/env bash
set -euo pipefail

php_bin="${PHP_BIN:-php}"

command -v "$php_bin" >/dev/null 2>&1 || {
  echo "PHP executable is unavailable: $php_bin" >&2
  exit 1
}

while IFS= read -r -d '' php_file; do
  "$php_bin" -l "$php_file"
done < <(find . -type f -name '*.php' -not -path './.git/*' -print0 | sort -z)
