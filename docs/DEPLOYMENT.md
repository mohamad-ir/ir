# Installing on DirectAdmin shared hosting

This guide takes you from the downloaded package to a live website. Allow about 30 minutes. No SSH, Node.js or command line is needed.

---

## 1. What you need

| Requirement | Where to check / change it in DirectAdmin |
|---|---|
| **PHP 8.3 or newer** | *Account Manager › Select PHP version* (or *PHP Version Selector*) |
| PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `intl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `zip`, and `gd` **or** `imagick` | same screen, *Extensions* tab — tick any that are missing |
| PHP options (recommended): `upload_max_filesize = 64M`, `post_max_size = 64M`, `memory_limit = 256M`, `max_execution_time = 120` | same screen, *Options* tab |
| **MySQL 8 or MariaDB 10.6+** database | *Account Manager › MySQL Management* |
| An email mailbox (e.g. `noreply@yourdomain.com`) | *E-mail Manager › E-mail Accounts* |
| SSL certificate | *Account Manager › SSL Certificates* → *Free & automatic certificate from Let’s Encrypt* |

The installer checks all of these and tells you exactly what is missing.

---

## 2. Create the database

1. Open **MySQL Management → Create new database**.
2. Enter a database name (e.g. `octacademy`) and a database user, and let DirectAdmin generate a strong password.
3. **Write down** the full database name, user name and password — DirectAdmin prefixes them with your account name, e.g. `jsmith_octacademy`.

---

## 3. Upload the files

The package `oct-academy-<version>.zip` (e.g. `oct-academy-1.5.0.zip`) contains:

```
oct-academy/    ← the application (must NOT be inside public_html)
public_html/    ← the public files
docs/           ← these guides
```

Your domain lives in `/home/USERNAME/domains/YOURDOMAIN/`, which already contains a `public_html` folder.

1. Open **File Manager** and go to `domains/YOURDOMAIN/`.
2. **Upload** the zip file there and choose **Extract**. You now have `domains/YOURDOMAIN/oct-academy/` next to `public_html/`, plus a second `public_html` folder from the package.
   - If DirectAdmin extracts into a sub-folder, move `oct-academy` so it sits directly in `domains/YOURDOMAIN/`.
3. Delete the default files inside the domain’s existing `public_html` (e.g. `index.html`, `logo.png`).
4. Move **the contents** of the package’s `public_html` folder (including the hidden `.htaccess`) into the domain’s `public_html`.
   *Tip: enable “show hidden files” in File Manager so `.htaccess` is moved too.*

The final layout must look like this:

```
domains/YOURDOMAIN/
├── oct-academy/          (app, vendor, storage, .env …)
└── public_html/
    ├── index.php
    ├── .htaccess
    ├── favicon.svg
    ├── build/  css/  js/  fonts/  icons/
    └── media/            (your uploaded images will be stored here)
```

Check the download first (optional): the package's SHA-256 checksum is listed in `release/SHA256SUMS` of the project; on a computer, `shasum -a 256 oct-academy-1.5.0.zip` (macOS/Linux) or `certutil -hashfile oct-academy-1.5.0.zip SHA256` (Windows) must print the same value.

> **Alternative (if your host lets you change the document root):** keep everything inside `oct-academy/` and point the domain’s document root to `oct-academy/public`. Then you do not need the package’s `public_html` folder.

> **Trying it on your own computer (Laravel Herd, Valet, Laragon…):** use only the `oct-academy` folder as the site (its `public` folder is the web root), create an empty MySQL/MariaDB database, open the site and follow the installer. Do not create `.env` yourself — it is created automatically on the first visit.

### Folder permissions
Folders should be `755` and files `644` (DirectAdmin’s default). These folders must be writable by PHP: `oct-academy/storage` (and everything inside), `oct-academy/bootstrap/cache`, `public_html/media`, and the file `oct-academy/.env` once it exists.

### About `.env` (passwords and keys)
- `oct-academy/.env` holds the database password, the application key and mail/payment credentials. The package does **not** contain one: it is created automatically on the first visit from `.env.example`, with a new random application key and permissions `640` (readable only by your account), and completed by the installer.
- It lives in `oct-academy/`, **outside** `public_html`, so it can never be downloaded. Check after installing: `https://YOURDOMAIN/.env` must show the site’s “page not found” page. (`oct-academy/` also contains an `.htaccess` that refuses all web access, in case the folder is ever placed inside `public_html` by mistake.)
- Never upload a `.env` from another site, never put it inside `public_html`, and keep a copy only in a safe place (it is needed to restore backups: the application key decrypts stored secrets).
- When updating, **keep** the existing `.env` (the update steps below never replace it).

