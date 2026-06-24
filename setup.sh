#!/usr/bin/env bash
#
# RTB — installation clé en main (nécessite WP-CLI et une install WordPress fonctionnelle).
# À lancer depuis la RACINE de votre site WordPress, après avoir greffé le thème + plugins
# dans wp-content/ (voir README → Installation).
#
# Usage :  bash setup.sh
#
set -euo pipefail

command -v wp >/dev/null 2>&1 || { echo "❌ WP-CLI introuvable. Installez-le : https://wp-cli.org"; exit 1; }
wp core is-installed 2>/dev/null || { echo "❌ WordPress n'est pas installé dans ce dossier."; exit 1; }

echo "→ Activation du thème RTB"
wp theme activate rtb

echo "→ Activation des extensions du projet"
for p in onass-live-edit rtb-search rtb-chat rtb-seo; do
  wp plugin is-installed "$p" >/dev/null 2>&1 && wp plugin activate "$p" || echo "  (extension $p absente de wp-content/plugins — ignorée)"
done

echo "→ Multilingue : Polylang (dépendance tierce)"
if ! wp plugin is-active polylang >/dev/null 2>&1; then
  wp plugin install polylang --activate || echo "  (échec installation Polylang — installez-le manuellement si besoin)"
fi

if wp plugin is-active polylang >/dev/null 2>&1; then
  echo "→ Création des langues (si absentes)"
  # slug:locale:nom
  for L in "fr:fr_FR:Français" "en:en_US:English" "mos:mos:Mooré" "dyu:dyu:Dioula" "ff:ff:Fulfuldé" "gux:gux:Gulmancéma"; do
    SLUG="${L%%:*}"; REST="${L#*:}"; LOCALE="${REST%%:*}"; NAME="${REST#*:}"
    wp pll lang list --field=slug 2>/dev/null | grep -qx "$SLUG" \
      || wp pll lang create "$NAME" "$SLUG" "$LOCALE" 2>/dev/null || true
  done
fi

echo "→ Rafraîchissement des permaliens (routes /assistant, /login)"
wp rewrite flush

echo "→ Apprentissage du lexique de l'assistant (correction des fautes)"
wp rtb-chat learn 2>/dev/null || echo "  (lexique : à relancer après import de contenu)"

echo "✅ Terminé. Site prêt — pensez à régler Apparence → Personnaliser → RTB."
