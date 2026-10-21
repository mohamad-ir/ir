# OCT Academy — ophthalmology education platform

A complete, database-driven website for ophthalmology education: OCT case library with interactive diagnostic challenges, Case of the Week, video library, articles, FRCS/FRCOphth question bank, topic hubs, newsletter, member accounts — and a full administration dashboard with a live visual page builder.

Built with **Laravel 13**, **Filament 5**, **Livewire 4**, **Alpine.js** and **Tailwind CSS 4**, designed for **standard DirectAdmin shared hosting** (PHP 8.3+, MySQL/MariaDB, cron — no Node.js, Redis or SSH needed in production).

| Document | For |
|---|---|
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Installing on DirectAdmin, cron, email, Google, backups, updates, troubleshooting |
| [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | Using the dashboard |
| [PROJECT_PROGRESS.md](PROJECT_PROGRESS.md) | Build log, architecture decisions, test results |

## Development

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # set DB_* and APP_INSTALLED=true
php artisan migrate --seed
php artisan cms:admin you@example.com              # create a super administrator
php artisan cms:demo                               # optional sample content
php artisan serve
```

Tests (MySQL/MariaDB database `oculus_test`, see `phpunit.xml`): `./vendor/bin/pest`

Build the upload package: `scripts/build-release.sh` → `dist/oct-academy-<version>.zip`

## Architecture overview

- `app/Models` — content models with shared concerns: scheduled publishing, revisions, SEO metadata, unified search index, activity log, automatic slug redirects.
- `app/Services/PageBuilder` — 24 section types; each has an editing schema and a Blade view in `resources/views/blocks`.
- `app/Services/Analytics` — cookieless first-party analytics (daily-rotating anonymous hash).
- `app/Services/Google` — GA4 Data API and Search Console client (service account, no SDK).
- `app/Services/Backup` — PDO database dump + media zip, restore.
- `app/Services/Access/ContentAccess` — single access decision point (public / registered), ready for paid tiers later.
- `app/Filament` — dashboard resources, settings pages, insights, system tools.

Payments, paid memberships, course sales and live webinars are intentionally not part of this phase; content already carries an `access_level`, and "register interest" lists collect demand for future courses and webinars.
