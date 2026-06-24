# Architecture

## Vue d'ensemble
- **Thème `rtb`** : présentation (templates PHP + Alpine.js + CSS design-tokens). Pas de page builder.
- **Extensions `rtb-*`** : logique métier isolée en **POO** (autoload PSR-4, services à responsabilité unique).
- **`onass-live-edit`** : édition inline via le Customizer (helpers `onass_mod()`, `onass_cs_setting()`).
- **mu-plugins** : infrastructure (cache de page, `/login` propre, récupération admin).

## Thème `rtb`
```
themes/rtb/
├── functions.php          Enqueue (Alpine local, Font Awesome local), favicon, hints, helpers
├── theme.json             Palette + typographies + layout
├── front-page.php, single.php, single-rtb_emission.php, archive.php, page-*.php, search.php, 404.php
├── parts/                 header, footer, card, sidebar…
├── inc/
│   ├── cpt.php            CPT rtb_antenne / rtb_station / rtb_emission + taxonomies
│   ├── customizer.php     Sections live-edit (identité, contact, ticker, hero, radio, À propos)
│   ├── data.php           Helpers données + rtb_cdnize() (CDN images, idempotent)
│   ├── import.php         Synchro contenu depuis rtb.bf (REST) + extraction PDF + cron
│   ├── admin-meta.php     Meta-box Direct/Radio
│   └── i18n.php           Helpers multilingues
└── assets/                css/rtb.css · js/rtb.js · js/alpine.min.js · fontawesome/ · img/
```

## Extension `rtb-search`
Moteur de pertinence qui remplace la recherche WordPress native.
```
src/
├── Plugin.php                 Câblage des services
├── Support/Normalizer.php     Mots-vides, pluriels, accents
├── Search/Engine.php          Ranking (posts_search + posts_orderby), recherche programmatique
├── Search/Results.php         Mise en forme d'un résultat
├── Search/InstantController.php  Endpoint AJAX (au fil de la frappe)
├── Analytics/Store.php        Table des recherches (tendances / récentes)
└── Frontend/Assets.php        Enqueue (versionné par filemtime)
```
- Pertinence : titre pondéré > corps ; à score égal, le plus récent.
- Instantané : debounce, navigation clavier, skeletons, transition en fondu.

## Extension `rtb-chat` (assistant 100 % local)
Entièrement local : pipeline de **responders** + recherche par mots-clés + résumé extractif.
```
src/
├── Plugin.php
├── Assistant.php             Pipeline : premier responder qui « handles() » répond
├── Message.php               Message analysé (flat, keywords)
├── Knowledge.php             Accès au contenu (recherche, récents, comptages, cartes)
├── Summarizer.php            Résumé extractif (scoring de phrases)
├── Reply.php / Renderer.php  Réponse structurée → HTML sûr
├── Nlp/Normalizer.php        Normalisation, mots-vides, élision
├── Nlp/Lexicon.php           Vocabulaire appris (correction stricte optionnelle)
├── Responders/               SmallTalk, Help, Goodbye, DateTime, Contact, Programme, Count, Live, Summary, Search, Fallback
├── Learning/Learner.php      Apprentissage du lexique (partagé WP-CLI + admin)
├── Ajax/ChatController.php    Endpoint AJAX
├── Console/LearnCommand.php   `wp rtb-chat learn`
├── Admin/Page.php             Outils → Assistant RTB
└── Frontend/Assets.php + AssistantPage.php   Widget flottant + page /assistant
```

## Extension `rtb-seo`
```
src/
├── Plugin.php                Câblage + retrait des doublons natifs/thème
├── Context.php               Résout titre/description/image/URL/type de la requête
├── HeadMeta.php              description, canonical, robots, Open Graph, Twitter
├── Hreflang.php              Alternates multilingues (Polylang)
└── Schema/JsonLd.php         @graph : WebSite+SearchAction, Organization, NewsArticle, VideoObject, BreadcrumbList
```

## Principes transverses
- **Sécurité** : sortie échappée systématiquement, nonces + capacités sur l'admin/AJAX, `$wpdb->prepare()`.
- **Performance** : cache de page (mu-plugin), assets locaux versionnés (`filemtime`), images via CDN, préchargement LCP.
- **Autonomie** : aucune dépendance CDN pour le JS/CSS critique ; les appels à Polylang sont protégés par `function_exists()`.