---

## 4. Run the web installer

1. Visit `https://YOURDOMAIN/` — you are redirected to **/install**.
2. **Requirements** — fix anything marked ✗ in DirectAdmin and reload.
3. **Website & database** — enter the site name, the address (`https://yourdomain.com`), database host `localhost`, port `3306`, and the database name/user/password from step 2. The installer writes `.env`, creates all tables and the starting pages, menus, topics and search dictionary.
4. **Administrator** — enter your name, email and a strong password. Leave *Install sample content* ticked to see the site populated with example OCT cases (you can remove them later in one click).
5. **Done** — the installer disables itself permanently.

> Run the installer straight after uploading. Until it is finished, anyone who knows the address could start it.

---

## 5. Add the cron job (important)

Scheduled newsletters, automatic backups and data clean-up need one cron job.

1. **Advanced Features › Cron Jobs → Create cron job**
2. Minute `*`, Hour `*`, Day `*`, Month `*`, Weekday `*`
3. Command (adjust the user name, domain and PHP path):

```
/usr/local/bin/php /home/USERNAME/domains/YOURDOMAIN/oct-academy/artisan schedule:run >> /dev/null 2>&1
```

The exact line for your server is shown on the installer’s last page and under **System › Health & maintenance**. On some servers PHP 8.3 is at `/usr/local/php83/bin/php` — DirectAdmin’s *Select PHP version* page shows the path.

**No cron access?** In *System › Health & maintenance* generate a web-cron address and call it every minute from a free service such as cron-job.org.

Within a few minutes *System › Health* shows “Scheduled tasks: last run … ago”.

---

## 6. Secure and configure (dashboard)

Sign in at `https://YOURDOMAIN/admin`. The dashboard shows a **launch checklist**:

1. **Two-factor authentication** — you are asked to set it up at first sign-in with an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password…). **Save the recovery codes** somewhere safe.
2. **Settings › Email & newsletter** — choose *SMTP mailbox*, host `mail.YOURDOMAIN`, port `465`, encryption *SSL*, your mailbox address and password. Use **Send test email**.
3. **Settings › Site settings** — site name, contact address for enquiry notifications, doctor profile and professional photo, social profiles.
4. **Settings › Theme & branding** — logo, favicon, colours and fonts (live preview).
5. **Insights › Google** — optional, see section 8.
6. **System › Health & maintenance** — when your own content is ready, click **Remove sample content**.

### Recommended `.env` hardening
Edit `oct-academy/.env` in File Manager:

- `APP_DEBUG=false` and `APP_ENV=production` (set by the installer)
- `ADMIN_PATH=studio` — moves the dashboard to `/studio`, reducing automated login attempts
- After SSL works, uncomment the three “force HTTPS” lines in `public_html/.htaccess`

---

## 7. Search engines

1. In **Google Search Console** add your domain, choose the *HTML tag* method, and paste only the `content="…"` value into **SEO › SEO settings › Google verification**.
2. Submit the sitemap: `https://YOURDOMAIN/sitemap.xml`.
3. Each case, article and video has its own SEO panel (title, description, social image, noindex) with a Google preview.

---

## 8. Optional: Google Analytics 4 & Search Console reports in the dashboard

The website already records privacy-friendly statistics without cookies. To also see GA4 and Search Console data in **Insights › Google**:

