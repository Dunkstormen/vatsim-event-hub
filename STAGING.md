# Staging environment

Lightweight staging for reviewing `main` outside local dev. No external
hosting account needed — it runs in the project's existing sandbox and is
exposed over a Cloudflare *quick* tunnel (`cloudflared tunnel --url`, no
Cloudflare account/signup, no cost).

## Stack

- `php artisan serve` on `127.0.0.1:8010`, backed by a dedicated
  `database/staging.sqlite` (separate from the local dev SQLite DB).
- `cloudflared tunnel --url http://127.0.0.1:8010` exposes that port at a
  random `https://*.trycloudflare.com` URL.
- Config lives in `.env.staging` (gitignored — copy `.env.staging.example`
  and fill in `APP_KEY` via `php artisan key:generate --env=staging
  --show`, plus `VATSIM_CLIENT_ID`/`VATSIM_CLIENT_SECRET` once available).

## Deploying

```sh
./scripts/staging-deploy.sh
```

Builds frontend assets, runs staging migrations, and (re)starts both
background processes. Prints the current tunnel URL at the end.

## Known limitations

- **URL is not stable.** A quick tunnel gets a new random URL every restart.
  For a stable URL across redeploys, switch to a named Cloudflare Tunnel,
  which requires a free Cloudflare account (signup decision — flag for
  board approval before creating one).
- **No VATSIM OAuth creds configured yet.** The landing page and public
  event listing work without them; VATSIM Connect login will not complete
  until real `VATSIM_CLIENT_ID`/`VATSIM_CLIENT_SECRET` are proposed via the
  Paperclip secret-proposal flow and added to `.env.staging`.
- **Lifetime tied to the sandbox.** The serve/tunnel processes run as plain
  background processes in this sandbox, not a managed service — if the
  sandbox restarts, re-run `scripts/staging-deploy.sh`.
- **No recurring spend.** Everything here is free (sandbox compute we
  already have + Cloudflare's free quick tunnel), so no board approval was
  needed for this v1.
