# Déploiement — RTB (Radiodiffusion Télévision du Burkina)

Ce document décrit la mise en production du site. Trois scénarios :

- **A.** Coolify (cible retenue — Docker Compose, proxy + SSL gérés par Coolify)
- **B.** Hébergement mutualisé (cPanel : O2switch, OVH, Hostinger…)
- **C.** Contenu de démonstration (importer la base + médias livrés)

> Le thème `rtb` **n'a pas d'étape de build** : le CSS est écrit à la main et
> Font Awesome est auto-hébergé. Rien à compiler — on livre les fichiers tels quels.

---

## Avant de déployer — checklist

- [ ] `wp-config.php` **n'est pas** committé (ignoré par `.gitignore`)
- [ ] Pas de `WP_DEBUG = true` dans le wp-config de prod
- [ ] Permaliens en `/%postname%/` (déjà configuré ; sinon Réglages → Permaliens → enregistrer)
- [ ] Plugins **Polylang** et **onass-live-edit** présents dans `wp-content/plugins/`
- [ ] DNS A/AAAA pointe vers le serveur
- [ ] Certificat TLS prévu (Let's Encrypt / proxy)

---

## A. Coolify  *(cible retenue)*

Tout est fourni : `Dockerfile`, `docker-compose.yml` (variables magiques Coolify),
`docker/extra-config.php`, `.dockerignore`, et le mu-plugin `rtb-admin-recovery.php`.

Coolify gère le **reverse-proxy (Traefik) + le certificat SSL Let's Encrypt** : on ne publie
aucun port à la main, on assigne juste un domaine dans l'UI.

### A.1 — Créer la ressource

1. Pousser ce dépôt sur Git (GitHub/GitLab) — sans `wp-config.php` ni `uploads/` (déjà `.gitignore`).
2. Coolify → **New Resource → Docker Compose** → connecter le repo (ou coller `docker-compose.yml`).
3. Coolify détecte 2 services : `wordpress` (build via `Dockerfile`) + `db` (MariaDB).

### A.2 — Domaine & variables

- **Domaine** : Coolify le génère via `SERVICE_FQDN_WORDPRESS_80` (route vers le port 80),
  ou saisir le vôtre (`www.rtb.bf`) dans l'onglet *Domains* du service `wordpress`.
- **Secrets** : `SERVICE_USER_WORDPRESS` / `SERVICE_PASSWORD_WORDPRESS` / `SERVICE_PASSWORD_MYSQLROOT`
  sont **générés automatiquement** par Coolify (rien à saisir).
- `WP_HOME` / `WP_SITEURL` se câblent seuls sur `SERVICE_URL_WORDPRESS`.

### A.3 — Déployer

Cliquer **Deploy**. Coolify build l'image (thème `rtb` + plugins Polylang/onass-live-edit),
lance MariaDB, branche le proxy + SSL.

### A.4 — Installer le contenu

- **Site clé en main (démo RTB)** → **section C** (import base + médias) — recommandé.
- **Site vierge** → ouvrir le domaine, faire l'install WordPress, activer le thème `rtb`
  (le seed remplit articles/émissions/antennes/régions) puis configurer Polylang (**section D**).

### A.5 — Premier accès admin (sans shell)

Sur Coolify on n'a pas toujours de terminal sous la main. Le mu-plugin **RTB Admin Recovery**
permet de (ré)initialiser le mot de passe admin via une URL secrète :

1. Coolify → variables d'env du service `wordpress` → ajouter
   `RTB_RECOVERY_TOKEN=<chaîne aléatoire ≥ 24 caractères>` → **Redeploy**.
2. Visiter `https://<domaine>/?rtb-recover=<le-même-token>` → le login + mot de passe s'affichent.
3. Se connecter, changer le mot de passe (Utilisateurs → Profil).
4. **Supprimer** `RTB_RECOVERY_TOKEN` dans Coolify → **Redeploy** (désactive la recovery).

> Sécurité : la recovery exige HTTPS, un token ≥ 24 car., compare en temps constant et
> temporise les tentatives invalides. Sans token configuré, elle renvoie 404.

### A.6 — Domaine définitif

Après bascule sur `www.rtb.bf`, faire le `search-replace` des URLs (cf. **C.3**).

---

## B. Hébergement mutualisé (cPanel)

### B.1 — Préparer un tarball propre

```bash
cd /chemin/vers/rtb-wordpress
tar --exclude='.git' \
    --exclude='wp-config.php' \
    --exclude='.wp-admin-pass' \
    --exclude='wp-content/uploads' \
    --exclude='setup' \
    --exclude='linkedin' \
    -czf rtb-deploy.tgz .
```

### B.2 — Côté hébergeur

1. **Créer la base MySQL** (cPanel → MySQL Databases) : DB `rtb_prod`, user dédié,
   mot de passe fort, **ALL PRIVILEGES**.
2. **Upload + Extract** de `rtb-deploy.tgz` dans `public_html/`.
3. **Créer `wp-config.php`** sur le serveur (depuis `wp-config-sample.php`) :
   ```php
   define('DB_NAME',     'utilisateur_rtb_prod');
   define('DB_USER',     'utilisateur_rtb_user');
   define('DB_PASSWORD', '<mot-de-passe-fort>');
   define('DB_HOST',     'localhost');
   // + coller des salts neufs : https://api.wordpress.org/secret-key/1.1/salt/
   define('DISALLOW_FILE_EDIT', true);
   define('WP_ENVIRONMENT_TYPE', 'production');
   ```
4. **Installer le contenu** : section **C** (import) ou install vierge + activation thème.
5. **Permaliens** : Réglages → Permaliens → `/%postname%/` → Enregistrer (régénère le `.htaccess`).

---

## C. Contenu de démonstration (base + médias livrés)

Le dossier `setup/` contient le site complet prêt à l'emploi :

| Fichier | Contenu |
|---|---|
| `setup/rtb-database.sql.gz` | base complète : 15 articles, 27 émissions (vraies vidéos YouTube), 5 antennes, 4 stations, 13 régions, 11 pages, 6 langues, traductions |
| `setup/rtb-uploads.tar.gz` | médias (images sideloadées en pièces jointes) |

### C.1 — Importer les médias

```bash
# à la racine du site (où vit wp-content/)
tar -xzf setup/rtb-uploads.tar.gz -C wp-content/
# Docker : docker compose cp setup/rtb-uploads.tar.gz wordpress:/tmp/ puis extraire dans le conteneur
```

### C.2 — Importer la base

```bash
gunzip -c setup/rtb-database.sql.gz | wp db import -
# ou, sans WP-CLI :
gunzip -c setup/rtb-database.sql.gz | mysql -u USER -p NOM_BASE
# Docker : docker compose exec -T db sh -c 'mysql -urtb -p"$MARIADB_PASSWORD" rtb_wp' < <(gunzip -c setup/rtb-database.sql.gz)
```

### C.3 — Adapter les URLs au domaine de prod

La base de démo référence `http://localhost:8083`. Remplacer par le vrai domaine :

```bash
wp search-replace 'http://localhost:8083' 'https://www.rtb.bf' --all-tables --skip-columns=guid
wp cache flush
wp rewrite flush
```

Identifiants admin de démo : `rtb_admin` / `Rtb@2026!` → **à changer immédiatement** :
```bash
wp user update rtb_admin --user_pass='<nouveau-mot-de-passe-fort>'
```

---

## D. Configuration Polylang (si install vierge)

Si vous repartez de zéro (sans importer la base de démo), après activation du thème :

1. **Langues → Ajouter** les 6 langues : Français (par défaut), English, Mooré,
   Dioula (Jula), Fulfuldé (Fula), Gulmancéma.
2. **Langues → Réglages** : associer les contenus existants à *Français*.
3. **Langues → Traductions des chaînes** : traduire les libellés d'interface.

> Avec l'import de la section C, tout ceci est **déjà fait** — rien à refaire.

---

## E. Après mise en ligne — vérifications

- [ ] Page d'accueil = `front-page.php` (hero « Le Direct », ticker, à la une, antennes…)
- [ ] Permaliens propres : `/emissions/…`, `/programme/…`, `/chaine/…`, `/region/…`
- [ ] **Écran de connexion sur `/login`** (nécessite `mod_rewrite` + `.htaccess` ; activé d'office sur l'image Apache/Coolify). `wp-login.php` reste accessible en secours.
- [ ] Page **404** : renvoie bien le statut HTTP 404 et propose recherche + accès rapides + derniers contenus
- [ ] Recherche plein écran fonctionnelle (articles + émissions)
- [ ] Sélecteur de langue + mode sombre OK
- [ ] Replays YouTube se chargent au clic
- [ ] Bandeau cookies présent, non bloquant
- [ ] HTTPS forcé, `wp-admin` en HTTPS
- [ ] Mot de passe admin de démo changé
- [ ] `WP_DEBUG` désactivé

---

## F. Sauvegarde / mise à jour

```bash
# Sauvegarde
wp db export backup-$(date +%F).sql
tar -czf uploads-$(date +%F).tar.gz wp-content/uploads

# Mise à jour de l'image Docker (nouvelle version du thème)
docker compose up -d --build wordpress
```

---

*Déploiement RTB — ONASS Groupe — contact : jerome.o@digitek-consulting.com*
