# PROJECT_PROGRESS.md — Ophthalmology Education Platform

> Persistent build log. If a session is interrupted, resume from the last ✅ checkpoint below.

## Stack (verified)
- Laravel 13.x, PHP 8.3+, Filament 5.10 (Livewire 4), Tailwind CSS 4, Vite (build-time only)
- MariaDB 10.11 locally (target: MySQL 8 / MariaDB 10.6+ on DirectAdmin)
- No Node.js, Redis, Docker or daemons at runtime. Cron (`schedule:run`) for newsletters/backups; web-cron fallback.

## Scope decisions
- v1.0: Phase-1 exclusions honoured. v1.1 (owner request): paid membership, course sales and live webinars added.
- Payments: Stripe Checkout (hosted page, PCI SAQ-A) via a small REST client (`App\Services\Payments\StripeClient`, no SDK). Audience is international/EU → Stripe; VAT-inclusive prices, optional Stripe Tax, VAT-ID collection, EU withdrawal-waiver consent.
- Live webinars run on Zoom/YouTube Live/Teams; the site handles registration, payment, ICS invites, reminders, private join link, recordings.
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

- ✅ Stage 9 (v1.1) — Membership, courses, webinars
  - Schema: membership_plans, subscriptions, orders, payment_events, courses, course_sections, lessons, enrollments, lesson_progress, webinars, webinar_registrations, users.stripe_customer_id
  - Stripe: Checkout (subscription + one-time), webhook with HMAC verification and idempotent event log, success-page fulfilment, Customer Portal, refunds, cancellation, test→live reference reset, one-click webhook creation; parses classic and 2025 ("basil") object shapes
  - Access: new `members` access level; ContentAccess handles courses (purchase/free/admin permanent, membership while active) and webinars
  - Public: /membership, /courses, /courses/{slug}, lesson player with progress, /webinars, webinar page with countdown/ICS/join/recording, /account/billing, checkout result pages, page-builder blocks (courses, webinars, pricing)
  - Admin: Learning (courses + lessons/sections/students, webinars + registrations/messages), Sales (orders + refunds + CSV, members, plans, payment settings), sales widget, permissions sales.view / sales.manage
  - Upgrade migration for 1.0 sites (menus, legal pages, old /courses placeholder → draft)

