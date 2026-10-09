#!/bin/bash

PROD_HOST="ryan@hellyer.kiwi"
PROD_PATH="~/www/phone.hellyer.kiwi/"
SITE_URL="https://phone.hellyer.kiwi"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

EXCLUDES=(
    --exclude='.env'
    --exclude='.env.*'
    --exclude='.git/'
    --exclude='.gitignore'
    --exclude='/.syncthing/'
    --exclude='storage/'
    --exclude='logs/'
    --exclude='database/'
    --exclude='bootstrap/cache/'
    --exclude='public/storage/'
    --exclude='public/storage'
    --exclude='node_modules/'
    --exclude='vendor/'
    --exclude='/files/'
    --exclude='/.trash/'
    --exclude='.phpunit.result.cache'
    --exclude='access.log'
)

echo "Deploying to ${PROD_HOST}:${PROD_PATH}..."
rsync -avz --delete "${EXCLUDES[@]}" \
    "${SCRIPT_DIR}/" \
    "${PROD_HOST}:${PROD_PATH}"

echo ""
echo "Running post-deploy commands..."
# php/composer on this host are only ~/.local/bin wrappers (symlinks to
# server-setup/bin/pod-exec, which podman-exec into the php-fpm container), and
# ~/.local/bin is only on PATH in a LOGIN shell. A plain `ssh host "..."` runs a
# non-login shell, so `composer` is "command not found" — hence `bash -lc`.
#
# Deploy excludes storage/, bootstrap/cache/, database/ and vendor/, so create
# them and make them group-writable by www-data (the php-fpm user, which is also
# the user pod-exec runs PHP tooling as) before composer runs.
ssh "${PROD_HOST}" "bash -lc '
    set -e
    cd ${PROD_PATH}
    mkdir -p storage/app/glide storage/framework/cache/data storage/framework/data \
             storage/framework/sessions storage/framework/views storage/logs \
             bootstrap/cache database
    sudo chown -R ryan:www-data storage bootstrap/cache database
    sudo chown ryan:www-data .
    sudo chmod 2775 .
    sudo find storage bootstrap/cache database -type d -exec chmod 2775 {} +
    composer install --no-dev --no-interaction --optimize-autoloader
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php-reload
'" </dev/null

# Image smoke test: catches a broken Glide driver (e.g. Imagick without an AVIF
# encoder) which otherwise serves 0-byte thumbnails silently. Originals live
# OUTSIDE the deployed tree, on the sshfs mount php-fpm reads at
# /var/phone-files (host ~/phone-files), not under the site directory.
echo ""
echo "Running image smoke test..."
SMOKE_SOURCE="$(ssh "${PROD_HOST}" "find ~/phone-files -type f \( -iname '*.avif' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' -o -iname '*.webp' \) -size +1k 2>/dev/null | head -n 1")"
if [ -z "${SMOKE_SOURCE}" ]; then
    echo "! Image smoke test skipped: no source image found under ~/phone-files"
else
    SMOKE_REL="${SMOKE_SOURCE#*phone-files/}"
    SMOKE_BASE="${SMOKE_REL%.*}"
    SMOKE_EXT="${SMOKE_REL##*.}"
    SMOKE_URL="${SITE_URL}/files/${SMOKE_BASE}-400x0.${SMOKE_EXT}"
    # The vhost sits behind Authelia, so an unauthenticated curl gets a 302 to
    # the auth portal rather than the image. Treat that as "skipped", not failed.
    SMOKE_RESULT="$(curl -sk -o /dev/null -w '%{http_code} %{size_download}' "${SMOKE_URL}" 2>/dev/null || echo '000 0')"
    SMOKE_CODE="${SMOKE_RESULT%% *}"
    SMOKE_BYTES="${SMOKE_RESULT##* }"
    if [ "${SMOKE_CODE}" = "302" ]; then
        echo "! Image smoke test skipped: ${SMOKE_URL} is behind Authelia (HTTP 302)"
    elif [ "${SMOKE_CODE}" = "200" ] && [ "${SMOKE_BYTES:-0}" -gt 0 ]; then
        echo "OK Image smoke test passed (${SMOKE_BYTES} bytes: ${SMOKE_URL})"
    else
        echo "x Image smoke test FAILED: ${SMOKE_URL} returned HTTP ${SMOKE_CODE}, ${SMOKE_BYTES} bytes" >&2
        exit 1
    fi
fi

echo ""
echo "✓ Deploy complete!"
