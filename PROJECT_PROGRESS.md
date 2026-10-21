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
- ✅ Stage 5 — Settings pages (site, doctor, theme & branding w/ preview, header/footer, SEO, email, analytics/Google, security), dashboard widgets, analytics page, Google reports, backups, system tools (health, pending-migration runner), demo-content removal
- ✅ Stage 6 — Web installer (+ `bootstrap/prepare-env.php`), scheduler/cron + web-cron fallback, console commands (`cms:install`, `cms:admin`, `cms:reindex`, `cms:demo`)
- ✅ Stage 7 — Automated tests (Pest: 97 tests / 261 assertions passing), browser QA (desktop + mobile), installer tested end-to-end twice
- ✅ Stage 8 — Release package (`release/oct-academy-1.0.0.zip`, built by `scripts/build-release.sh`), docs/DEPLOYMENT.md, docs/ADMIN_GUIDE.md, README, final report (below)

## Verification log (2026-10-21)
- `php artisan test` → 97 passed, 261 assertions (MariaDB test DB).
- Browser QA: home, case page (quiz submit → answer reveal, lightbox, compare slider), video playback + analytics events, builder live preview of unsaved edits, theme preview of unsaved colours, MFA enrolment enforced on fresh install.
- Release zip extracted into a DirectAdmin-style `domains/site/{oct-academy,public_html}` layout and installed through the browser installer: MEDIA_ROOT auto-set, media served from `public_html/media` (WebP 200), admin login + Filament assets 200, `schedule:run` heartbeat recorded, CLI backup included all 70 media files.

## Known limitations (not claimed as done)
- GA4 Data API / Search Console reporting: implemented (service-account JWT) but **not tested against live Google accounts** — no credentials available in the build environment.
- Visitor country stats need a GeoLite2-Country.mmdb upload (or Cloudflare `CF-IPCountry`).
- Demo images are synthetic (generated, labelled) and the doctor profile/CV is placeholder text — replace before launch or run `cms:demo --remove` / the dashboard "Remove demo content" action.
- The web installer is reachable by anyone until installation completes — install immediately after upload (it locks itself afterwards).
- Self-hosted video on shared hosting is limited by disk/bandwidth; YouTube/Vimeo embeds are recommended.
- Phase-1 exclusions (payments, paid memberships, course sales, live webinars, paid gating) are intentionally not implemented; `access_level` + `ContentAccess` and "register interest" lists are the extension points.

