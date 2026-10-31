#!/bin/sh
# Builds dist/kibarou-cms-<version>.zip: the application with production dependencies (vendor/), without tests,
# development files, secrets or data. Upload and unzip it on the server, then open /install.php.
set -eu
ROOT=$(cd "$(dirname "$0")/.." && pwd)
VERSION=${1:-$(git -C "$ROOT" describe --always 2>/dev/null || date +%Y%m%d)}
OUT="$ROOT/dist"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$OUT" "$TMP/kibarou"

tar -C "$ROOT" \
  --exclude=./.git --exclude=./node_modules --exclude=./vendor --exclude=./tests --exclude=./dist \
  --exclude=./test-results --exclude=./.taste-skill --exclude=./.env --exclude=./.env.tmp --exclude=./phpunit.xml \
  --exclude=./PRODUCT.md --exclude=./.phpunit.result.cache --exclude=./package.json --exclude=./package-lock.json \
  --exclude='./storage/*' --exclude='./public/uploads/*' --exclude=./public/install.php.bak \
  -cf - . | tar -C "$TMP/kibarou" -xf -
cp "$ROOT/public/uploads/.htaccess" "$TMP/kibarou/public/uploads/.htaccess"
cp "$ROOT/storage/.htaccess" "$TMP/kibarou/storage/.htaccess"
for d in logs cache sessions backups private sites; do mkdir -p "$TMP/kibarou/storage/$d"; touch "$TMP/kibarou/storage/$d/.gitkeep"; done

( cd "$TMP/kibarou" && composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --quiet )
printf '# Jamais servi par le web\nRequire all denied\n' > "$TMP/kibarou/vendor/.htaccess"
echo "$VERSION" > "$TMP/kibarou/VERSION"
rm -f "$OUT/kibarou-cms-$VERSION.zip"
( cd "$TMP" && zip -qr "$OUT/kibarou-cms-$VERSION.zip" kibarou )
echo "Built: $OUT/kibarou-cms-$VERSION.zip"
