#!/usr/bin/env bash
set -euo pipefail

# Laravel Forge zero-downtime deployment script for SiteForge.
# Copy this into your Forge site's Deployment Script field, or keep the
# default Forge script — the stub package.json makes npm steps no-ops.

$CREATE_RELEASE()

cd "$FORGE_RELEASE_DIRECTORY"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
