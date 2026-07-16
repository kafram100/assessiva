#!/bin/bash
set -e

echo "Deploying application..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

# Pull the latest version of the app
# git pull origin main

# Install composer dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear the old cache
php artisan clear-compiled

# Recreate cache
php artisan optimize

# Install node modules and build assets
# npm ci
# npm run build

# Run database migrations
php artisan migrate --force

# Exit maintenance mode
php artisan up

echo "Application deployed!"