- ✅ Stage 10 (v1.2) — Photo galleries, lesson quizzes, certificates, multi-currency prices
  - Galleries: galleries + gallery_items, /galleries + album pages (justified/masonry/grid), full-screen viewer (keyboard, swipe, zoom, thumbnails, #photo-N deep links), access levels, ImageGallery schema, image sitemap, search, admin with bulk upload/library picker/reorder/cover, page-builder album mode + “Photo galleries” block, menu link under OCT cases
  - Quizzes: lessons.quiz JSON, lesson_quiz_attempts, Livewire LessonQuiz (answers graded server-side, correct answers never sent before submit), pass mark gates lesson completion
  - Certificates: certificates table, auto-issue on completion, Dompdf PDF (A4 landscape), public verification /certificates/{code}, email with PDF, revoke/reinstate, design settings with sample preview
  - Currencies: prices list on courses/webinars/plans, Currency::current() (cookie → country → main), switcher, checkout + orders in chosen currency, one Stripe price per currency per plan, offers per currency in JSON-LD

- ✅ Stage 11 (v1.3) — Comments, banner sliders, videos in content
  - Comments: comments + comment_likes + comment_reports; CommentService (moderation modes first/all/none, link & blocked-word holds, rate limits, duplicate check, honeypot, access & verified-email checks), Livewire discussion (sort, load more, one-level replies, helpful votes, edit window, delete with thread-preserving placeholder, reports → auto-hide at 3, author/team badges, pinning, inline moderation), reply emails with signed opt-out + profile toggle, staff email for pending comments, admin queue with tabs/bulk actions/reply-as-team, Comment settings page, per-item “Allow comments”, GDPR export/erasure, commentCount in JSON-LD, upgrade migration (editor permission)
  - Banners: banners + banner_slides, 9 placements + page-builder block, desktop/mobile images, text overlay & position, internal/external links (new tab), schedule, autoplay/fade/slide, swipe, keyboard & reduced motion, click counting
  - Videos in content: rich-editor “Video” block (link / upload / library) expanded after sanitising, gated library videos show a locked card, emails get a linked thumbnail; case “Videos” field; page-builder Video block gained link/upload sources (and members-only library videos are now locked — previously they played for anyone)
  - About page: demo Featured video + Gallery sections; gallery block layouts reuse the album viewer

## Verification log (2026-10-22, v1.3)
- `php artisan test` → 201 passed (27 new for v1.3).
- Browser QA: guest/learner/admin comment flows (first comment pending → approved inline → staff reply → pin → helpful → edit → report), mobile layout, case spoiler fold, admin queue, banner sliders on home/courses (desktop + mobile, autoplay, arrows), About video + gallery.
- Bugs found and fixed: page-builder Video block played members-only library videos for anyone (pre-existing); video placeholders whose base64 ended in “=” were dropped because the sanitiser entity-encodes “=”.

## Verification log (2026-10-21, v1.2)
- `vendor/bin/pest` → 174 passed, 622 assertions (15 new for v1.2).
- Browser QA: gallery index/album/viewer (deep links, arrows, Esc), header menu, admin bulk upload (2 images added), quiz fail → retry → pass 100%, certificate auto-issued, verification page, PDF rendered and inspected, currency by country (GB → £, US → $), plan editor with GBP/USD prices.
- Bug fixed during QA: new courses created in code did not default to certificates on (DB default only).

## Verification log (2026-10-21, v1.1)
- `vendor/bin/pest` → 159 passed, 534 assertions (60 new: payments, learning, admin).
- Bugs found and fixed during v1.1 QA: cache unserialization of topic tree (1.0), members-only YouTube IDs leaked via thumbnail/JSON-LD/sitemap (pre-existing for "registered" level too), webinar join link would have been wiped on edit ($hidden + Filament fill), Stripe network failure caused 500 on checkout, simple-repeater state lost outcomes in the editor, payment settings unsavable when legal page missing.
- Browser QA: learner journey (sign in → enrol free course → complete → 100%), paid course buy box with consent, Stripe-unreachable handling, signed webhook to dev server → member state, billing page, member-free webinar, admin lesson creation through the slide-over.
- NOT verified: real Stripe API calls (api.stripe.com is blocked from the build environment). All Stripe requests/responses are covered by faked HTTP tests that follow Stripe's documented API; a test-mode purchase must be done once on the real server before going live (see docs/DEPLOYMENT.md §9).

## Verification log (2026-10-21, v1.0)
- `php artisan test` → 97 passed, 261 assertions (MariaDB test DB).
- Browser QA: home, case page (quiz submit → answer reveal, lightbox, compare slider), video playback + analytics events, builder live preview of unsaved edits, theme preview of unsaved colours, MFA enrolment enforced on fresh install.
- Release zip extracted into a DirectAdmin-style `domains/site/{oct-academy,public_html}` layout and installed through the browser installer: MEDIA_ROOT auto-set, media served from `public_html/media` (WebP 200), admin login + Filament assets 200, `schedule:run` heartbeat recorded, CLI backup included all 70 media files.

## Known limitations (not claimed as done)
- Comments are plain text (links auto-linked, no images or formatting) and have one level of replies. There is no automatic spam service (e.g. Akismet); spam control is approval of first comments, link/word holds, rate limits, a honeypot and member reports.
- Stripe integration is tested against faked API responses only — run one test-mode purchase on the live server before accepting real payments.
- EU VAT compliance (OSS registration, Stripe Tax) is the owner's responsibility; the site supports it but cannot decide it.
- Course videos are protected by access checks and private Vimeo/unlisted YouTube links, not DRM; lesson downloads are in the public media folder (unguessable names, but not access-controlled).
- Discount coupons are managed in Stripe, not in the dashboard. Certificates have no QR code (the verification URL and ID are printed).
- GA4 Data API / Search Console reporting: implemented (service-account JWT) but **not tested against live Google accounts** — no credentials available in the build environment.
- Visitor country stats need a GeoLite2-Country.mmdb upload (or Cloudflare `CF-IPCountry`).
- Demo images are synthetic (generated, labelled) and the doctor profile/CV is placeholder text — replace before launch or run `cms:demo --remove` / the dashboard "Remove demo content" action.
- The web installer is reachable by anyone until installation completes — install immediately after upload (it locks itself afterwards).
- Self-hosted video on shared hosting is limited by disk/bandwidth; YouTube/Vimeo embeds are recommended.
- Phase-1 exclusions (payments, paid memberships, course sales, live webinars, paid gating) are intentionally not implemented; `access_level` + `ContentAccess` and "register interest" lists are the extension points.

