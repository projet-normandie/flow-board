#!/bin/bash
set -e

echo "==> Pulling latest changes..."
git pull

echo "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "==> Installing JS dependencies..."
npm install

echo "==> Building assets..."
npm run build

echo "==> Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Updating audit database schema..."
php bin/console audit:schema:update --force

echo "==> Compiling .env for production..."
APP_ENV=prod composer dump-env prod

echo "==> Clearing and warming up cache..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "==> Update complete!"
