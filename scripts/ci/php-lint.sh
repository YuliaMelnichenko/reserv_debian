#!/usr/bin/env bash
set -euo pipefail

while IFS= read -r -d '' php_file; do
  php -l "$php_file"
done < <(find . -type f -name '*.php' -not -path './.git/*' -print0 | sort -z)