1. **GA4 tag** — in Google Analytics create a *Web data stream* and copy the Measurement ID (`G-…`) into **Settings › Analytics & Google**. The tag only loads after a visitor accepts cookies.
2. **Service account** (for the reports):
   1. Go to `console.cloud.google.com` → create a project.
   2. *APIs & Services › Library* → enable **Google Analytics Data API** and **Google Search Console API**.
   3. *IAM & Admin › Service accounts* → *Create service account* → *Keys › Add key › JSON* — a file downloads.
   4. In **GA4 › Admin › Property access management** add the service-account email as **Viewer**. Copy the numeric **Property ID** (Admin › Property details).
   5. In **Search Console › Settings › Users and permissions** add the same email (Restricted is enough).
   6. In **Settings › Analytics & Google** paste the whole JSON file contents, the Property ID, and the Search Console property (`sc-domain:yourdomain.com` or `https://yourdomain.com/`). Click **Test Google connection**.
3. **Countries** — upload the free *GeoLite2-Country.mmdb* (maxmind.com, free account) in the same settings page, unless your site runs behind Cloudflare.

---

## 9. Optional: selling membership, courses and webinar tickets (Stripe)

Payments use **Stripe Checkout**: Stripe hosts the payment page, so card data never touches your hosting, and cards, Apple Pay, Google Pay, SEPA Direct Debit, iDEAL, Bancontact and more appear automatically for buyers in Europe and elsewhere. No extra software is needed on the server.

1. Create a Stripe account at stripe.com and complete the business verification (Stripe needs a legal entity and bank account in a country it supports).
2. Dashboard: **Sales › Payment settings** → paste the **test secret key** (`sk_test_…`), switch on *Accept payments*, **Save**, **Test connection**, then **Set up webhook automatically**. The webhook address is `https://YOURDOMAIN/stripe/webhook`.
3. Create plans under **Sales › Membership plans** and set prices on courses/webinars. Buy something with the test card `4242 4242 4242 4242`. Check *Sales › Orders* and that access is granted.
4. Switch to the **live** key (`sk_live_…`) and click **Set up webhook automatically** again (test and live webhooks are separate).
5. In Stripe, enable customer emails (receipts, refunds), the **Customer portal** (cancellation, invoices, card update) and **Revenue recovery** (retries for failed renewals).
6. VAT: prices are VAT-inclusive by default. For EU consumers you will usually need the OSS scheme; activate **Stripe Tax** and switch on *Calculate VAT automatically*. Confirm with your accountant.
7. Review the **Terms of sale** and **Refund policy** pages (templates) with an adviser.

The cron job (section 5) sends webinar reminders and queued emails; without it these are delayed.

Webinars themselves run on **Zoom, YouTube Live, Teams or Google Meet** — paste the meeting link into the webinar. Shared hosting is not suitable for streaming live video, and the website does not try to: it handles the event page, registration and payment, calendar invites, reminders, the private join link and the recording.

## 10. Backups

**System › Backups** creates a zip with the full database (`database.sql`) and the media library, stored privately in `oct-academy/storage/app/backups`. Set a daily or weekly schedule (needs the cron job) and **download a copy regularly**.

To restore: super administrators can click **Restore** on any backup (a safety backup is made first). Without access to the dashboard, import `database.sql` with phpMyAdmin (*MySQL Management › phpMyAdmin*) and copy the `media/` folder into `public_html/media`.

DirectAdmin’s own *Create/Restore Backups* tool is a good additional safety net.

---

## 11. Updating to a new version

1. **System › Backups → Create backup now**, and download it.
2. **System › Health & maintenance → Enable maintenance mode** (keep the bypass link).
3. Upload the new package and replace the contents of `oct-academy/` **except** `.env` and `storage/`, and replace `public_html/build`, `css`, `js`, `fonts`, `icons`, `favicon.svg` and `index.php` with the ones from the package’s `public_html` folder (never delete `public_html/media`). Copy any other new folder found in the package’s `public_html` as well.
4. Open **System › Health & maintenance** (`/admin/system/health`). If database updates are pending, click **Apply database updates**, then **Clear all caches** and **Optimise for speed**. (With SSH you can instead run `php artisan migrate --force && php artisan optimize`.)
5. Bring the site back online.

