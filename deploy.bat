git pull origin main

php8 artisan optimize:clear

composer8 install --no-interaction --prefer-dist --optimize-autoloader

php8 artisan package:discover

php8 artisan config:cache
php8 artisan route:cache
php8 artisan view:cache
php8 artisan event:cache
