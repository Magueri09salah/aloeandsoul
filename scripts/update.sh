#!/usr/bin/env bash
# Install or update the site on the server from GitHub. See docs/DEPLOYMENT.md.
#
#   bash ~/domains/aloeandsoul.com/aloeandsoul/scripts/update.sh            latest version
#   bash ~/domains/aloeandsoul.com/aloeandsoul/scripts/update.sh <commit>   go back to a version
#
# It follows the `production` branch, which GitHub Actions builds from `main`
# with the compiled assets (.github/workflows/production.yml): the server never
# runs npm. .env, uploads (storage/app/public) and logs are not in git, so they
# are kept.
set -Eeuo pipefail
trap 'echo "✗ Update stopped. The site may still be in maintenance mode: fix the error above, then run this script again."' ERR

# Everything runs from this function, so git replacing this file midway is harmless.
main() {
  local app php composer branch target previous
  app="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
  php="${PHP_BIN:-php}"            # e.g. PHP_BIN=/opt/alt/php83/usr/bin/php if `php -v` is not 8.3
  composer="${COMPOSER_CMD:-composer}"
  branch="${BRANCH:-production}"
  target="${1:-origin/$branch}"

  "$php" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
    || { echo "✗ $php is $("$php" -r 'echo PHP_VERSION;'), need 8.3+. Set PHP_BIN."; exit 1; }

  cd "$app"
  [[ -f .env ]] || { echo "✗ Create $app/.env first (see docs/DEPLOYMENT.md)."; exit 1; }
  mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache "$HOME/backups"

  echo "→ Fetching $branch from GitHub"
  git fetch --depth 1 origin "$branch"
  previous="$(git rev-parse --short HEAD)"

  echo "→ Backing up the database"
  local stamp db_name db_user db_pass db_host
  stamp="$(date +%Y%m%d%H%M%S)"
  db_name=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')
  db_user=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')
  db_pass=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')
  db_host=$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"')
  MYSQL_PWD="$db_pass" mysqldump --single-transaction --quick --no-tablespaces -h "$db_host" -u "$db_user" "$db_name" \
    | gzip > "$HOME/backups/db-$stamp.sql.gz"
  ls -1t "$HOME"/backups/db-*.sql.gz | tail -n +11 | xargs -r rm --

  [[ -d vendor ]] && { "$php" artisan down --retry=30 || true; }

  echo "→ Switching the code to $target"
  git reset --hard "$target"

  echo "→ Installing PHP dependencies"
  # Hostinger disables proc_open, so Composer cannot run the package scripts
  # itself: skip them and run package discovery directly.
  # shellcheck disable=SC2086 # intentional word splitting of the command
  $composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts
  "$php" artisan package:discover --ansi

  [[ -f public/build/manifest.json ]] \
    || { echo "✗ public/build is missing: this is not a production build. Site left in maintenance mode."; exit 1; }

  echo "→ Migrating (additive migrations only; never fresh/reset in production)"
  "$php" artisan migrate --force --no-interaction
  "$php" artisan db:seed --force --no-interaction   # idempotent: only creates missing categories/pages
  [[ -L public/storage ]] || ln -s ../storage/app/public public/storage   # what storage:link does
  "$php" artisan optimize
  "$php" artisan up

  echo "✓ Now on $(git log -1 --format='%h %s')"
  echo "  Previous version: $previous (to go back: bash $app/scripts/update.sh $previous)"
}

main "$@"
