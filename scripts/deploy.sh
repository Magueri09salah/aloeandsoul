#!/usr/bin/env bash
# Atomic release on the server (Hostinger SSH). Keeps the last 5 releases
# for instant rollback. See docs/DEPLOYMENT.md.
#
#   bash deploy.sh ~/release-20261013-120000.tar.gz
#
# Layout (APP_ROOT defaults to ~/domains/<domain>/aloeandsoul):
#   $APP_ROOT/releases/<timestamp>/   extracted code
#   $APP_ROOT/shared/.env             production configuration (create once)
#   $APP_ROOT/shared/storage/         uploads, logs, sessions (persist across releases)
#   $APP_ROOT/current → releases/<timestamp>
#   public_html → $APP_ROOT/current/public
set -euo pipefail

ARCHIVE="${1:?Usage: deploy.sh path/to/release.tar.gz}"
APP_ROOT="${APP_ROOT:-$HOME/domains/${DOMAIN:-aloeandsoul.com}/aloeandsoul}"
PHP="${PHP_BIN:-php}"            # e.g. PHP_BIN=/opt/alt/php83/usr/bin/php if `php -v` is not 8.3
# Composer command; e.g. COMPOSER_CMD="/opt/alt/php83/usr/bin/php /usr/local/bin/composer"
COMPOSER_CMD="${COMPOSER_CMD:-composer}"
KEEP=5

"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
  || { echo "✗ $PHP is $("$PHP" -r 'echo PHP_VERSION;'), need 8.3+. Set PHP_BIN."; exit 1; }

[[ -f "$APP_ROOT/shared/.env" ]] || { echo "✗ Create $APP_ROOT/shared/.env first (see docs/DEPLOYMENT.md)."; exit 1; }

STAMP="$(date +%Y%m%d%H%M%S)"
RELEASE="$APP_ROOT/releases/$STAMP"
mkdir -p "$RELEASE" "$APP_ROOT/shared/storage" "$HOME/backups"

echo "→ Extracting $ARCHIVE into $RELEASE"
tar -xzf "$ARCHIVE" -C "$RELEASE"

echo "→ Linking shared .env and storage"
rm -rf "$RELEASE/storage"
ln -s "$APP_ROOT/shared/.env" "$RELEASE/.env"
ln -s "$APP_ROOT/shared/storage" "$RELEASE/storage"
mkdir -p "$APP_ROOT/shared/storage"/{app/public,framework/{cache/data,sessions,views},logs}

cd "$RELEASE"
if [[ ! -d vendor ]]; then
  echo "→ Installing PHP dependencies"
  # shellcheck disable=SC2086 # intentional word splitting of the command
  $COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

echo "→ Backing up the database before migrating"
DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')
DB_USER=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')
DB_PASS=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"')
MYSQL_PWD="$DB_PASS" mysqldump --single-transaction --quick --no-tablespaces -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" \
  | gzip > "$HOME/backups/db-$STAMP.sql.gz"
ls -1t "$HOME"/backups/db-*.sql.gz | tail -n +11 | xargs -r rm --

echo "→ Migrating (additive migrations only; never fresh/reset in production)"
"$PHP" artisan down --retry=30 || true
"$PHP" artisan migrate --force --no-interaction
"$PHP" artisan db:seed --force --no-interaction   # idempotent: only creates missing categories/pages
"$PHP" artisan storage:link --force >/dev/null 2>&1 || true
"$PHP" artisan optimize

echo "→ Switching current → $STAMP"
ln -sfn "$RELEASE" "$APP_ROOT/current.tmp" && mv -Tf "$APP_ROOT/current.tmp" "$APP_ROOT/current"
"$PHP" "$APP_ROOT/current/artisan" up

echo "→ Removing old releases (keeping $KEEP)"
ls -1dt "$APP_ROOT"/releases/* | tail -n +$((KEEP + 1)) | xargs -r rm -rf --

echo "✓ Deployed $STAMP. Run the smoke tests in docs/DEPLOYMENT.md."
