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

**Videos in a case** — the *Videos* section of a case takes one or more videos: a YouTube/Vimeo link, an uploaded MP4/WebM file, or a video from your Video library (members-only library videos stay locked for visitors without access). They play on the case page after the images.

**Videos inside an article or case text** — in any rich-text editor, click the **Video** button (film icon) in the toolbar, choose the source (link, upload or library), add an optional title, caption and cover image. The video appears exactly where you placed it. In newsletters the same block becomes a linked thumbnail, because email programs cannot play videos.

### Comments (Audience › Comments)
Signed-in members can discuss OCT cases, articles and videos. On a case the discussion is folded behind **Show discussion** with a spoiler warning, so visitors read it after working through the case.

- **Approval** — by default each member’s **first** comment waits for approval; once approved, their later comments appear immediately. Comments with many links or a *blocked word* are always held. Change this in **Comment settings** (button on the Comments page): approve everything, approve first comments only, or publish immediately.
- **Moderation queue** — tabs *Awaiting approval*, *Reported*, *Published*, *Spam*, *Hidden*. Approve, reply as the team, pin to the top, hide or mark as spam — one at a time or in bulk. The menu badge shows how many comments wait.
- **On the page** — editors and administrators also see *Approve / Pin / Hide* links under each comment and can close the discussion on that page.
- **Reports** — members can report a comment (spam, abuse, patient-identifiable information, misleading medical information). After **three reports** from different members the comment is hidden until a moderator reviews it.
- **Members** can reply (one level of replies keeps threads readable), mark comments as *Helpful*, edit their comment for 15 minutes and delete it. They get an email when someone replies, with a one-click link to stop these emails (also in *My profile*).
- **Closing a discussion** — switch off *Allow comments* in the Publishing box of a case, article or video. Existing comments stay visible.
- **Privacy** — comments are included in a member’s data export. When an account is deleted, its comments are deleted; a comment that already has replies is kept as “[This comment was removed.]” so the thread still makes sense.
- Editors have the *Moderate comments* permission by default (System › Roles & permissions).

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
- **About page: video and photo gallery** — open *Design › Pages › About*, **Add section** → **Featured video** (a library video, a YouTube/Vimeo link or an uploaded file) or **Gallery** (pick images from the media library with captions, or show an existing photo album; grid, masonry or justified layout). Drag the section to where you want it. A sample of each is included in a fresh installation.
- **Reusable blocks** are sections you edit once and place on many pages (e.g. a newsletter banner).

### Banners & sliders (Design › Banners & sliders)
Image banners — a single banner or a slider with several slides — for the **homepage**, the **OCT cases**, **Videos**, **Articles**, **Courses** and **Webinars** pages, **every course page**, **Photo galleries** and **Membership**.

1. **New banner** → give it a name, choose **Where it appears**, the height (compact / standard / tall), slide or fade transition, autoplay and its speed, arrows and dots.
2. Add **slides**: a wide image (about 2400 × 900 px), optionally a separate **mobile image** (about 1080 × 1350 px), alt text, and optional small heading, title, text and button.
3. **Link**: a page on your site (`/courses/oct-basics`) or any external address (`https://…`), optionally opening in a new tab. Without a button label the whole slide is clickable.
4. **Schedule**: *Show from* / *Show until* dates let you prepare a campaign in advance; switched-off or expired slides are hidden automatically.
5. Drag slides to reorder. The *Clicks* column shows how often each slide was clicked.

One banner per location is shown (the first active one). You can also place a banner anywhere with the **Banner slider** section of the page builder.

### Navigation menus
Header, header buttons, three footer columns and legal links. Drag to reorder; header items can have dropdowns. Pick a destination from the list or type any URL.

### Theme & branding / Header & footer
Colours (with an accessibility contrast check and ready-made presets), heading and body fonts, text size, corner rounding, button shape, spacing, header and footer styles, logo, favicon and default social image — all with a live preview before saving.

## Library
- **Media library** — upload many files at once (metadata such as GPS/EXIF is removed automatically; responsive WebP versions are created). Add alt text for accessibility and Google Images. **Replace file** swaps an image everywhere it is used. The details page shows where each file is used.
- **Topics & diseases** — the backbone of filters, topic pages (e.g. `/topics/central-serous-chorioretinopathy`) and related content. Featured topics appear on the homepage and in search.

