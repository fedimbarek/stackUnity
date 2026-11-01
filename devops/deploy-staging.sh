#!/usr/bin/env bash
set -euo pipefail

# Lancer depuis /srv/stackunity sur le serveur de staging.
# Preconditions : checkout main, .env correct, PHP/Composer/Node/NPM,
# MySQL et serveur web provisionnes. Sauvegarde DB recommandee.

if [ "$(git branch --show-current)" != 'main' ]; then
  echo 'Refus de deployer : la branche du serveur doit etre main.' >&2
  exit 1
fi

if [ ! -f .env ]; then
  echo 'Fichier .env staging absent : arret du deploiement.' >&2
  exit 1
fi

git pull --ff-only origin main
composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan queue:restart

echo 'Deploiement staging termine.'
