#!/bin/bash

# Exit on fail
set -e

# Wait for database connection (simple check, could be more robust)
# In production, the platform usually handles readiness checks or restarts
# But for now, we just proceed.

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
