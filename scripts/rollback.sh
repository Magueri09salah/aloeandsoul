#!/usr/bin/env bash
# Point `current` back to the previous release. Code only: database
# migrations are not reversed (restore ~/backups/db-*.sql.gz if needed).
set -euo pipefail

APP_ROOT="${APP_ROOT:-$HOME/domains/${DOMAIN:-aloeandsoul.com}/aloeandsoul}"
PHP="${PHP_BIN:-php}"

CURRENT="$(readlink -f "$APP_ROOT/current")"
PREVIOUS="$(ls -1dt "$APP_ROOT"/releases/* | grep -vx "$CURRENT" | head -1)"
[[ -n "$PREVIOUS" ]] || { echo "✗ No previous release to roll back to."; exit 1; }

ln -sfn "$PREVIOUS" "$APP_ROOT/current.tmp" && mv -Tf "$APP_ROOT/current.tmp" "$APP_ROOT/current"
"$PHP" "$APP_ROOT/current/artisan" optimize
echo "✓ Rolled back to $(basename "$PREVIOUS")"
