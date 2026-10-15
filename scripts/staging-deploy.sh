#!/usr/bin/env bash
# Manual staging deploy for vatsim-event-hub.
#
# Builds the current checkout of `main`, runs staging migrations, and
# (re)starts `php artisan serve` behind a Cloudflare quick tunnel. Run from
# the repo root. See STAGING.md for background.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

export MAMBA_ROOT_PREFIX="${MAMBA_ROOT_PREFIX:-$HOME/.local/micromamba}"
export PATH="$HOME/.local/micromamba/envs/php/bin:$HOME/.local/bin:$PATH"

# A real OS environment variable always wins over .env.staging (Laravel's env()
# helper checks getenv() first). If APP_URL/APP_ENV were ever exported or
# inline-assigned in the shell that launched `php artisan serve` (e.g. during
# manual debugging), that stale value gets inherited by the long-lived server
# process and survives every future `pkill`+restart of this script, silently
# ignoring whatever APP_URL is set to in .env.staging. Strip them here so each
# deploy always picks up .env.staging fresh, regardless of the caller's shell.
unset APP_URL APP_ENV

if [ ! -f .env.staging ]; then
  echo ".env.staging is missing. Copy .env.staging.example and fill in APP_KEY/APP_URL/VATSIM creds first." >&2
  exit 1
fi

echo "==> composer install"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> npm ci && build"
npm ci
npm run build

echo "==> migrate (staging DB)"
php artisan migrate --force --env=staging

echo "==> restarting php artisan serve"
pkill -f "artisan serve --env=staging" 2>/dev/null || true
sleep 1
mkdir -p storage/staging
PHPRC="$(pwd)/php.ini" nohup php artisan serve --env=staging --host=127.0.0.1 --port=8010 --no-reload > storage/staging/serve.log 2>&1 &
disown
sleep 2
curl -sf -o /dev/null http://127.0.0.1:8010/ && echo "local app is responding on 127.0.0.1:8010"

echo "==> restarting cloudflared quick tunnel"
pkill -f "cloudflared tunnel --url http://127.0.0.1:8010" 2>/dev/null || true
sleep 1
nohup cloudflared tunnel --url http://127.0.0.1:8010 --no-autoupdate > storage/staging/tunnel.log 2>&1 &
disown
sleep 8

echo "==> tunnel URL (also logged in storage/staging/tunnel.log):"
grep -o 'https://[a-z0-9-]*\.trycloudflare\.com' storage/staging/tunnel.log | tail -1

cat <<'EOF'

NOTE: a Cloudflare *quick* tunnel (no account) gets a new random
trycloudflare.com URL every time it restarts. After the first deploy, update
APP_URL in .env.staging to match, then re-run this script once more so Vite
asset/Ziggy route URLs are generated correctly, or switch to a named tunnel
(requires a free Cloudflare account) for a stable URL across restarts.
EOF
