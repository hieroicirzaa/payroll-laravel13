#!/usr/bin/env sh
set -eu

cd /var/www/html

log() {
    printf '%s\n' "[payroll-entrypoint] $*"
}

# Create .env only when it does not exist yet. Values from docker-compose env_file
# still override Laravel's .env values at runtime.
if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        log "Creating .env from .env.docker"
        cp .env.docker .env
    elif [ -f .env.example ]; then
        log "Creating .env from .env.example"
        cp .env.example .env
    fi
fi

mkdir -p \
    storage/app/private/payroll \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Several services use the same image. The lock must live inside the shared
# project mount, not /tmp, because /tmp is private for each container.
# This prevents app/queue/scheduler/vite from touching vendor/ at the same time.
if [ "${SKIP_COMPOSER_INSTALL:-false}" != "true" ] && [ ! -f vendor/autoload.php ]; then
    LOCK_DIR="storage/framework/payroll-composer-install.lock"
    LOCK_OWNER=0

    while ! mkdir "$LOCK_DIR" 2>/dev/null; do
        if [ -f vendor/autoload.php ]; then
            break
        fi

        # Remove a stale lock if the previous first-run container was stopped.
        if [ -d "$LOCK_DIR" ] && find "$LOCK_DIR" -mmin +10 -print -quit 2>/dev/null | grep -q .; then
            log "Removing stale composer install lock..."
            rmdir "$LOCK_DIR" 2>/dev/null || true
            continue
        fi

        log "Waiting for another container to prepare Composer dependencies..."
        sleep 2
    done

    if [ ! -f vendor/autoload.php ]; then
        LOCK_OWNER=1
        trap 'if [ "$LOCK_OWNER" = "1" ]; then rmdir storage/framework/payroll-composer-install.lock 2>/dev/null || true; fi' EXIT INT TERM

        mkdir -p vendor
        if [ -f /opt/payroll/vendor/autoload.php ]; then
            log "Restoring Composer dependencies from image cache..."
            cp -a /opt/payroll/vendor/. vendor/
        else
            log "Installing Composer dependencies..."
            composer install --prefer-dist --no-interaction --no-progress --optimize-autoloader
        fi

        rmdir "$LOCK_DIR" 2>/dev/null || true
        LOCK_OWNER=0
        trap - EXIT INT TERM
    fi
fi

# Ensure APP_KEY is valid exactly once.
# Do not keep APP_KEY in .env.docker, because Docker env_file variables override
# Laravel's .env values. An empty APP_KEY in .env.docker causes
# MissingAppKeyException even when .env has a key.
valid_app_key() {
    grep -Eq '^APP_KEY=base64:[A-Za-z0-9+/]{43}=$' .env 2>/dev/null
}

write_app_key() {
    NEW_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    if grep -q '^APP_KEY=' .env 2>/dev/null; then
        # Replace the whole APP_KEY line. This also fixes broken lines such as
        # APP_KEY=base64:one=base64:two caused by concurrent key generation.
        awk -v key="APP_KEY=${NEW_KEY}" 'BEGIN{done=0} /^APP_KEY=/{ if(!done){ print key; done=1 } next } { print } END{ if(!done) print key }' .env > .env.tmp
        mv .env.tmp .env
    else
        printf '\nAPP_KEY=%s\n' "$NEW_KEY" >> .env
    fi
}

if [ -f .env ] && ! valid_app_key; then
    KEY_LOCK_DIR="storage/framework/payroll-app-key.lock"
    KEY_LOCK_OWNER=0

    while ! mkdir "$KEY_LOCK_DIR" 2>/dev/null; do
        if valid_app_key; then
            break
        fi

        if [ -d "$KEY_LOCK_DIR" ] && find "$KEY_LOCK_DIR" -mmin +10 -print -quit 2>/dev/null | grep -q .; then
            log "Removing stale APP_KEY lock..."
            rmdir "$KEY_LOCK_DIR" 2>/dev/null || true
            continue
        fi

        log "Waiting for another container to prepare APP_KEY..."
        sleep 1
    done

    if ! valid_app_key; then
        KEY_LOCK_OWNER=1
        trap 'if [ "$KEY_LOCK_OWNER" = "1" ]; then rmdir storage/framework/payroll-app-key.lock 2>/dev/null || true; fi' EXIT INT TERM

        log "Writing Laravel APP_KEY"
        write_app_key

        rmdir "$KEY_LOCK_DIR" 2>/dev/null || true
        KEY_LOCK_OWNER=0
        trap - EXIT INT TERM
    fi
fi

if [ -f vendor/autoload.php ]; then
    php artisan package:discover --ansi >/dev/null 2>&1 || true
fi

php artisan storage:link >/dev/null 2>&1 || true

# Wait for PostgreSQL when the service uses pgsql. This prevents artisan workers
# from crashing while PostgreSQL is still becoming ready.
if [ "${WAIT_FOR_DB:-true}" = "true" ] && [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    DB_HOST_VALUE="${DB_HOST:-postgres}"
    DB_PORT_VALUE="${DB_PORT:-5432}"
    DB_USER_VALUE="${DB_USERNAME:-postgres}"
    DB_NAME_VALUE="${DB_DATABASE:-payroll_system}"

    until pg_isready -h "$DB_HOST_VALUE" -p "$DB_PORT_VALUE" -U "$DB_USER_VALUE" -d "$DB_NAME_VALUE" >/dev/null 2>&1; do
        log "Waiting for PostgreSQL at ${DB_HOST_VALUE}:${DB_PORT_VALUE}..."
        sleep 2
    done
fi

chown -R www-data:www-data storage bootstrap/cache public/storage 2>/dev/null || true

exec "$@"
