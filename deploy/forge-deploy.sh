#!/usr/bin/env bash
set -euo pipefail

# LEGACY Laravel Forge deployment script.
# Prefer self-managed: deploy/deploy.sh + docs/hosting/caddy-nginx.md
# Copy this into Forge's Deployment Script field only if you still use Forge.

$CREATE_RELEASE()

cd "$FORGE_RELEASE_DIRECTORY"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