## Audience
- **Users & members** — invite team members and give them a role. *Roles & permissions* (System) lets you fine-tune what each role may do.
  - *Super administrator* — everything. *Administrator* — everything except roles. *Editor* — all content including courses and webinars, menus, SEO, newsletter, enquiries, and read-only sales. *Author* — own cases/articles/videos/questions, can publish. *Contributor* — drafts for review. *Member* — public account only.
- **Newsletter subscribers** — import/export CSV; people who unsubscribed are never re-added.
- **Email campaigns** — write, **Send test**, then **Send now** or **Schedule**. Sending is throttled (Settings › Email) to respect your host’s limits; the report shows opens and clicks.
- **Enquiries** — messages from the contact form. **Reply** sends an email and records it in the notes.

## Photo galleries (Content › Photo galleries)
Albums on one subject — e.g. “Pachychoroid spectrum”, “Fundus autofluorescence” or “ESCRS 2026”. They appear at `/galleries` (linked under **OCT cases › Image galleries** in the main menu) and each album has its own page.
1. **Gallery details** — title, short summary, optional introduction, topics, cover image, who can see it (everyone / registered / paying members), and the **layout**: *justified rows* (keeps every image’s shape — best for mixed scans), *masonry columns* or *even square grid*. Switch on **Dark background** for OCT and angiography.
2. **Images tab** — **Upload images** adds many photos at once (location data is removed and web-optimised versions are created); **Add from media library** reuses existing images. Drag rows to reorder, click the star to choose the cover, and edit a caption per image (otherwise the media-library caption is used). Add alt text in the media library for accessibility and Google Images.
3. Visitors open any photo full screen with arrows/swipe, zoom, captions and a thumbnail strip; each photo has its own shareable link (`…/galleries/album#photo-3`).
4. To show an album inside any page, add the **Image gallery** section in the page builder and choose *Show a photo gallery*. The **Photo galleries** section shows album cards (e.g. on the homepage).

## Learning

### Courses (Learning › Courses)
1. **Course details** — title, subtitle, summary, learning outcomes, description, optional introduction video (YouTube/Vimeo), questions & answers, cover image, level and topics.
2. **Price & access** — enter the price in euros (e.g. `49` or `49.50`); `0` makes the course free (an account is needed so progress can be saved). **Included in membership** gives members access for as long as their membership is active; everyone else can buy the course for lifetime access.
3. **Lessons tab** — **Add lesson** opens a side panel: title, section, duration, *free preview* (anyone can watch), video and lesson notes, plus downloadable files from the media library. Drag rows to reorder.
   - Video for paid lessons: use **Vimeo** with *Hide from Vimeo* and *domain-level privacy* (only plays on your website), or an **unlisted YouTube** video. The link is only sent to people who have access, but no web video can be made completely copy-proof.
   - Downloads are stored in the public media folder: anyone who obtains the exact file link can open it. Do not upload highly confidential material.
   - **Quiz** (inside the lesson editor) — add questions with 2–6 answers each; mark one correct answer (single best answer) or several (“select all that apply”), add an explanation and set the pass mark. Learners must pass the quiz to complete the lesson; they see their score, the correct answers and the explanations, and can retry.
4. **Sections tab** — group lessons into modules (“Foundations”, “Fluid”…).
5. **Students tab** — who is enrolled, how (purchase, membership, free, staff), their progress, completion and certificate. **Give access** enrols anyone for free (e.g. a colleague or a prize winner); the trophy button issues a certificate manually.

### Certificates (Learning › Certificates)
When a learner completes every lesson of a course (and passes every lesson quiz), a **certificate of completion** is issued automatically: they receive it by email as a PDF and can download it from *My learning*. Each certificate has an ID (e.g. `OC-7K3F-9Q2M`) and a public verification page (`/certificates/OC-7K3F-9Q2M`) that employers or training programmes can check.
- Per course: *Award a certificate* and the *hours* shown (default: total lesson time) — in the course’s **Certificate** box.
- **Certificate design** (button on the Certificates list): signatory name and title, signature image (PNG), the wording, and **Preview sample PDF**. Your logo (PNG/JPEG) from Theme & branding is used.
- **Revoke** marks a certificate as revoked on its verification page (reversible).

