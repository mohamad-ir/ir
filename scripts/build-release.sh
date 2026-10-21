#!/usr/bin/env bash
# Builds the DirectAdmin upload package: dist/oct-academy-<version>.zip
#
#   oct-academy/   application (goes OUTSIDE the web root, e.g. /home/USER/domains/DOMAIN/oct-academy)
#   public_html/   public files (copy INTO the domain's public_html)
#   docs/          installation & administration guides
#
# Requirements on the build machine: PHP 8.3+, Composer 2, Node 20+.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(grep -oP "'version' => '\K[^']+" "$ROOT/config/cms.php")"
WORK="$(mktemp -d)"
PKG="$WORK/package"
APP="$PKG/oct-academy"
DIST="$ROOT/dist"

echo "› Building release v$VERSION in $WORK"
mkdir -p "$APP" "$DIST"

# 1. Copy tracked source (respects .gitignore)
git -C "$ROOT" archive HEAD | tar -x -C "$APP"

# 2. Production dependencies and assets
( cd "$APP" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet )
# Remove VCS metadata, tests and docs from dependencies (not needed at runtime)
find "$APP/vendor" -type d \( -name .git -o -name tests -o -name test -o -name test_files -o -name docs -o -name .github \) -prune -exec rm -rf {} + 2>/dev/null || true
rm -rf "$APP/vendor/laravel/framework/bin"  # monorepo split tooling, not used at runtime
( cd "$ROOT" && npm ci --silent && npm run build --silent )
rm -rf "$APP/public/build" && cp -a "$ROOT/public/build" "$APP/public/build"
( cd "$APP" && php artisan filament:assets --quiet 2>/dev/null || true )

# 3. Strip development-only files
rm -rf "$APP"/{tests,release,node_modules,.github,scripts,phpunit.xml,vite.config.js,package.json,package-lock.json,.editorconfig,.gitattributes,.npmrc}
rm -f "$APP/.env" "$APP/storage/app/installed.lock"
find "$APP/storage" -type f ! -name .gitignore -delete
mkdir -p "$APP/storage/app/backups" "$APP/storage/framework/"{cache/data,sessions,views} "$APP/storage/logs" "$APP/bootstrap/cache"
printf 'Require all denied\n' > "$APP/.htaccess"   # in case the folder is ever exposed by mistake

# 4. public_html variant (application one level up in ../oct-academy)
cp -a "$APP/public" "$PKG/public_html"
cat > "$PKG/public_html/index.php" <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Location of the application folder, relative to this public_html folder.
$appPath = __DIR__.'/../oct-academy';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/bootstrap/prepare-env.php';
require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
PHP

# 5. Documentation
mkdir -p "$PKG/docs"
cp "$ROOT"/docs/*.md "$PKG/docs/" 2>/dev/null || true
cp "$ROOT/README.md" "$PKG/README.md"

# 6. Zip
rm -f "$DIST/oct-academy-$VERSION.zip"
( cd "$PKG" && zip -qr -9 "$DIST/oct-academy-$VERSION.zip" . -x '*.DS_Store' )
rm -rf "$WORK"
echo "✓ $(du -h "$DIST/oct-academy-$VERSION.zip" | cut -f1)  dist/oct-academy-$VERSION.zip"
