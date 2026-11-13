#!/usr/bin/env bash
set -e

echo "Deploying Assessiva to production..."

# 1. Put application into maintenance mode
php artisan down || true

# 2. Pull latest code from master
git pull origin master

# 3. Install composer dependencies without dev packages and optimize autoloader
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 4. Install npm dependencies and build production assets
npm ci --prefer-offline --no-audit
npm run build

# 5. Run database migrations safely in production
php artisan migrate --force

# 6. Clear stale caches and cache configuration, routes, and views
php artisan optimize:clear
php artisan optimize
php artisan view:cache

# 7. Ensure storage symlink exists
php artisan storage:link || true

# 8. Restart queue workers to pick up new code
php artisan queue:restart || true

# 9. Bring application back online
php artisan up

echo "Assessiva deployment completed successfully!"
