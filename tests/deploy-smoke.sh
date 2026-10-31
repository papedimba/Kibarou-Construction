#!/bin/bash
# End-to-end deployment check on a real Apache + mod_php, simulating cPanel: the web root is the project root
# (so the root .htaccess does the protection). Builds the release, installs it through install.php, then probes it.
# Needs: apache2 + libapache2-mod-php8.3, MariaDB with a root socket login, zip, composer. Run as root.
set -u
ROOT=$(cd "$(dirname "$0")/.." && pwd)
W=/tmp/kib-deploy; PORT=8090; PASS=0; FAIL=0
# REWRITE=0 reproduces a host where mod_rewrite is not available (first real cPanel deployment).
REWRITE=${REWRITE:-1}
if [ "$REWRITE" = 1 ]; then INSTALL=/install.php; else INSTALL=/public/install.php; fi
echo "== mod_rewrite: $([ "$REWRITE" = 1 ] && echo on || echo OFF)"
ok()   { PASS=$((PASS+1)); printf '  ok   %s\n' "$1"; }
bad()  { FAIL=$((FAIL+1)); printf '  FAIL %s\n' "$1"; }
check(){ if [ "$2" = "$3" ]; then ok "$1"; else bad "$1 (expected $2, got $3)"; fi; }
code() { curl -s -o /dev/null -w '%{http_code}' -H "Host: ${2:-kibarou.example.test}" "http://127.0.0.1:$PORT$1"; }

command -v apache2 >/dev/null || { echo "apache2 missing"; exit 2; }
pkill -x apache2 2>/dev/null; sleep 1
rm -rf "$W"; mkdir -p "$W/run" "$W/log"
"$ROOT/bin/build-release.sh" deploytest >/dev/null || { echo "build failed"; exit 2; }
unzip -q "$ROOT/dist/kibarou-cms-deploytest.zip" -d "$W"
APP="$W/kibarou"; chown -R www-data:www-data "$APP" "$W/run" "$W/log"
mariadb -uroot -e "DROP DATABASE IF EXISTS kibarou_release; CREATE DATABASE kibarou_release CHARACTER SET utf8mb4; GRANT ALL ON kibarou_release.* TO 'kibarou'@'localhost';"

MODS=/usr/lib/apache2/modules
cat > "$W/httpd.conf" <<CONF
ServerRoot /etc/apache2
PidFile $W/run/apache.pid
ErrorLog $W/log/error.log
Listen 127.0.0.1:$PORT
User www-data
Group www-data
LoadModule mpm_prefork_module $MODS/mod_mpm_prefork.so
LoadModule authz_core_module $MODS/mod_authz_core.so
LoadModule dir_module $MODS/mod_dir.so
LoadModule mime_module $MODS/mod_mime.so
$([ "$REWRITE" = 1 ] && echo "LoadModule rewrite_module $MODS/mod_rewrite.so")
LoadModule headers_module $MODS/mod_headers.so
LoadModule expires_module $MODS/mod_expires.so
LoadModule deflate_module $MODS/mod_deflate.so
LoadModule filter_module $MODS/mod_filter.so
LoadModule alias_module $MODS/mod_alias.so
LoadModule php_module $MODS/libphp8.3.so
TypesConfig /etc/mime.types
DirectoryIndex index.php
<FilesMatch "\.php$">
  SetHandler application/x-httpd-php
</FilesMatch>
DocumentRoot "$APP"
<Directory "$APP">
  AllowOverride All
  Require all granted
</Directory>
CONF
apache2 -f "$W/httpd.conf" || { echo "apache failed"; cat "$W/log/error.log"; exit 2; }
sleep 1

echo "== before install"
check "install page is served"                 200 "$(code $INSTALL platform.example.test)"
for p in /.env /.env.example /composer.json /app/Core/App.php /vendor/autoload.php /storage/ /bin/console.php /database/migrations/001_core.sql /docs/ARCHITECTURE.md /routes/site.php /README.md /.git/config; do
  check "blocked: $p"                          403 "$(code $p platform.example.test)"
done

echo "== install through the web installer"
JAR="$W/jar"; BASE="http://127.0.0.1:$PORT"
TOKEN=$(curl -s -c "$JAR" -H "Host: platform.example.test" "$BASE$INSTALL" | sed -n 's/.*name="_csrf" value="\([0-9a-f]*\)".*/\1/p' | head -1)
[ -n "$TOKEN" ] && ok "csrf token present" || bad "csrf token missing"
OUT=$(curl -s -b "$JAR" -H "Host: platform.example.test" "$BASE$INSTALL" \
  --data-urlencode "_csrf=$TOKEN" --data-urlencode "db_host=localhost" --data-urlencode "db_port=3306" --data-urlencode "db_name=kibarou_release" \
  --data-urlencode "db_user=kibarou" --data-urlencode "db_pass=kibarou_dev" --data-urlencode "platform_host=platform.example.test" --data-urlencode "base_domain=example.test" \
  --data-urlencode "admin_name=Root" --data-urlencode "admin_email=root@example.test" --data-urlencode "admin_password=a-very-long-passphrase" \
  --data-urlencode "site_name=KIBAROU CONSTRUCTION" --data-urlencode "site_slug=kibarou" --data-urlencode "site_host=kibarou.example.test" \
  --data-urlencode "site_admin_name=Admin" --data-urlencode "site_admin_email=admin@example.test" --data-urlencode "site_admin_password=another-long-passphrase")
