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

The package `oct-academy-1.0.0.zip` contains:

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
    ├── build/  css/  js/  fonts/
    └── media/            (your uploaded images will be stored here)
```

> **Alternative (if your host lets you change the document root):** keep everything inside `oct-academy/` and point the domain’s document root to `oct-academy/public`. Then you do not need the package’s `public_html` folder.

### Folder permissions
Folders should be `755` and files `644` (DirectAdmin’s default). These folders must be writable by PHP: `oct-academy/storage` (and everything inside), `oct-academy/bootstrap/cache`, `public_html/media`, and the file `oct-academy/.env` once it exists.

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

## 9. Backups

**System › Backups** creates a zip with the full database (`database.sql`) and the media library, stored privately in `oct-academy/storage/app/backups`. Set a daily or weekly schedule (needs the cron job) and **download a copy regularly**.

To restore: super administrators can click **Restore** on any backup (a safety backup is made first). Without access to the dashboard, import `database.sql` with phpMyAdmin (*MySQL Management › phpMyAdmin*) and copy the `media/` folder into `public_html/media`.

DirectAdmin’s own *Create/Restore Backups* tool is a good additional safety net.

---

## 10. Updating to a new version

1. **System › Backups → Create backup now**, and download it.
2. **System › Health & maintenance → Enable maintenance mode** (keep the bypass link).
3. Upload the new package and replace the contents of `oct-academy/` **except** `.env` and `storage/`, and replace `public_html/build`, `css`, `js`, `fonts` and `index.php` (never delete `public_html/media`).
4. Open **System › Health & maintenance** (`/admin/system/health`). If database updates are pending, click **Apply database updates**, then **Clear all caches** and **Optimise for speed**. (With SSH you can instead run `php artisan migrate --force && php artisan optimize`.)
5. Bring the site back online.

---

## 11. Troubleshooting

| Problem | Fix |
|---|---|
| **500 error / blank page** | Check `oct-academy/storage/logs/laravel-YYYY-MM-DD.log`. Most often a folder is not writable or PHP < 8.3. |
| **“No application encryption key”** | `.env` is not writable; give it permission `644` and reload — a key is generated automatically. |
| **Styles missing** | `public_html/build` was not copied, or `.htaccess` is missing (hidden file). |
| **Images do not upload** | Increase `upload_max_filesize`/`post_max_size`; check that `public_html/media` is writable. |
| **Emails not arriving** | Use *Send test email* in Settings › Email; check spam; prefer port 465/SSL with a real mailbox. |
| **Stuck in maintenance mode** | Delete `oct-academy/storage/framework/down` in File Manager. |
| **Lost the authenticator phone** | Use a recovery code on the sign-in screen. Another super administrator can reset your 2FA in *Users*. With SSH: `php artisan cms:admin you@example.com --reset-2fa`. |
| **Forgot password** | Use *Forgot password* on the sign-in page, or with SSH: `php artisan cms:admin you@example.com`. |
| **Newsletters not sending** | The cron job is missing — see *System › Health*. While the campaign page is open, sending also progresses in the browser. |

### Useful commands (only if you have SSH)

```
php artisan cms:install --demo        # install without the web installer
php artisan cms:admin email@example.com   # create a super admin / reset a password
php artisan cms:reindex               # rebuild search index and sitemap
php artisan cms:demo --remove         # remove sample content
php artisan optimize                  # cache config/routes/views for speed
```
