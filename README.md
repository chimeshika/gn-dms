# GN-DMS — React frontend and Laravel backend

A Grama Niladhari and Public Officers Management System with a standalone React application and a Laravel JSON API.

## Structure

```text
frontend/
  src/
    pages/           React screens: authentication, dashboard, records, letters
    components/      Shared UI, navigation, forms, officer selector
    lib/             API client, session state, data hooks, form definitions
    App.jsx          Client-side routes
    main.jsx         React entry point
    styles.css       Responsive application styles
  index.html
  vite.config.js     React build and development API proxy
  package.json
  dist/              Generated production frontend (ignored by Git)
backend/
  app/
    Http/Controllers/Api/  JSON API controllers
    Http/Middleware/       Active-account guard
    Models/                Database models
    Services/              Access scope, letters, imports, PDF generation
  routes/api.php           API endpoint definitions
  routes/web.php           Registers the API with session and CSRF middleware
  resources/views/pdf/     Server-rendered PDF templates
  database/                Migrations, reference data, demo seeders
  tests/                   Laravel integration tests
  legacy/                  Previous Blade/Filament UI, inactive reference only
  public/                  Laravel HTTP entry point
  artisan
  composer.json
```

React owns all active application screens, including administration. Laravel owns authentication, authorization, validation, persistence, file storage, imports, and PDF generation. Filament is no longer installed or registered. The old UI source is retained under `backend/legacy` and is not served.

## Requirements

- PHP 8.2+ and Composer. Enable the extensions required by Composer, including `pdo_sqlite` (or your database driver), `mbstring`, `dom`, `gd`, and `zip`.
- Node.js 20.19+ or 22.12+ compatible with Vite 7 and npm.
- Existing Sinhala/Tamil PDF layouts expect the fonts configured in `backend/config/dompdf.php` under `backend/storage/fonts`. PDF byte generation is tested; multilingual typography still needs visual review with those fonts installed.

## First-time setup

Run from the project root:

```sh
cd backend
composer run setup
```

This installs PHP dependencies, creates `backend/.env`, generates a key, creates a SQLite file if absent, runs migrations, installs React dependencies, and builds the frontend. For another database, create and configure `backend/.env` before setup. On an existing installation, preserve its application key and run `composer install` and `php artisan migrate` individually instead of regenerating the key.

Populate reference data and optional demo accounts:

```sh
php artisan db:seed
```

The existing `DatabaseSeeder` includes demo users from `database/seeders/UserSeeder.php`. Use it only for local development: the main demo account is `admin@gn.gov.lk` with password `password`. Review the seeders before running them on an existing database. New officer registrations remain pending until an administrator approves them.

Composer was downloaded locally for verification in this workspace. If it is not on PATH, use `php ../.tools/composer.phar` from `backend` for individual Composer commands; `.tools` is ignored and is not part of deployment.

## Run locally

Start Laravel from `backend`:

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Start React in another terminal from `frontend`:

```sh
npm run dev
```

Open **http://127.0.0.1:5173**. Laravel listens on port 8000; it returns API responses rather than rendering the application. Vite forwards `/api` to Laravel. The optional `frontend/.env` setting `BACKEND_URL` changes that proxy target. Keep backend secrets in `backend/.env`; do not put them in frontend environment variables.

After setup, `composer run dev` from `backend` starts both development servers. Enable required PHP extensions in your PHP configuration first. On the XAMPP installation used for verification, the extension flags `-d extension=zip -d extension=gd` were needed for PHP tests because those extensions were disabled by default.

## Application routes

- `/` — public homepage
- `/login`, `/register` — authentication and officer registration
- `/dashboard` — role-aware overview
- `/officers`, `/documents`, `/service-histories` — personnel records
- `/letters`, `/letters/batches/:id`, `/letters/:id/edit` — batches and letter editor
- `/signatories`, `/users` — administration
- `/admin` — redirects to the React dashboard for older bookmarks

Officers see their own records. Divisional/district administrators see officers within their assigned jurisdiction. User management and record deletion require the main administrator; signatory management requires a district or main administrator. Batches belong to their creator, with main-administrator access across batches. Finalized letters are read-only and download the archived PDF.

## Session authentication

`GET /api/session` starts the Laravel session and returns the current user and CSRF token. React sends the token in `X-CSRF-TOKEN` for mutations, with the session cookie. Login rotates the session token; logout invalidates it. There are no browser-stored bearer tokens. The API definitions intentionally run through Laravel's `web` middleware to retain CSRF protection and sessions. Registration and login are rate-limited.

This configuration uses a shared browser origin: Vite proxies in development, and the deployment web server must proxy `/api` in production. Separately hosted cross-origin deployments require an explicit cookie/CORS authentication configuration.

## Validation

From `frontend`:

```sh
npm test
npm run build
```

From `backend`:

```sh
composer test
```

The backend tests use an isolated in-memory SQLite database. Coverage includes guest access, login/logout, pending registration, jurisdiction restrictions, uploads/downloads, CSV import, preservation of edited drafts, idempotent finalization, and PDF output for every document type. React tests cover navigation, login/logout, registration, role-specific controls, batch creation, and read-only finalized letters.

## Security and operations

Sensitive documents and archived letter PDFs are stored on Laravel's private `local` disk and are returned only through authenticated, jurisdiction-scoped API endpoints. Do not place uploads in `backend/public` or expose the storage directory through the web server. Successful logins, officer verification, directory mutations, letter generation/editing/finalization, and deletions are recorded in `audit_logs`; passwords, tokens, and other authentication secrets are excluded.

Keep `backend/.env`, `backend/vendor`, `backend/storage/logs`, `frontend/node_modules`, and `frontend/dist` out of Git. Use safe placeholders in `.env.example` and never commit application keys, credentials, certificates, or uploaded files. For production, prefer MySQL/MariaDB or PostgreSQL with regular encrypted backups of both the database and private document storage; SQLite remains supported for local development and tests.

PDF production requires the Sinhala and Tamil font files configured in `backend/config/dompdf.php` and installed under `backend/storage/fonts`. Verify Sinhala/Tamil glyphs, bold text, wrapping, numbered lists, signatures, headers, footers, and multi-page output manually in a production-like environment because automated tests validate PDF generation but not visual typography.

## Deployment

Build the frontend with `npm ci && npm run build` inside `frontend`. Serve `frontend/dist` as static files and configure client-side route fallback to `index.html`. Forward `/api/*` to Laravel's `backend/public/index.php` while preserving the path. Route missing API endpoints to Laravel, never to the React HTML fallback. An example Nginx configuration is in `deployment/nginx.conf.example`.

Install production Composer dependencies in `backend`, configure its environment and database, run migrations, and make `backend/storage` and `backend/bootstrap/cache` writable. Keep all Laravel source, uploads, secrets, and legacy files outside the public static root. Set `APP_DEBUG=false`, the public `APP_URL`, and `SESSION_SECURE_COOKIE=true` under HTTPS.

Architecture references: [React with Vite](https://react.dev/learn/build-a-react-app-from-scratch), [Vite proxy configuration](https://vite.dev/config/server-options.html#server-proxy), and [Laravel CSRF protection](https://laravel.com/docs/12.x/csrf).
