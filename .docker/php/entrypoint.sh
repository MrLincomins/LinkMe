#!/bin/sh

composer update

php artisan migrate --force

npm install

npm run dev &

exec "$@"