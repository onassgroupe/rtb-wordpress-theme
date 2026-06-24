#!/usr/bin/env bash
#
# RTB — installe un site WordPress COMPLET (cœur inclus) à partir de ce dépôt.
# Pré-requis : PHP 8.1+, MySQL/MariaDB accessible, WP-CLI.
#
# Usage (avec valeurs par défaut) :
#   bash install-full.sh
# ou en surchargeant :
#   DB_NAME=rtb DB_USER=root DB_PASS=secret URL=http://localhost:8080 TARGET=./rtb-site bash install-full.sh
#
set -euo pipefail

DB_NAME="${DB_NAME:-rtb_wp}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-localhost}"
URL="${URL:-http://localhost:8080}"
TITLE="${TITLE:-RTB}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
TARGET="${TARGET:-./rtb-site}"

command -v wp >/dev/null 2>&1 || { echo "❌ WP-CLI introuvable : https://wp-cli.org"; exit 1; }
SRC="$(cd "$(dirname "$0")" && pwd)"

echo "→ Téléchargement du cœur WordPress dans $TARGET"
wp core download --path="$TARGET" --locale=fr_FR --force

echo "→ Configuration ($DB_NAME @ $DB_HOST)"
wp config create --path="$TARGET" --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="$DB_HOST" --force
wp db create --path="$TARGET" 2>/dev/null || true

echo "→ Installation du site"
wp core install --path="$TARGET" --url="$URL" --title="$TITLE" \
  --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --skip-email

echo "→ Greffe du thème, des extensions et des mu-plugins"
mkdir -p "$TARGET/wp-content/mu-plugins"
cp -R "$SRC/wp-content/themes/rtb" "$TARGET/wp-content/themes/"
for p in rtb-search rtb-chat rtb-seo onass-live-edit; do
  cp -R "$SRC/wp-content/plugins/$p" "$TARGET/wp-content/plugins/"
done
cp "$SRC"/wp-content/mu-plugins/*.php "$TARGET/wp-content/mu-plugins/" 2>/dev/null || true

echo "→ Activation"
wp theme activate rtb --path="$TARGET"
wp plugin activate rtb-search rtb-chat rtb-seo onass-live-edit --path="$TARGET"
wp plugin install polylang --activate --path="$TARGET" || echo "  (Polylang : installez-le manuellement si besoin)"
wp rewrite flush --path="$TARGET"
wp rtb-chat learn --path="$TARGET" 2>/dev/null || true

echo "✅ Site complet prêt dans $TARGET"
echo "   URL : $URL   ·   admin : $ADMIN_USER / $ADMIN_PASS"
echo "   Servez-le, ex. : wp server --path=\"$TARGET\" --host=0.0.0.0 --port=8080"
