#!/usr/bin/env bash
# Membuat paket siap-upload untuk shared hosting (InfinityFree / Hostinger).
# Jalankan dari Git Bash:  bash deploy/build-hosting.sh
# Hasil di build-hosting/:
#   sigapdesa/              folder aplikasi (isinya diupload ke htdocs / public_html)
#   sigapdesa-hosting.zip   folder yang sama dalam bentuk zip (untuk File Manager Hostinger)
#   sigapdesa.sql           salinan database lokal untuk diimport lewat phpMyAdmin
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/build-hosting"
APP="$OUT/sigapdesa"
MYSQLDUMP="${MYSQLDUMP:-/c/xampp/mysql/bin/mysqldump}"
DB_NAME="${DB_NAME:-sigapdesa}"

echo "==> Menyiapkan $OUT"
rm -rf "$OUT"
mkdir -p "$APP"
cd "$ROOT"

echo "==> Menyalin file aplikasi"
git ls-files -co --exclude-standard \
  | grep -vE '^(tests/|deploy/|build-hosting/|\.github/|\.env|phpunit\.xml|webpack\.mix\.js|package(-lock)?\.json|README\.md|\.editorconfig|\.styleci\.yml|\.gitattributes|\.gitignore$)' \
  | while IFS= read -r f; do
      [ -f "$f" ] || continue
      mkdir -p "$APP/$(dirname "$f")"
      cp "$f" "$APP/$f"
    done

echo "==> Memasang library produksi (tanpa paket development)"
cd "$APP"
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress

echo "==> Folder storage & cache"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
rm -f storage/logs/*.log storage/framework/sessions/* storage/framework/views/*.php bootstrap/cache/*.php

echo "==> .env produksi + APP_KEY baru"
cp "$ROOT/deploy/env.hosting.example" .env
php artisan key:generate --force --no-interaction
php artisan package:discover --ansi >/dev/null

echo "==> .htaccess folder utama (mengarahkan ke public/)"
cp "$ROOT/deploy/root.htaccess" .htaccess

echo "==> Export database $DB_NAME"
"$MYSQLDUMP" -uroot --default-character-set=utf8mb4 --single-transaction --skip-comments "$DB_NAME" > "$OUT/sigapdesa.sql"
# Versi terkompresi: batas upload phpMyAdmin di hosting gratis biasanya ~10 MB
gzip -9 -k "$OUT/sigapdesa.sql"

echo "==> Membuat zip"
cd "$OUT"
/c/Windows/System32/tar.exe -a -c -f sigapdesa-hosting.zip -C sigapdesa .

echo
echo "Selesai. Isi .env di build-hosting/sigapdesa/.env (APP_URL & DB_*) sebelum upload."
