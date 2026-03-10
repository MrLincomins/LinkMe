#!/bin/sh

echo "Waiting for vendor/autoload.php..."
while [ ! -f /var/www/vendor/autoload.php ]; do
  sleep 2
done
echo "vendor ready, starting queue worker"

exec "$@"