#!/bin/sh
set -e

cd /var/www

mkdir -p storage/framework/cache/data
mkdir -p storage/framework/views
mkdir -p storage/framework/sessions
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ ! -d "vendor" ]; then
    echo "Vendor directory not found, installing dependencies..."
    composer install --no-dev --optimize-autoloader
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan vendor:publish --force --tag=livewire:assets
php artisan optimize
php artisan storage:link

# php artisan queue:work --daemon --tries=3 --timeout=300 &

exec "$@"