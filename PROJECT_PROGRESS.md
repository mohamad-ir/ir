# PROJECT_PROGRESS.md — Ophthalmology Education Platform

> Persistent build log. If a session is interrupted, resume from the last ✅ checkpoint below.

## Stack (verified)
- Laravel 13.x, PHP 8.3+, Filament 5.10 (Livewire 4), Tailwind CSS 4, Vite (build-time only)
- MariaDB 10.11 locally (target: MySQL 8 / MariaDB 10.6+ on DirectAdmin)
- No Node.js, Redis, Docker or daemons at runtime. Cron (`schedule:run`) for newsletters/backups; web-cron fallback.

## Scope decisions
- Phase-1 exclusions honoured (payments, paid memberships, course sales/LMS, live webinars, paid gating).
  Hooks for later: `access_level` on content + `App\Services\Access\ContentAccess`; "register interest" lists (courses/webinars) via newsletter interests + InterestBlock.
- Uploads stored in `public/media` (no storage:link symlink needed on shared hosting).
- First-party cookieless analytics (daily-rotating visitor hash) + optional GA4 (consent banner) + GA4/Search Console reporting via service account.

## Local dev notes
- MariaDB started with `mysqld_safe &` (apt-installed in container). DB `oculus` / user `oculus` / pw `secret`; test DB `oculus_test`.
- `.env` has APP_INSTALLED=true. Admin: admin@example.com / Admin12345! (local only). `security.require_staff_mfa` set false locally.
- Gotcha: Filament injects closure params BY NAME — use `$query`, `$record`, `$state`, `$get`, `$set`, `$data` (never `$q`, `$r`).
- Demo images: `python3 -I scripts/generate_demo_images.py database/seeders/assets` (synthetic, labelled).

## Checkpoints
- ✅ Stage 0 — Scaffold, packages, schema (all migrations)
- ✅ Stage 1 — Models, concerns (publishing/scheduling, revisions, SEO, search index, activity log, slug redirects), enums, policies, permissions
- ✅ Stage 2 — Public site: design system, layout, header/footer/search palette, all content pages, case viewer (zoom/contrast/invert, compare slider, quiz reveal), video player tracking, exam practice, search with synonyms, newsletter double opt-in, contact, auth/account (GDPR export/delete), sitemap/robots/RSS, schema.org, 404→redirects
- ✅ Stage 3 — Page builder: 24 block types, reusable blocks, live responsive preview (desktop/tablet/mobile) of unsaved changes
- ✅ Stage 4 — Admin resources: cases, articles, videos, exam topics, questions, pages, reusable blocks, menus, media library (bulk upload, replace, usage), topics, categories, modalities, tags, CV, users, roles matrix, subscribers (CSV import/export), campaigns (test/schedule/send with progress), enquiries (reply), redirects, 404 monitor, search dictionary, activity log
- ⏳ Stage 5 — Settings pages (site, doctor, theme & branding w/ preview, header/footer, SEO, email, analytics/Google, security), dashboard widgets, analytics page, Google reports, backups, system tools, demo-content removal
- ⏳ Stage 6 — Installer, scheduler/cron, console commands
- ⏳ Stage 7 — Automated tests (Pest), browser QA, performance
- ⏳ Stage 8 — Release package + DirectAdmin docs + final report
