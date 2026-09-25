# Greenfield

Greenfield is a starting point for a new SaaS application. It is a
[Laravel 13](https://laravel.com/docs/13.x) app built on the official
[Vue starter kit](https://laravel.com/docs/13.x/starter-kits) (Inertia + Vue 3 + TypeScript + Tailwind),
with authentication handled by [WorkOS AuthKit](https://workos.com/docs/user-management) and a
fully Dockerized runtime.

**What's included**

- **Authentication via WorkOS AuthKit.** Sign-up, sign-in, email verification, and password/social/SSO
  options are all handled by WorkOS. The app stores no passwords.
- **User settings.** Profile, appearance (light/dark) and account deletion come from the starter kit.
- **Admin area** (`/admin/users`). Search and browse users, grant or revoke admin access,
  and deactivate or reactivate accounts.
- **Docker runtime.** PHP 8.5 (FPM), Nginx 1.30.5 with HTTPS, PostgreSQL 18, a queue worker,
  the scheduler and a Vite dev server with hot reloading.
- **Tests.** Pest tests for the auth flow, admin features and safeguards, plus Pint, PHPStan (Larastan)
  and ESLint/formatting via GitHub Actions.

## Contents

- [Requirements](#requirements)
- [Quick start (local development)](#quick-start-local-development)
- [Configuration](#configuration)
- [HTTPS](#https)
- [Admin users](#admin-users)
- [Everyday commands](#everyday-commands)
- [Testing and code quality](#testing-and-code-quality)
- [Production deployment](#production-deployment)
- [Project layout](#project-layout)

## Requirements

- [Docker](https://docs.docker.com/get-docker/) with Docker Compose v2 (Docker Desktop on macOS/Windows).
- A [WorkOS](https://workos.com) account, used to sign in.
- _(Optional)_ [mkcert](https://github.com/FiloSottile/mkcert), for a locally-trusted HTTPS certificate.

PHP, Composer and Node do **not** need to be installed on your machine. Everything runs in containers.

## Quick start (local development)

```bash
# 1. Create your environment file
cp .env.example .env

# 2. Add your WorkOS credentials to .env (see "Configuration" below)
#    WORKOS_CLIENT_ID=client_...
#    WORKOS_API_KEY=sk_test_...
#    WORKOS_REDIRECT_URL="${APP_URL}/authenticate"
#    ADMIN_EMAILS=you@example.com

# 3. (Optional, recommended) create a trusted local HTTPS certificate
./docker/nginx/generate-dev-cert.sh

# 4. Start everything
docker compose up
```

Then open **<https://localhost>**.

The first `docker compose up` builds the images. When the `app` container starts it automatically:

1. copies `.env.example` to `.env` if you skipped step 1,
2. runs `composer install` if `vendor/` is missing,
3. generates `APP_KEY` if it is empty,
4. runs the database migrations (disable with `RUN_MIGRATIONS=false`).

The `vite` container then runs `npm install` and starts the Vite dev server, so frontend changes reload
in the browser as you save. Optionally, load some sample users to explore the admin area:

```bash
docker compose exec app php artisan db:seed
```

### Local services

| Container              | Purpose                                                         | Host port(s)        |
| ---------------------- | --------------------------------------------------------------- | ------------------- |
| `greenfield_web`       | Nginx: HTTPS, static files, proxies PHP to `app`                | `80`, `443`, `5173` |
| `greenfield_app`       | PHP 8.5 FPM running Laravel                                     | none                |
| `greenfield_db`        | PostgreSQL 18 (database `greenfield_db`)                        | `5432`              |
| `greenfield_queue`     | `php artisan queue:work`                                        | none                |
| `greenfield_scheduler` | `php artisan schedule:work`                                     | none                |
| `greenfield_vite`      | Vite dev server (reached through Nginx on port 5173 over HTTPS) | none                |

Database data is stored in the `greenfield_db_data` Docker volume, so it survives
`docker compose down` / `up`. To wipe it completely, run `docker compose down -v`.

Host ports can be changed in `.env` with `HTTP_PORT`, `HTTPS_PORT`, `VITE_PORT` and
`FORWARD_DB_PORT`. If you change `HTTPS_PORT`, include the port in `APP_URL` too.

> **Linux hosts:** set `UID` and `GID` in `.env` to the output of `id -u` and `id -g` before the first
> build, so files the containers write to the bind-mounted project stay owned by you.

## Configuration

All configuration lives in `.env` (start from `.env.example`). The most important values:

| Variable              | Description                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------- |
| `APP_URL`             | Public URL of the app, e.g. `https://localhost`                                               |
| `APP_DOMAIN`          | Hostname Nginx serves and, in production, the Let's Encrypt certificate's domain              |
| `DB_*`                | Postgres connection. `DB_HOST=db` points at the Postgres container                            |
| `WORKOS_CLIENT_ID`    | From the WorkOS dashboard → _API Keys_                                                        |
| `WORKOS_API_KEY`      | From the WorkOS dashboard → _API Keys_ (secret)                                               |
| `WORKOS_REDIRECT_URL` | Where AuthKit sends users back to. Defaults to `${APP_URL}/authenticate`                      |
| `ADMIN_EMAILS`        | Comma-separated emails that become admins when they sign in (see [Admin users](#admin-users)) |
| `RUN_MIGRATIONS`      | Run `php artisan migrate` when the `app` container starts (default `true`)                    |

The Postgres container is initialised from `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` **the first time
its volume is created**. Changing them later means updating the role in Postgres, or recreating the volume.

### Setting up WorkOS

1. In the [WorkOS dashboard](https://dashboard.workos.com), open _API Keys_ and copy the **Client ID** and
   **API Key** into `WORKOS_CLIENT_ID` and `WORKOS_API_KEY`.
2. Under _Redirects_, add your redirect URI. For local development that is
   `https://localhost/authenticate`. It must exactly match `WORKOS_REDIRECT_URL`.
3. Under _Redirects_, set the **sign-out redirect** to your app URL (e.g. `https://localhost`).
4. Enable the authentication methods you want (email + password, magic auth, Google, GitHub, SSO…)
   under _Authentication_. No code changes are needed.

After editing `.env`, restart the PHP containers so they pick up the change:
`docker compose restart app queue scheduler`.

## HTTPS

The app is served over HTTPS in every environment. Plain HTTP on port 80 redirects to HTTPS, except for
Let's Encrypt challenge requests.

**Local development.** Nginx looks for `docker/nginx/certs/localhost.pem` and
`docker/nginx/certs/localhost-key.pem`:

- **Recommended:** install [mkcert](https://github.com/FiloSottile/mkcert) (`brew install mkcert` on macOS)
  and run `./docker/nginx/generate-dev-cert.sh`. The certificate is trusted by your browser, with no warnings.
- **Without mkcert:** the same script falls back to a self-signed certificate made with `openssl`. And if no
  certificate exists at all, the `web` container generates a self-signed one automatically. Either way
  your browser will show a warning, which you can accept to continue. Accept it for
  `https://localhost:5173` as well, because that's where the Vite dev server's assets are served from.

Run `docker compose restart web` after creating or replacing a certificate. Certificates are gitignored.

**Production:** see [HTTPS in production](#https-in-production).

## Admin users

Admins can open **Users** in the sidebar (`/admin/users`) to:

- search and browse users (name, email, status, last sign-in, join date),
- grant or revoke admin access,
- deactivate or reactivate accounts. A deactivated user is signed out on their next request and cannot sign
  in again until reactivated. Their data is kept.

There are two ways to make someone an admin:

1. **`ADMIN_EMAILS` in `.env`**, which is how the first admin gets created. A user whose email is in the list is
   made an admin automatically when they sign in (including the very first sign-in):

    ```dotenv
    ADMIN_EMAILS=you@example.com,cofounder@example.com
    ```

2. **The artisan command**, for any user who has signed in at least once:

    ```bash
    docker compose exec app php artisan greenfield:admin someone@example.com
    docker compose exec app php artisan greenfield:admin someone@example.com --revoke
    ```

    Admins can also do this from the admin area.

**Safeguards**

- Users listed in `ADMIN_EMAILS` can't lose admin access or be deactivated from the UI or the command.
  Remove them from the list first.
- Removing an email from `ADMIN_EMAILS` does **not** revoke access that was already granted. That has to
  be done on purpose, in the UI or with the command.
- The last active admin can't lose admin access or be deactivated.
- Admins can't revoke their own admin access or deactivate themselves.
- Admin actions only change this app's database. They do not modify the user in WorkOS.

## Everyday commands

```bash
docker compose up -d                          # start in the background
docker compose logs -f app web                # follow logs
docker compose exec app php artisan migrate   # run migrations
docker compose exec app php artisan tinker    # REPL
docker compose exec app composer require vendor/package
docker compose exec vite npm install some-package
docker compose exec db psql -U greenfield greenfield_db
docker compose down                           # stop (data is kept)
docker compose build --no-cache               # rebuild images from scratch
```

## Testing and code quality

Tests use [Pest](https://pestphp.com) and run against an in-memory SQLite database, so they never touch
your development data. WorkOS is replaced with a fake HTTP client (`tests/Support/FakeWorkOSRequestClient.php`),
so tests need no credentials or network access.

```bash
docker compose exec app php artisan test        # run the test suite
docker compose exec app composer lint           # fix PHP code style (Pint)
docker compose exec app composer types:check    # static analysis (PHPStan / Larastan)
docker compose exec vite npm run check:fix      # lint + format frontend code
docker compose exec vite npm run types:check    # TypeScript / vue-tsc
```

Tests cover:

- **Authentication** (`tests/Feature/Auth`): the redirect to AuthKit, state validation, first-time and
  returning sign-ins, redirecting users back to the page they asked for, failed code exchanges, logging out,
  protected routes, deactivated users (blocked at sign-in and signed out mid-session) and `ADMIN_EMAILS` promotion.
- **Admin** (`tests/Feature/Admin`): access control, search, granting and revoking admin access,
  deactivating and reactivating users, every safeguard, and the `greenfield:admin` command.
- **Unit** (`tests/Unit`): `ADMIN_EMAILS` parsing and the `User` model's admin rules.

GitHub Actions (`.github/workflows/tests.yml`) runs the linters, type checks and tests on every push and
pull request, and checks that the production Docker images build.

## Production deployment

Production uses the same `compose.yaml` plus `compose.prod.yaml` in place of the development overrides:

- The PHP image (`prod` target) contains the application code, production-only Composer dependencies
  and compiled Vite assets. Opcache is tuned for immutable code.
- The Nginx image (`web-prod` target) contains the `public/` directory and compiled assets.
- Your `.env` is mounted read-only into the PHP containers. It is never baked into an image.
- On start, each PHP container caches config, routes, views and events (`php artisan optimize`), and
  `app` runs migrations (`RUN_MIGRATIONS`).
- A `certbot` container renews Let's Encrypt certificates, and Nginx picks up renewed certificates automatically.

### Deploying to a Docker host (e.g. AWS EC2)

On a server with Docker installed, with ports 80 and 443 open and DNS for your domain pointing at it:

```bash
git clone <your-repo> greenfield && cd greenfield
cp .env.example .env
```

Edit `.env` for production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.example.com
APP_DOMAIN=app.example.com
LOG_LEVEL=warning
DB_PASSWORD=<a long random password>
WORKOS_CLIENT_ID=client_...
WORKOS_API_KEY=sk_live_...
ADMIN_EMAILS=you@example.com
```

Generate an application key and put it in `.env` as `APP_KEY=...`:

```bash
docker compose -f compose.yaml -f compose.prod.yaml build
docker compose -f compose.yaml -f compose.prod.yaml run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Make sure the file is readable by the container user (`chmod 644 .env`, or `640` with a matching group),
then start the stack:

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

Add `https://app.example.com/authenticate` to the allowed redirects in your **production** WorkOS environment.

> Tip: `export COMPOSE_FILE=compose.yaml:compose.prod.yaml` on the server lets you drop the `-f` flags.

### HTTPS in production

Until a real certificate exists, Nginx serves a temporary self-signed one so it can start. Request the
Let's Encrypt certificate once the stack is running:

```bash
docker compose -f compose.yaml -f compose.prod.yaml run --rm --entrypoint certbot certbot \
    certonly --webroot -w /var/www/certbot \
    -d app.example.com --email you@example.com --agree-tos --no-eff-email

docker compose -f compose.yaml -f compose.prod.yaml exec web greenfield-reload-ssl
```

After that, the `certbot` container tries to renew twice a day, and Nginx checks for renewed certificates
every 6 hours (`NGINX_SSL_RELOAD_INTERVAL`). Production responses include an HSTS header.

**TLS terminated upstream** (an AWS load balancer, Cloudflare, etc.): you can skip certbot and either keep
Nginx's HTTPS listener (upstream → Nginx over HTTPS) or adapt `docker/nginx/templates/default.conf.template`.
Configure Laravel's trusted proxies if the app runs behind a proxy
([docs](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies)).

### Updating a deployment

```bash
git pull
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

To build in CI and deploy pre-built images instead, set `GREENFIELD_APP_IMAGE`, `GREENFIELD_WEB_IMAGE` and
`GREENFIELD_IMAGE_TAG` (e.g. to a registry path and git SHA), push the images, and on the server run
`docker compose -f compose.yaml -f compose.prod.yaml pull && ... up -d`.

### Laravel Forge and Laravel Cloud

- **Forge:** provision a server with Docker installed and deploy with the Compose commands above (for
  example, in the site's deploy script). Alternatively, use Forge's standard non-Docker PHP/Nginx
  setup. The app itself has no Docker-specific code.
- **Laravel Cloud** builds and runs Laravel apps on its own infrastructure. It doesn't use this repo's
  Docker setup. The application deploys to Cloud as normal: attach a Postgres database, set the same
  environment variables (WorkOS, `ADMIN_EMAILS`, …), and use `npm ci && npm run build` as the build command.

## Project layout

```text
app/
  Actions/Admin/          GrantAdmin, RevokeAdmin, DeactivateUser, ReactivateUser (+ safeguards)
  Actions/Auth/           SyncWorkOSUser: find/create/update local users from WorkOS sign-ins
  Console/Commands/       greenfield:admin
  Http/Controllers/Admin/ Admin area controllers
  Http/Controllers/Auth/  WorkOS AuthKit callback
  Http/Middleware/        EnsureUserIsActive (signs out deactivated users)
  Policies/UserPolicy.php Admin authorization rules
config/admin.php          ADMIN_EMAILS parsing
docker/
  php/                    PHP ini, FPM pool config, container entrypoint
  nginx/                  Nginx config, TLS scripts, dev certificates (gitignored)
resources/js/pages/admin  Admin area (Vue + Inertia)
routes/admin.php          Admin routes
Dockerfile                dev, prod, web and web-prod image targets
compose.yaml              Services shared by all environments
compose.override.yaml     Local development (loaded automatically)
compose.prod.yaml         Production
```
