#!/bin/bash
set -e

# Create directories if they don't exist
mkdir -p var/cache var/log var/sessions

# Set proper ownership and permissions
chown -R www-data:www-data var/cache var/log var/sessions
chmod -R 775 var/cache var/log var/sessions

# Execute the original command
exec "$@"
