#!/bin/sh
# Render (offre gratuite) n'a pas de Shell : on peut seeder au démarrage.
# Activer SEED_ON_BOOT=true pour le PREMIER déploiement seulement, puis repasser à false
# (le DatabaseSeeder crée aussi des rapports de démo, qui seraient dupliqués).
if [ "$SEED_ON_BOOT" = "true" ]; then
    echo "SEED_ON_BOOT=true : exécution de php artisan db:seed"
    php /var/www/html/artisan db:seed --force
fi