### Webinars (Learning › Webinars)
- Set the **date and time** (in the site time zone shown under the field), duration and platform, and paste the **Zoom / YouTube Live / Teams link**. The link is stored encrypted and never shown publicly: registrants get a personal join button that opens 30 minutes before the start.
- **Tickets** — price (`0` = free), *free for members*, an optional maximum number of participants, and *registration open*.
- Registrants automatically receive a confirmation with a **calendar invitation**, a reminder **the day before** and **one hour before** (the cron job must be active).
- **Registrations tab** — list, CSV export, add a participant, cancel a place.
- **Registrants › Email all registrants** sends an update (e.g. slides, a changed time). After the event, paste the **recording link** (unlisted YouTube / private Vimeo) and use **Registrants › Email recording link**.

## Sales

### Payment settings (Sales › Payment settings) — first-time setup
1. Create an account at **stripe.com** (business details, bank account for payouts). Stripe handles cards, Apple Pay, Google Pay and local European methods (SEPA, iDEAL, Bancontact…), sends receipts and invoices, and deals with 3-D Secure.
2. In Stripe, open **Developers › API keys** and copy the **secret key**. Start with the **test** key (`sk_test_…`).
3. Paste it here, switch on **Accept payments on the website**, save, then click **Test connection** and **Set up webhook automatically** (your site must be online for this).
4. Try a purchase with the test card **4242 4242 4242 4242** (any future date, any CVC). Check that the order appears under *Sales › Orders* and that the course unlocks.
5. When everything works, repeat steps 2–3 with the **live** key (`sk_live_…`). The website notices the change of mode and creates customers, products and prices again in live mode.
6. In Stripe, also turn on: **Settings › Emails › Successful payments** (receipts) and **Refunds**; **Billing › Customer portal** (allow cancellation and invoice history); **Billing › Revenue recovery** (automatic retries and card-update emails for failed renewals).

**VAT.** Prices include VAT by default. If you sell to consumers in several EU countries you normally need to register for the EU **One-Stop-Shop (OSS)**; then activate **Stripe Tax**, add your registrations there, and switch on *Calculate VAT automatically*. Please confirm your obligations with an accountant — the website cannot decide them for you. Review the **Terms of sale** and **Refund policy** pages (templates are included) before going live.

### Prices in several currencies
Every course, webinar ticket and membership plan has a **main price**; add **Prices in other currencies** (e.g. £85 and $109 next to €99) in the same box. Choose which currencies visitors can pick under *Payment settings › Currencies visitors can choose*. Visitors see prices in their own currency — detected from their country (Cloudflare or the GeoLite database) or chosen with the currency switcher on price boxes and in the footer — and pay in that currency. Items without a price in the visitor’s currency are sold in the main currency. Stripe converts and pays out in your bank currency.

### Membership plans (Sales › Membership plans)
Create e.g. a monthly and a yearly plan: price, billing period, optional free-trial days, the benefits shown on the pricing card, and *Most popular*. Changing the price later applies to new members; existing members keep their price unless you change it in Stripe. To stop selling a plan, switch off *Available for new members* (existing members keep it).

What membership unlocks:
- every item whose **Who can see this** is set to **Paying members** (cases, articles, videos, exam questions),
- every course marked **Included in membership**,
- a free place at every webinar marked **Free for members**, plus their recordings.

### Orders (Sales › Orders)
Every course purchase, webinar ticket and membership payment, with VAT, billing country and the customer’s VAT number. **Refund** returns money through Stripe (full or partial); a full refund of a course or ticket also removes the access it bought. **Export CSV (accounting)** gives your accountant everything in one file.

### Members (Sales › Members)
Active memberships, kept in sync with Stripe. Members manage their own card, invoices and cancellation under **Account › Billing**. You can also **cancel at period end**, **cancel immediately** or **resume** from here.

## Insights
- **Analytics** — page views, visits, sources, devices, countries, most visited pages, popular cases (with reveal and correct-answer rates), video engagement, on-site searches (including searches with no results — great ideas for new content) and subscriber growth. Export to CSV.
- **Google** — GA4 and Search Console figures once connected.

## SEO
SEO settings (title template, verification codes, robots), **Redirects**, the **404 monitor** (turn broken links into redirects in one click) and the **Search dictionary** (abbreviations such as CSR ⇄ central serous chorioretinopathy used by site search).

## System
Site settings, email, analytics, roles, **Activity log** (who changed what), **Backups** and **Health & maintenance** (requirements, cron status, caches, search index, maintenance mode, removing sample content).