---

### Upgrading from 1.0 to 1.1 (membership, courses, webinars)
Follow the steps above. **Apply database updates** adds the new tables and, on an existing site, also: adds *Courses / Webinars / Membership* to the main menu and footer, creates *Terms of sale* and *Refund policy* template pages, turns the old “courses coming soon” page into a draft (the real course catalogue now lives at `/courses`) and replaces the homepage “coming soon” courses section with live course and webinar sections. Nothing else on your pages is changed. Then add the new paragraph about payments to your privacy policy (see the template in a fresh installation).

### Upgrading from 1.1 to 1.2 (galleries, quizzes, certificates, currencies)
Same steps. **Apply database updates** adds the new tables, gives editors/authors the gallery permissions and adds *Image galleries* under *OCT cases* in the main menu and to the footer. Certificates PDFs are generated on the server with Dompdf (pure PHP — nothing to install).

### Upgrading from 1.2 to 1.3 (comments, banners, videos in content)
Same steps. **Apply database updates** adds the comment and banner tables and gives editors the *Moderate comments* permission. Comments are switched on for cases, articles and videos, with each member’s first comment held for approval — review **Audience › Comments › Comment settings** before announcing the feature, and add a sentence about comments to your privacy policy (comments are public, kept until deleted, and removed with the account). No banners are created on an existing site; add them under **Design › Banners & sliders**.

### Upgrading from 1.6 to 1.7 (roles, payments and security fixes)
Same steps. **Apply database updates** adds two permissions: *Sign in to the dashboard* (given to the five built-in team roles, so nothing changes for them) and *Free access to paid and members-only content* (given to nobody). Custom roles you created earlier stay outside the dashboard until you tick *Sign in to the dashboard* for them in **System › Roles & permissions**. Built-in team members can still sign in before the update is applied.

Also in 1.7 (no action needed unless noted):
- **Stripe**: open **Sales › Payment settings** and click **Set up webhook automatically** once, so the endpoint also receives `charge.dispute.closed` (a lost chargeback now ends the access it paid for). Membership payments are linked to their payment so they can be refunded from **Orders**; out-of-order Stripe events can no longer lock out paying members or keep access after a cancellation.
- **Backups** can be downloaded by super administrators only (archives contain the application key and password hashes).
- **Publish dates** and campaign schedules are now entered in the site time zone (Settings › Site), like webinars and popups; dates already saved are unchanged.
- Links in menus, buttons and blocks must be web addresses, site paths, `mailto:` or `tel:` links; anything else is shown as `#` until corrected.

### Upgrading from 1.5 to 1.6 (image quality)
Same steps. **Apply database updates** adds three columns to the media library. Existing images are then re-optimised automatically in the background by the cron job (about ten images a minute; the website keeps working with the old copies meanwhile). Originals are not re-encoded: only their privacy metadata is removed. With SSH you can do it in one go with `php artisan media:optimize`. Installing the PHP **Imagick** extension is recommended (better resizing, CMYK and 16-bit images) but not required; WebP files are written by GD/libwebp in both cases. On hosts with Imagick, this version also fixes the removal of EXIF/GPS data from images and profile photos (earlier uploads are cleaned during the re-optimisation).

### Upgrading from 1.4.x to 1.5 (dashboard usability, career stages)
Same steps (from 1.4.0, the 1.4.1 notes below also apply). **Apply database updates** adds one table: the career stages members choose from become a list managed in **Audience › Career stages**. Existing members keep their choice (the stored values are unchanged) and four stages are added for an international audience. Nothing else needs configuring. The dashboard now has an **English | فارسی** switch in the top bar; Persian users of the right-to-left layout will see English content (titles, dates, sentences) in the correct order.

