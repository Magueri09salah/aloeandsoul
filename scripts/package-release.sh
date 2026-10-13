#!/usr/bin/env bash
# Build a deployable archive on your computer (or in CI).
# Production never runs npm: the compiled assets travel inside the archive.
#
#   ./scripts/package-release.sh            → release-YYYYmmdd-HHMMSS.tar.gz
#   ./scripts/package-release.sh --vendor   → also bundles vendor/ (when the host has no Composer)
set -euo pipefail

cd "$(dirname "$0")/.."

WITH_VENDOR=false
[[ "${1:-}" == "--vendor" ]] && WITH_VENDOR=true

echo "→ Installing JS dependencies and building assets"
npm ci --no-audit --no-fund
npm run build

if $WITH_VENDOR; then
  echo "→ Installing production PHP dependencies"
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

NAME="release-$(date +%Y%m%d-%H%M%S).tar.gz"

EXCLUDES=(
  --exclude=./.git --exclude=./node_modules --exclude=./.env --exclude=./.env.*
  --exclude=./storage/logs/* --exclude=./storage/framework/cache/data/*
  --exclude=./storage/framework/sessions/* --exclude=./storage/framework/views/*
  --exclude=./storage/app/public/* --exclude=./public/storage --exclude=./public/hot
  --exclude=./tests --exclude=./playwright-report --exclude=./test-results
  --exclude=./bootstrap/cache/*.php --exclude="./release-*.tar.gz"
)
$WITH_VENDOR || EXCLUDES+=(--exclude=./vendor)

tar -czf "$NAME" "${EXCLUDES[@]}" .

if $WITH_VENDOR; then
  # Restore dev dependencies for local work.
  composer install --no-interaction >/dev/null
fi

echo "✓ Created $NAME ($(du -h "$NAME" | cut -f1))"
echo "  Upload it to the server and run: bash scripts/deploy.sh $NAME"
