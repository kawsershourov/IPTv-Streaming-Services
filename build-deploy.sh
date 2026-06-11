#!/usr/bin/env bash
# Build a production-ready copy of SunPlex into ./ready-for-deploy
# Usage:  bash build-deploy.sh
set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"
OUT="ready-for-deploy"

echo "Building $OUT …"
rm -rf "$OUT"
mkdir -p "$OUT"

# Entry files + folders that make up the running site.
INCLUDE=(
  index.php watch.php category.php login.php register.php logout.php
  forgot-password.php reset-password.php account.php plans.php subscribe.php
  stats-data.php app admin assets player sql uploads DEPLOY.md .user.ini
)
for item in "${INCLUDE[@]}"; do
  [ -e "$item" ] && cp -r "$item" "$OUT/$item"
done

# Production .htaccess (forces HTTPS, HSTS, headers, gzip, cache).
cp deploy/htaccess-production "$OUT/.htaccess"

# Production config with a freshly generated app key.
KEY="$(php -r 'echo bin2hex(random_bytes(24));')"
sed "s/__APP_KEY__/$KEY/" deploy/config.production.php > "$OUT/app/config.php"

# Strip any stray dev artifacts from the copy.
find "$OUT" -name '.DS_Store' -delete 2>/dev/null || true
find "$OUT" -name 'Thumbs.db' -delete 2>/dev/null || true

echo "Done. Size: $(du -sh "$OUT" | cut -f1)"
echo "Next: edit $OUT/app/config.php (DB creds), zip the CONTENTS of $OUT, upload to public_html."
