# Deployment on Hostinger

This guide deploys Aloe & Soul to a Hostinger **Premium, Business or Cloud** hosting plan (shared/cloud, LiteSpeed, hPanel). It needs **no Node.js on the server, no Redis and no long-running worker**. A VPS works too (see the end).

> **Plan check (do this first).** In hPanel, confirm: PHP 8.3 available · SSH access available (Premium and above) · MySQL/MariaDB databases · Cron jobs. If SSH is not available on your plan, upgrade or use the "No SSH" notes in §9; everything else assumes SSH.

## 1. Requirements

| | Required |
|---|---|
| PHP | **8.3** (8.4 also works) for both the website **and** the SSH command line |
| PHP extensions | bcmath, ctype, curl, dom, fileinfo, **gd**, **intl**, mbstring, openssl, pdo_mysql, tokenizer, xml, zip |
| Database | MySQL 8 or MariaDB 10.6+ |
| Composer | 2.x (installed on Hostinger shared hosting) |
| Disk | ~150 MB for the app + uploads |
| Locally | PHP 8.3, Composer, Node 22+, npm (to build the assets) |

## 2. One-time server setup

### 2.1 PHP version and extensions
hPanel → *Websites* → your site → *Advanced* → **PHP Configuration**: choose **PHP 8.3**, and under *PHP Extensions* make sure `gd`, `intl`, `fileinfo`, `pdo_mysql`, `bcmath` and `zip` are ticked. Under *PHP Options* set `memory_limit` ≥ 256M, `upload_max_filesize` and `post_max_size` ≥ 16M.

### 2.2 SSH and the PHP command line
hPanel → *Advanced* → **SSH Access** → enable, then connect: `ssh -p 65002 u123456789@<server-ip>` (values shown in hPanel).

The SSH `php` command can be a **different version** from the website. Check it:

```bash
php -v
ls /opt/alt/            # lists available PHP builds, e.g. php83
```

If `php -v` is not 8.3, put PHP 8.3 first on your PATH (once):

```bash
echo 'export PATH=/opt/alt/php83/usr/bin:$PATH' >> ~/.bashrc && source ~/.bashrc
php -v                  # must now show 8.3.x
```

### 2.3 Database
hPanel → *Databases* → **MySQL Databases**: create a database and a user with a strong password (e.g. `u123456789_aloe` / `u123456789_aloe`). Note the host (usually `localhost`). The user only needs rights on this database.

### 2.4 Folder layout (keeps the code out of the web root)

```
~/domains/aloeandsoul.com/
├── public_html  →  symlink to aloeandsoul/current/public   (the ONLY public folder)
└── aloeandsoul/
    ├── current  →  releases/20261013120000
    ├── releases/            (last 5 releases, for rollback)
    └── shared/
        ├── .env             (production configuration, created once)
        └── storage/         (uploads, logs, sessions — persist across releases)
```

```bash
cd ~/domains/aloeandsoul.com
mkdir -p aloeandsoul/releases aloeandsoul/shared/storage
mv public_html public_html.old          # keep the old Zyro/Hostinger placeholder for now
```

The `public_html` symlink is created after the first release (§3.4). **Never** upload the whole project into `public_html`: `.env`, `storage/` and the source would become downloadable.

### 2.5 Production `.env`
Create `~/domains/aloeandsoul.com/aloeandsoul/shared/.env` from `.env.example` and set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aloeandsoul.com
APP_KEY=            # generated below
LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_HOST=localhost
DB_DATABASE=u123456789_aloe
DB_USERNAME=u123456789_aloe
DB_PASSWORD=********

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

