# Administrator guide

Sign in at `https://YOURDOMAIN/admin` (or the path set in `ADMIN_PATH`). The search box at the top (⌘/Ctrl + K) finds cases, articles and people; the **New** button creates content from anywhere.

## Content

### OCT cases (Content › OCT cases)
Each case has tabs that follow the teaching flow:

1. **Presentation** — age, sex, eye, presenting complaint and history.
2. **Imaging** — add images from the media library (drag to reorder), give each a label (“OCT B-scan”, “FAF”) and caption, and optionally a **before/after comparison** slider. Tick the imaging modalities.
3. **Diagnostic challenge** — a question and 2–6 answer options with one marked correct. Visitors commit to a diagnosis before the answer is revealed. Leave the options empty to show a simple “Reveal the answer” button.
4. **Answer & discussion** — diagnosis, explanation, imaging findings, differential diagnosis table, discussion and management.
5. **Teaching points & references** — clinical pearls and references. Write `[1]` in any text to link to reference 1.

The sidebar holds **Publishing** (status, publish date, who can see it, featured), **Case of the Week** (tick and choose the week — schedule several weeks ahead), topics, difficulty, tags and the cover image.

### Publishing workflow
- **Draft** → only visible in the dashboard and via **Preview**.
- **In review** → for contributors; an editor publishes it.
- **Published** with a **future date** → scheduled; it appears automatically at that time (no cron needed).
- **Archived** → removed from the site but kept.

Every save creates a **revision**. Use **History** to compare versions and restore any of them. Changing the URL of a published item automatically creates a redirect from the old address.

Articles, Videos, Exam topics and the Question bank work the same way. Videos accept YouTube or Vimeo links (title, thumbnail and duration fill in automatically) or a short uploaded MP4; add chapters and a transcript for accessibility and Google “key moments”.

### Profile & CV
Education, fellowships, appointments, publications, lectures, awards… These appear automatically wherever the page builder’s *CV section* or *Publications* blocks are used (e.g. the About page).

## Design

### Pages & page builder
Pages are built from **sections**. Click **Add section** to choose from 24 types (hero, text, automatic content feeds, Case of the Week, image & text, features, topics, key figures, call-to-action, newsletter, doctor profile, CV, publications, timeline, testimonials, logos, FAQ, featured video, gallery, exam hub, contact form, “coming soon / register interest”, spacer, reusable block).

- Drag sections to reorder, duplicate them, or hide one without deleting it.
- Each section has **Content** and **Appearance** tabs (background, spacing, width, show on desktop/mobile only, anchor link).
- The **live preview** on the right shows unsaved changes; switch between desktop, tablet and mobile.
- **Set as homepage** is in the page list’s row actions.
- **Reusable blocks** are sections you edit once and place on many pages (e.g. a newsletter banner).

### Navigation menus
Header, header buttons, three footer columns and legal links. Drag to reorder; header items can have dropdowns. Pick a destination from the list or type any URL.

### Theme & branding / Header & footer
Colours (with an accessibility contrast check and ready-made presets), heading and body fonts, text size, corner rounding, button shape, spacing, header and footer styles, logo, favicon and default social image — all with a live preview before saving.

## Library
- **Media library** — upload many files at once (metadata such as GPS/EXIF is removed automatically; responsive WebP versions are created). Add alt text for accessibility and Google Images. **Replace file** swaps an image everywhere it is used. The details page shows where each file is used.
- **Topics & diseases** — the backbone of filters, topic pages (e.g. `/topics/central-serous-chorioretinopathy`) and related content. Featured topics appear on the homepage and in search.

## Audience
- **Users & members** — invite team members and give them a role. *Roles & permissions* (System) lets you fine-tune what each role may do.
  - *Super administrator* — everything. *Administrator* — everything except roles. *Editor* — all content, menus, SEO, newsletter, enquiries. *Author* — own cases/articles/videos/questions, can publish. *Contributor* — drafts for review. *Member* — public account only.
- **Newsletter subscribers** — import/export CSV; people who unsubscribed are never re-added.
- **Email campaigns** — write, **Send test**, then **Send now** or **Schedule**. Sending is throttled (Settings › Email) to respect your host’s limits; the report shows opens and clicks.
- **Enquiries** — messages from the contact form. **Reply** sends an email and records it in the notes.

## Insights
- **Analytics** — page views, visits, sources, devices, countries, most visited pages, popular cases (with reveal and correct-answer rates), video engagement, on-site searches (including searches with no results — great ideas for new content) and subscriber growth. Export to CSV.
- **Google** — GA4 and Search Console figures once connected.

## SEO
SEO settings (title template, verification codes, robots), **Redirects**, the **404 monitor** (turn broken links into redirects in one click) and the **Search dictionary** (abbreviations such as CSR ⇄ central serous chorioretinopathy used by site search).

## System
Site settings, email, analytics, roles, **Activity log** (who changed what), **Backups** and **Health & maintenance** (requirements, cron status, caches, search index, maintenance mode, removing sample content).
