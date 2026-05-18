#!/usr/bin/env bash

# Ensure SQLite database directory exists and is writable by www-data
DB_PATH=$(/usr/bin/php -r "echo env('DB_DATABASE', database_path('database.sqlite'));" 2>/dev/null || echo "")
if [ -z "$DB_PATH" ]; then
  DB_PATH="/data/database.sqlite"
fi
mkdir -p "$(dirname "$DB_PATH")"
touch "$DB_PATH"
chown www-data:www-data "$(dirname "$DB_PATH")" 2>/dev/null || true
chown www-data:www-data "$DB_PATH" 2>/dev/null || true
chmod 775 "$(dirname "$DB_PATH")" 2>/dev/null || true
chmod 664 "$DB_PATH" 2>/dev/null || true

# Run pending migrations
/usr/bin/php /var/www/html/artisan migrate --force --no-ansi -q 2>/dev/null || true

# Seed database if users table is empty (idempotent)
USER_COUNT=$(/usr/bin/php /var/www/html/artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
  /usr/bin/php /var/www/html/artisan db:seed --force --no-ansi -q 2>/dev/null || true
fi

# Cache config, routes, views
/usr/bin/php /var/www/html/artisan config:cache --no-ansi -q
/usr/bin/php /var/www/html/artisan route:cache --no-ansi -q
/usr/bin/php /var/www/html/artisan view:cache --no-ansi -q