echo "$OUT" | grep -q "Installation terminée" && ok "installer reports success" || { bad "installer failed: $(echo "$OUT" | grep -o 'class="err"[^<]*<[^<]*' | head -2)"; }
[ ! -e "$APP/public/install.php" ] && ok "install.php deleted itself" || bad "install.php still present"
[ -f "$APP/.env" ] && [ "$(stat -c %a "$APP/.env")" = "600" ] && ok ".env written with mode 600" || bad ".env missing or wrong mode"
grep -q "^APP_DEBUG=0" "$APP/.env" && ok "APP_DEBUG=0" || bad "APP_DEBUG not 0"

echo "== after install"
check "/install.php gone"                      404 "$(code $INSTALL platform.example.test)"
check "platform login"                         200 "$(code /platform/login platform.example.test)"
check "site home"                              200 "$(code / kibarou.example.test)"
curl -s -H "Host: kibarou.example.test" "$BASE/" | grep -q "Construisons vos projets" && ok "home content rendered" || bad "home content missing"
check "unknown host"                           404 "$(code / nobody.example.test)"
check "robots.txt"                             200 "$(code /robots.txt)"
check "sitemap.xml"                            200 "$(code /sitemap.xml)"
check "pretty URL falls to router (404 page)"  404 "$(code /does-not-exist)"
for p in /.env /storage/installed.lock /storage/logs/app.log /app/Services/Installer.php /vendor/composer/installed.json; do
  check "still blocked: $p"                    403 "$(code $p)"
done
HDR=$(curl -s -D- -o /dev/null -H "Host: kibarou.example.test" -H "Accept-Encoding: gzip" "$BASE/assets/css/site.css")
echo "$HDR" | grep -qi "content-encoding: gzip" && ok "css is gzip-compressed" || bad "css not compressed"
echo "$HDR" | grep -qi "^cache-control:.*max-age=86400" && ok "css has cache headers" || bad "css missing cache headers"
check "font served"                            200 "$(code /assets/fonts/literata-latin-wght-normal.woff2)"
curl -s -H "Host: kibarou.example.test" "$BASE/" -D- -o /dev/null | grep -qi "content-security-policy" && ok "CSP header present" || bad "CSP header missing"

echo "== uploads cannot execute code"
mkdir -p "$APP/public/uploads/1"; printf '<?php echo "PWNED";' > "$APP/public/uploads/1/shell.php"; chown -R www-data "$APP/public/uploads"
BODY=$(curl -s -H "Host: kibarou.example.test" "$BASE/uploads/1/shell.php")
echo "$BODY" | grep -q PWNED && bad "uploaded PHP was EXECUTED" || ok "uploaded .php is not executed"
UC=$(code /uploads/1/shell.php); [ "$UC" != 200 ] && ok "uploaded .php not served (HTTP $UC)" || bad "uploaded .php served"
check "uploaded .php denied via /public/"      403 "$(code /public/uploads/1/shell.php)"
LIST=$(curl -s -H "Host: kibarou.example.test" "$BASE/uploads/1/"); LC=$(code /uploads/1/)
[ "$LC" != "200" ] && ! echo "$LIST" | grep -qi "Index of" && ok "uploads directory is not listed (HTTP $LC)" || bad "uploads directory listing exposed"

echo "== traversal and direct paths"
for p in "/assets/../../.env" "/assets/..%2f..%2f.env" "/assets/%2e%2e/%2e%2e/.env" "/public/../.env" "/public/.htaccess" "/public/uploads/.htaccess" "/storage/installed.lock" "/vendor/composer/installed.json"; do
  C=$(curl -s --path-as-is -o "$W/body" -w '%{http_code}' -H "Host: kibarou.example.test" "$BASE$p")
  if [ "$C" != 200 ] && ! grep -q "APP_KEY\|DB_PASS\|Require\|installed" "$W/body"; then ok "not served: $p (HTTP $C)"; else bad "SERVED: $p"; fi
done
check "admin login reachable"                  200 "$(code /admin/login)"
check "land catalogue reachable"               200 "$(code /terrains)"

echo "== CLI in the release"
( cd "$APP" && sudo -u www-data php bin/console.php doctor >"$W/doctor.txt" 2>&1; echo "doctor exit $?" ) | tail -1
grep -q "FAIL" "$W/doctor.txt" && bad "doctor reports FAIL" || ok "doctor: no FAIL"
( cd "$APP" && sudo -u www-data php bin/console.php backup --keep=5 ) | grep -q "Backup:" && ok "backup command works" || bad "backup command failed"
B=$(ls "$APP"/storage/backups/*.zip | head -1)
check "backup not downloadable over the web"   403 "$(code /storage/backups/$(basename "$B"))"

pkill -x apache2 2>/dev/null
echo "== $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
