# syntax=docker/dockerfile:1.7
#
# RTB — Radiodiffusion Télévision du Burkina
# Image de production. Le cœur WordPress vient de l'image de base ;
# on n'ajoute que wp-content (thème rtb + plugins). Le thème n'a PAS
# d'étape de build (CSS écrit à la main, Font Awesome auto-hébergé).

FROM wordpress:php8.3-apache

# poppler-utils → pdftotext : extraction du texte des articles-documents (PDF) de rtb.bf
# (comptes rendus du Conseil des ministres, communiqués…) pour les afficher en clair.
RUN apt-get update \
    && apt-get install -y --no-install-recommends poppler-utils \
    && rm -rf /var/lib/apt/lists/*

# Thème + plugins (onass-live-edit, polylang)
COPY --chown=www-data:www-data wp-content/ /var/www/html/wp-content/

# .htaccess (règle /login + bloc WordPress)
COPY --chown=www-data:www-data .htaccess /var/www/html/.htaccess

# Réglages wp-config supplémentaires (sécurité, HTTPS derrière proxy)
# injectés dans le template wp-config-docker.php utilisé par l'entrypoint.
COPY docker/extra-config.php /usr/src/wordpress/wp-config-extra.php
RUN sed -i "/wp-settings\.php/i require_once ABSPATH . 'wp-config-extra.php';" \
    /usr/src/wordpress/wp-config-docker.php

# Dossier uploads + permissions
RUN mkdir -p /var/www/html/wp-content/uploads \
    && chown -R www-data:www-data /var/www/html/wp-content

# Réécriture d'URL Apache (permaliens jolis)
RUN a2enmod rewrite
