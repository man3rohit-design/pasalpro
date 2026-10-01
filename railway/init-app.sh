#!/usr/bin/env sh

set -eu

# The Railway filesystem is ephemeral, so restore the sample product photos
# included with this project on each deployment.
mkdir -p storage/app/public/products
cp database/seeders/images/products/*.jpg storage/app/public/products/

php artisan storage:link --force
php artisan migrate --force

# Populate the initial demonstration data only for a brand-new database.
if ! php artisan tinker --execute="exit(\App\Models\User::query()->exists() ? 0 : 1);"; then
    php artisan db:seed --force
fi

php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
