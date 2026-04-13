# docker-entrypoint.sh - Docker entrypoint script for PHP container

#!/bin/sh
set -e

# Ensure directories exist
mkdir -p /var/run/nginx /var/cache/nginx /var/log/nginx

# Fix permissions
chown -R www-data:www-data /var/run/nginx /var/cache/nginx /var/log/nginx /run/nginx /var/www

# Execute the main command
exec php-fpm