### Upgrading from 1.4.0 to 1.4.1 (installer fix)
Same steps; there are **no database updates** and no new settings — all content, members, settings, uploads and the existing `.env` stay exactly as they are. The release fixes the first-run setup of new installations (see Troubleshooting) and makes *Settings › General › Require two-factor authentication* take effect immediately even after **Optimise for speed** (before, a change only applied after the caches were cleared). It is safe to apply to running sites. After uploading: **Clear all caches** and **Optimise for speed** (System › Health & maintenance), then check that the page shows version 1.4.1. Recommended once: in File Manager set the permissions of `oct-academy/.env` to `640` (new installations get this automatically; older ones were created with `644`).

### Upgrading from 1.3 to 1.4 (dark mode, profile photos, site identity, Persian dashboard, installable app, popups)
Same steps — make sure the new **`public_html/icons`** folder is copied (default browser/app icons; without it the installable app cannot start offline). **Apply database updates** runs three updates:
- each member’s light/dark preference and each team member’s dashboard language;
- the **Popups & announcements** tables; Editors get the new *Manage popups* permission, and the old *Header & footer › Announcement bar* (text, link, on/off) becomes an announcement bar in **Design › Popups & announcements**.

Then:
1. **Design › Theme & branding › Site identity** — the site name and tagline now live here (moved from Settings › General). Upload a **square site icon** (PNG, ≥ 512 px): browser, iPhone/Android and app icons are created from it. If your logo is an image, upload a version for dark backgrounds under *Light & dark mode*.
2. **Installable app** — on by default, needs HTTPS (System › Health shows a warning otherwise). Follow the checklist below on staging before going live.
3. **Popup settings** (Design › Popups & announcements) — check the site-wide limits.
4. Privacy policy — add a sentence about profile photos (see the template in a fresh installation).
5. **Behind a proxy or CDN other than Cloudflare?** The application now trusts forwarded visitor IP addresses only from the same server/private network and from Cloudflare (so nobody can fake their IP to get around sign-in limits). If your host puts another proxy in front of the site and every visitor shows the same IP in the dashboard, add its address to `.env`: `TRUSTED_PROXIES=203.0.113.10,PRIVATE_SUBNETS` (or `*` if every request really comes through the proxy), then **Clear all caches**.

The dashboard can be shown in Persian per team member (top-right menu); the public website and all emails stay in English. Persian fonts are included in the package.

### Installable app (PWA) — staging and go-live checklist
Browsers only install apps and run the offline worker on **HTTPS** (or `localhost`). On staging:
1. Give staging a real certificate: DirectAdmin › SSL Certificates › *Let’s Encrypt*, then set `APP_URL=https://staging.yourdomain…` in `.env` and **Clear all caches**. Self-signed certificates do not work for installation.
2. Open **System › Health** — the *Installable app (PWA)* row must be green.
3. Desktop Chrome/Edge: open the site, DevTools › *Application* › *Manifest* (no errors) and *Service workers* (status *activated*); the install icon appears in the address bar. Lighthouse › *PWA/Best practices* should pass.
4. Android (Chrome): visit 3 pages → the suggestion card appears → *Install*. Open the app from the home screen: it starts full screen with the launch screen. Turn on airplane mode: pages you read appear, others show the offline page.
5. iPhone/iPad (Safari): Share › *Add to Home Screen*; check the icon, the name, the launch screen and full-screen start.
6. Sign in on the phone, open *My learning*, sign out, go offline: no account page must be available offline.
7. Update test: change any theme colour and save; reopen the installed app → *“A new version is available”* → *Refresh*.
8. To switch the app off in an emergency: Theme & branding › Site identity › untick *Let visitors install the website as an app* — browsers remove the worker and its caches on their next visit.
The worker is served by Laravel at `/sw.js` (never cached by browsers or proxies); if a CDN/Cloudflare is used, exclude `/sw.js` and `/manifest.webmanifest` from edge caching.

## 12. Go-live checklist

Tick these off before announcing the site:

