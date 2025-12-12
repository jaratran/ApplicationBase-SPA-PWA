git pull origin main

composer8 install --no-interaction --prefer-dist --optimize-autoloader

php8 artisan config:cache
php8 artisan route:cache
php8 artisan view:cache