# Hostinger email account used to send admin password-reset links
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@aloeandsoul.com
MAIL_PASSWORD=********
MAIL_FROM_ADDRESS=no-reply@aloeandsoul.com
```

Protect it: `chmod 600 ~/domains/aloeandsoul.com/aloeandsoul/shared/.env`.

## 3. First deployment

### 3.1 Build the release on your computer

```bash
git pull
./scripts/package-release.sh          # npm ci + npm run build + archive (no vendor)
```

This produces `release-YYYYmmdd-HHMMSS.tar.gz` containing the code **and the compiled assets** (`public/build`). `.env`, `node_modules`, tests and local uploads are excluded. If the server cannot run Composer, use `./scripts/package-release.sh --vendor` (bundles production dependencies; build it with PHP 8.3).

### 3.2 Upload

```bash
scp -P 65002 release-*.tar.gz scripts/deploy.sh scripts/rollback.sh u123456789@<server-ip>:~/
```

(or hPanel → *File Manager*, upload into your home directory).

### 3.3 Generate the application key (first time only)

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Paste the printed value into `shared/.env` as `APP_KEY=…`. **Keep a copy of the key in your password manager**: without it, encrypted sessions and two-factor secrets cannot be read after a restore. Never change it on a live site.

### 3.4 Deploy

```bash
cd ~
DOMAIN=aloeandsoul.com bash deploy.sh ~/release-YYYYmmdd-HHMMSS.tar.gz
```

`deploy.sh` will: check PHP ≥ 8.3 → extract into `releases/<timestamp>` → link the shared `.env` and `storage` → `composer install --no-dev --optimize-autoloader --no-scripts`, then `package:discover` (Hostinger disables `proc_open`, so Composer cannot run the package scripts itself) → **back up the database** to `~/backups` → `php artisan down` → `migrate --force` (additive migrations only) → `db:seed --force` (creates categories, draft products, policy pages and FAQs only if missing; no users, no prices) → link `public/storage` to the shared uploads → `optimize` → switch `current` atomically → `up` → keep the last 5 releases.

Then point the web root at the release (first time only):

```bash
cd ~/domains/aloeandsoul.com
ln -s aloeandsoul/current/public public_html
```

> If Hostinger refuses the symlink (rare on Cloud plans), use the fallback in §9.

### 3.5 Create the owner account

```bash
cd ~/domains/aloeandsoul.com/aloeandsoul/current
php artisan admin:create          # prompts for name, email and password (not echoed)
```

Sign in at `https://aloeandsoul.com/admin/login`, then **Profile → Set up two-factor authentication**. There are no default credentials; nothing else creates admin users.

### 3.5b Product information, photos and prices (first deployment only)

`database/assets/products/<product>/` holds, for each of the 26 seeded products, the product information in English (`details.md`) and French (`details.fr.md`), its official photographs and its Moroccan retail price and reference. Fill the catalogue once:

```bash
php artisan catalog:import --dry-run   # preview
php artisan catalog:import             # takes several minutes (about 120 photos); products stay unpublished
```

Only empty fields are filled (in each language), and products that already have photos keep them, so it is safe to run again. `--overwrite` replaces texts, prices and references (and removes sale prices); `--replace-images` replaces the photos. Five products get no price (C9 and four products missing from the Moroccan catalogue): the command lists them; set their prices in Admin → Products. Then review each product, add stock and publish.

### 3.6 Cron (scheduler + queue)
hPanel → *Advanced* → **Cron Jobs** → *Custom*, every minute:

```
* * * * * /opt/alt/php83/usr/bin/php /home/u123456789/domains/aloeandsoul.com/aloeandsoul/current/artisan schedule:run >> /dev/null 2>&1
```

The scheduler processes queued jobs (`queue:work --stop-when-empty`), prunes abandoned carts and old audit entries, clears expired password-reset tokens and deletes expired rate-limit rows. Check it: `php artisan schedule:list`.

### 3.7 HTTPS
hPanel → *Security* → **SSL**: install the free certificate and enable **Force HTTPS**. The app sends HSTS on HTTPS responses and secure-only cookies (`SESSION_SECURE_COOKIE=true`).

### 3.8 Hostinger CDN / proxy
If you enable Hostinger CDN or Cloudflare, visitors' IPs arrive in forwarded headers. Set `TRUSTED_PROXIES` in `.env` to the proxy IP ranges (or `*` only if the origin is not reachable directly), otherwise rate limits apply to the proxy's IP.

## 4. Smoke tests after every deployment

```bash
cd ~/domains/aloeandsoul.com/aloeandsoul/current
php artisan about --only=environment     # Environment: production, Debug Mode: OFF
php artisan migrate:status | tail -5     # all "Ran"
curl -sI https://aloeandsoul.com/up | head -1                       # 200
curl -s  https://aloeandsoul.com/robots.txt                         # Sitemap line present
curl -sI https://aloeandsoul.com/ | grep -iE 'strict-transport|content-security|x-frame'
curl -s  -o /dev/null -w '%{http_code}\n' https://aloeandsoul.com/.env          # 404
curl -s  -o /dev/null -w '%{http_code}\n' https://aloeandsoul.com/composer.json # 404
```

In a browser: home, a category, a product, add to cart, checkout page loads with delivery areas, the same pages in French (the **Français** link in the header, or `/fr`), `/admin/login`. Place one real test order with your own phone number, then cancel it in the admin (stock returns automatically).

## 5. Launch checklist (content)

The admin dashboard shows a "Before you launch" list. In short:

- [ ] Products: price, official images, description; then **Publish** (drafts are invisible)
- [ ] **Delivery areas** with fees (checkout stays closed until one exists)
- [ ] Policy pages: replace every `[placeholder]` and untick "Still needs review"
- [ ] Settings → contact details, social links, COD wording
- [ ] French texts: switch each admin form to **Français** and check the French versions (story, home page, policy pages, FAQ, products, delivery areas); empty French fields show the English text on `/fr`
- [ ] Two-factor authentication on for every admin
- [ ] Confirm with Forever Living that your distributor terms allow this online shop (see PRD §17)

## 6. Updating the site

```bash
# locally
./scripts/package-release.sh
scp -P 65002 release-*.tar.gz u123456789@<server-ip>:~/
# on the server
DOMAIN=aloeandsoul.com bash ~/deploy.sh ~/release-…tar.gz
```

`deploy.sh` runs the migrations and the safe `db:seed`, which only adds what is missing (for example French names and texts for the original categories, pages and FAQ, and never over an edit). After the update that added French, also run `php artisan catalog:import` once: it fills the empty French product information and leaves everything else as it is.

Migrations must be **additive** (new tables/columns, nullable or defaulted). Never run `migrate:fresh`, `migrate:reset`, `db:wipe`, `DemoSeeder` or `E2eSeeder` on production (the seeders refuse to run there).

## 7. Rollback

```bash
DOMAIN=aloeandsoul.com bash ~/rollback.sh       # code back to the previous release
```

If a migration caused the problem, restore the backup taken just before it:

```bash
php artisan down
gunzip -c ~/backups/db-<timestamp>.sql.gz | mysql -h localhost -u u123456789_aloe -p u123456789_aloe
php artisan up
```

## 8. Backups and restore

- **Automatic**: hPanel → *Files* → **Backups** (daily/weekly depending on plan) covers files and databases. Download one monthly and keep it off-site.
- **Before each deploy**: `deploy.sh` writes `~/backups/db-<timestamp>.sql.gz` (last 10 kept).
- **Uploads** live in `shared/storage/app/public`; include them in your off-site copy:
  `tar -czf ~/backups/uploads-$(date +%F).tar.gz -C ~/domains/aloeandsoul.com/aloeandsoul/shared/storage app/public`
- **Restore test** (do it once before launch): create a scratch database in hPanel, import a backup into it with `mysql … < dump.sql`, and check the row counts of `orders` and `products`.

## 9. Without SSH, or if `public_html` cannot be a symlink

*No symlink:* keep the app in `~/domains/aloeandsoul.com/aloeandsoul/current` as above, copy only the **contents** of `current/public/` into `public_html/`, then edit `public_html/index.php` so both `__DIR__.'/../…'` paths point to `__DIR__.'/../aloeandsoul/current/…'`. Re-copy `public/build` after each release, and create `public_html/storage` as a symlink (or set `FILESYSTEM_DISK`'s public root to `public_html/storage`). The application code still stays outside the web root.

*No SSH:* build with `./scripts/package-release.sh --vendor`, upload and extract via File Manager, run migrations from hPanel's *Cron Jobs* with a one-off `php artisan migrate --force` command, then remove that job. This is fragile; prefer a plan with SSH.

## 10. VPS / plans with Node.js

On a Hostinger VPS (or a plan that can keep a Node process alive), you may also enable server-side rendering:

```bash
npm ci && npm run build:ssr              # builds bootstrap/ssr/ssr.js
php artisan inertia:start-ssr            # keep it running with supervisor/pm2
# .env: INERTIA_SSR_ENABLED=true
```

Use the same `.env` settings; Redis can replace the database cache/queue (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`) with a supervised `queue:work`.

## 11. Troubleshooting

| Symptom | Fix |
|---|---|
| 500 error, blank page | `tail -50 shared/storage/logs/laravel-*.log`; quote the request ID shown on the error page |
| "Please provide a valid cache path" | `mkdir -p shared/storage/framework/{cache/data,sessions,views}` and check permissions (755 dirs, 644 files) |
| Assets 404 | `public/build` missing: rebuild with `package-release.sh` |
| Images 404 | In `current/`: `ln -sfn ~/domains/aloeandsoul.com/aloeandsoul/shared/storage/app/public public/storage` |
| "The Process class relies on proc_open" | The host disables `proc_open`: run Composer with `--no-scripts`, then `php artisan package:discover` (`deploy.sh` does this) |
| Composer errors about PHP version | SSH `php` is not 8.3: see §2.2 |
| Password reset emails not arriving | Check `MAIL_*` values; test with `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@example.com'))` |
| Changes to `.env` ignored | `php artisan optimize:clear && php artisan optimize` |