- [ ] PHP 8.3+, required extensions and recommended options (installer *Requirements* step all ✓)
- [ ] `oct-academy/` sits **next to** `public_html/`, not inside it; `public_html` contains `index.php`, `.htaccess`, `favicon.svg`, `build/ css/ js/ fonts/ icons/ media/`
- [ ] Installer finished; `https://YOURDOMAIN/install` now shows “page not found”
- [ ] `https://YOURDOMAIN/.env` shows “page not found”; `oct-academy/.env` has permissions `640` or `600`
- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…` (with **https**)
- [ ] SSL certificate active; the three “force HTTPS” lines in `public_html/.htaccess` enabled
- [ ] Cron job added (section 5) — System › Health shows the scheduler as running
- [ ] Two-factor authentication set up for every team member; recovery codes saved
- [ ] Email: *Send test email* arrives (also check the spam folder)
- [ ] Site identity: logo(s), square site icon, site name; privacy policy reviewed (profile photos, comments, analytics, payments if used)
- [ ] Stripe (if used): one test purchase in test mode, then live keys and webhook (section 9)
- [ ] Behind a proxy/CDN other than Cloudflare: `TRUSTED_PROXIES` set (see *Upgrading from 1.3 to 1.4*)
- [ ] Installable app: checklist above passed on a phone
- [ ] System › Health: everything green; **Optimise for speed** clicked
- [ ] First backup created and downloaded; automatic backups scheduled
- [ ] Sample content removed when your own content is ready

## 13. Troubleshooting

| Problem | Fix |
|---|---|
| **`/install` shows a 500 error “database.sqlite does not exist”** (versions up to 1.4.0, rare) | The first visit left a `.env` that contains only `APP_KEY`. Use 1.4.1 or later (it repairs such a `.env` automatically and the installer no longer needs a database before it is configured). With an older package: delete `oct-academy/.env` and open `/install` again. |
| **500 error / blank page** | Check `oct-academy/storage/logs/laravel-YYYY-MM-DD.log`. Most often a folder is not writable or PHP < 8.3. |
| **“No application encryption key”** | `.env` is not writable; give it permission `644` and reload — a key is generated automatically. |
| **Styles missing** | `public_html/build` was not copied, or `.htaccess` is missing (hidden file). |
| **Images do not upload** | Increase `upload_max_filesize`/`post_max_size`; check that `public_html/media` is writable. |
| **Emails not arriving** | Use *Send test email* in Settings › Email; check spam; prefer port 465/SSL with a real mailbox. |
| **Stuck in maintenance mode** | Delete `oct-academy/storage/framework/down` in File Manager. |
| **Lost the authenticator phone** | Use a recovery code on the sign-in screen. Another super administrator can reset your 2FA in *Users*. With SSH: `php artisan cms:admin you@example.com --reset-2fa`. |
| **Forgot password** | Use *Forgot password* on the sign-in page, or with SSH: `php artisan cms:admin you@example.com`. |
| **Payment completed but no access** | Check *Sales › Payment settings*: the webhook secret must be set (use *Set up webhook automatically*). In Stripe › Developers › Webhooks you can see failed deliveries and resend them. Access is also granted when the buyer returns to the site after paying. |
| **“We could not start the payment”** | Click *Test connection* in Payment settings; the error message from Stripe is shown there (wrong key, account not activated…). |
| **No webinar reminders** | Reminders and emails are sent by the cron job — check *System › Health*. |
| **Newsletters not sending** | The cron job is missing — see *System › Health*. While the campaign page is open, sending also progresses in the browser. |

### Useful commands (only if you have SSH)

```
php artisan cms:install --demo        # install without the web installer
php artisan cms:admin email@example.com   # create a super admin / reset a password
php artisan cms:reindex               # rebuild search index and sitemap
php artisan media:optimize            # re-optimise images made by an older version (--all for every image)
php artisan cms:demo --remove         # remove sample content
php artisan optimize                  # cache config/routes/views for speed
```
