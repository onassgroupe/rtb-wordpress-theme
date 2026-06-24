# Installation & configuration

## 1. WordPress
Installez WordPress (7.0+) de façon classique (cœur + base de données + `wp-config.php`).
PHP 8.1+ recommandé, avec l'extension `mbstring`.

## 2. Greffer le thème et les extensions
Depuis le dossier `wp-content/` de votre site :
```bash
git clone https://github.com/onassgroupe/rtb-wordpress-theme rtb-src

# Thème + extensions (lien symbolique en dev, ou copie en prod)
ln -s "$(pwd)/rtb-src/wp-content/themes/rtb"              themes/rtb
ln -s "$(pwd)/rtb-src/wp-content/plugins/rtb-search"      plugins/rtb-search
ln -s "$(pwd)/rtb-src/wp-content/plugins/rtb-chat"        plugins/rtb-chat
ln -s "$(pwd)/rtb-src/wp-content/plugins/rtb-seo"         plugins/rtb-seo
ln -s "$(pwd)/rtb-src/wp-content/plugins/onass-live-edit" plugins/onass-live-edit
cp    rtb-src/wp-content/mu-plugins/*.php                 mu-plugins/
```

## 3. Activation (wp-admin)
1. **Apparence → Thèmes** → activer **RTB**.
2. **Extensions** → activer **RTB Search**, **RTB Assistant**, **RTB SEO**, **Onass Live Edit**.
3. *(optionnel)* installer **Polylang** pour le multilingue.

## 4. Contenus & réglages
- **Personnaliser** (`Apparence → Personnaliser → RTB`) : contact, réseaux, ticker, hero, radio, À propos.
- **Antennes / Stations** : créez vos chaînes TV/radio et renseignez les URL de flux (+ repli YouTube).
- **Assistant** : `Outils → Assistant RTB → Apprendre le contenu` après avoir publié des articles.
- **Permaliens** : `Réglages → Permaliens` → enregistrer (active la route `/assistant` et `/login`).

## 5. Options utiles
| Besoin | Où |
|---|---|
| Extraction du texte des PDF | installer `poppler-utils` (`pdftotext`) sur le serveur |
| Recherche : tendances | automatique (table `*_rtb_search_queries`) |
| Cache de page | mu-plugin `rtb-page-cache.php` (vidé à chaque publication) |
| URL de connexion `/login` | mu-plugin `rtb-login-url.php` |

## 6. Variables d'environnement
Copiez `.env.example` → `.env` et renseignez vos valeurs (voir [DEPLOY.md](DEPLOY.md) pour Docker/Coolify).

## Dépannage
- **`/assistant` en 404** → ré-enregistrer les permaliens.
- **Assistant qui corrige mal les mots** → relancer *Outils → Assistant RTB → Apprendre*.
- **Anciens CSS/JS** → les assets sont versionnés par date de fichier ; videz le cache navigateur.
