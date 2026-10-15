#!/bin/sh
set -eu
export PORT="${PORT:-10000}"
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"
case "$PORT" in ''|*[!0-9]*) echo 'PORT inválido.' >&2; exit 1;; esac
php deploy/validate-environment.php
printf 'User-agent: *\nDisallow: /\n' > public/robots.txt
php artisan config:cache
php artisan migrate --force --no-interaction
php artisan db:seed --class=RenderDemoSeeder --force --no-interaction
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
