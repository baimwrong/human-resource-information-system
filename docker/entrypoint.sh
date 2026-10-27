#!/bin/sh
set -e

echo "==> Preparing HRIS Enterprise production environment..."

# Ensure storage link exists
php artisan storage:link --force || true

# Run pending database migrations
php artisan migrate --force

# Warm up production caches for instant HTTP throughput
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Environment ready. Starting Supervisor services (PHP-FPM, Nginx, Queue Workers, Scheduler)..."

exec "$@"
