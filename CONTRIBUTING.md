# Contribuer

Merci de votre intérêt ! Ce projet accueille les contributions (corrections, traductions,
améliorations d'accessibilité, nouvelles fonctionnalités).

## Démarrer
1. Forkez le dépôt et clonez votre fork.
2. Greffez le code sur une installation WordPress locale (voir [README](README.md#installation)).
3. Créez une branche : `git checkout -b feat/ma-fonctionnalite` (ou `fix/...`).

## Style de code
- **PHP** : standards [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/),
  PHP 8.1+, typage strict des signatures, **tout output échappé** (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- **Sécurité** : nonces sur les actions admin/AJAX, `current_user_can()` sur les actions sensibles,
  requêtes via `$wpdb->prepare()`. Aucune entrée utilisateur affichée sans échappement.
- **POO** : les extensions `rtb-*` suivent une architecture par classes (autoload PSR-4, services dédiés).
- **JS/CSS** : vanilla JS sans dépendance, CSS basé sur les design-tokens du thème (compatibles mode sombre).

## Commits
Format : `type(domaine[scope]): message`
- types : `feat`, `fix`, `enhance`, `refactor`, `chore`, `docs`, `style`, `test`, `perf`
- exemple : `feat(core[search]): filtre par type de contenu`

## Pull requests
- Une PR = une intention claire. Décrivez le quoi/pourquoi.
- Vérifiez `php -l` sur les fichiers modifiés ; testez le rendu (clair **et** sombre).
- Pas de secret, pas d'URL/identifiant de production dans le code ou les captures.

## Signaler un bug
Ouvrez une *issue* avec : étapes de reproduction, comportement attendu/observé, version PHP/WordPress,
et captures si pertinent.

## Sécurité
Pour une faille de sécurité, **n'ouvrez pas d'issue publique** : contactez les mainteneurs en privé.
