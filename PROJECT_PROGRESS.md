# PROJECT_PROGRESS.md — Ophthalmology Education Platform

> Persistent build log. If a session is interrupted, resume from the last ✅ checkpoint below.

## Stack (verified)
- Laravel 13.x, PHP 8.3+, Filament 5.10 (Livewire 4), Tailwind CSS 4, Vite (build-time only)
- MariaDB 10.11 locally (target: MySQL 8 / MariaDB 10.6+ on DirectAdmin)
- No Node.js, Redis, Docker or daemons at runtime. Cron (`schedule:run`) for queues/newsletters/backups.

## Interpretation of scope
- Phase-1 exclusions honoured (payments, paid memberships, course sales/LMS, live webinars, paid content gating).
  Architecture hooks: `access_level` on all content + `ContentAccess` service; "register interest" signups for future courses/webinars.

## Checkpoints
- [x] Stage 0 — Scaffold, packages, schema migrations
