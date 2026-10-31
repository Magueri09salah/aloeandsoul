# Deployment on Hostinger

This guide deploys Aloe & Soul to a Hostinger **Premium, Business or Cloud** hosting plan (shared/cloud, LiteSpeed, hPanel). It needs **no Node.js on the server, no Redis and no long-running worker**. A VPS works too (see the end).

How it works: every push to `main` makes GitHub Actions build the assets and commit them, with the code, to the **`production`** branch ([.github/workflows/production.yml](../.github/workflows/production.yml)). The server is a git clone of that branch, and [`scripts/update.sh`](../scripts/update.sh) pulls it, installs and migrates.

> **Plan check (do this first).** In hPanel, confirm: PHP 8.3 available · SSH access available (Premium and above) · MySQL/MariaDB databases · Cron jobs. This setup needs SSH.

## 1. Requirements

| | Required |
|---|---|
| PHP | **8.3** (8.4 also works) for both the website **and** the SSH command line |
| PHP extensions | bcmath, ctype, curl, dom, fileinfo, **gd**, **intl**, mbstring, openssl, pdo_mysql, tokenizer, xml, zip |
| Database | MySQL 8 or MariaDB 10.6+ |
| Composer, git | Installed on Hostinger shared hosting |
| Disk | ~150 MB for the app + uploads |
| GitHub | The repository, with Actions enabled (the default) |

Hostinger disables PHP's `proc_open`. The project is built for that: `update.sh` runs Composer without its scripts, and the scheduled tasks run inside the scheduler process (`routes/console.php`).

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

### 2.4 Read access to GitHub (deploy key)
The server reads the repository with its own key, which can only read this one repository:

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
ssh-keygen -t ed25519 -N "" -C "aloeandsoul-server" -f ~/.ssh/aloeandsoul_deploy
printf 'Host github.com\n  IdentityFile ~/.ssh/aloeandsoul_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
chmod 600 ~/.ssh/config
cat ~/.ssh/aloeandsoul_deploy.pub
```

On GitHub: the repository → *Settings* → **Deploy keys** → *Add deploy key*: paste the line printed above, leave *Allow write access* **unticked**. Test with `ssh -T git@github.com` (answer `yes` the first time): it greets the repository by name.

### 2.5 Folder layout (keeps the code out of the web root)

```
~/domains/aloeandsoul.com/
├── public_html  →  symlink to aloeandsoul/public   (the ONLY public folder)
└── aloeandsoul/                git clone of the `production` branch
    ├── .env                    production configuration (not in git)
    └── storage/app/public/     uploaded images (not in git)
```

**Never** put the project itself into `public_html`: `.env`, `storage/` and the source would become downloadable.

## 3. First deployment

### 3.1 The production branch
Push `main` to GitHub. On GitHub → **Actions**, the run *Build production branch* must finish with a green tick (about 2 minutes); the repository then has a `production` branch.

### 3.2 Clone

```bash
cd ~/domains/aloeandsoul.com
mv public_html public_html.old          # Hostinger's placeholder page
git clone --depth 1 --branch production --single-branch git@github.com:Magueri09salah/aloeandsoul.git aloeandsoul
```

### 3.3 Production `.env`
Generate the application key:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

**Keep a copy of the key in your password manager**: without it, encrypted sessions and two-factor secrets cannot be read after a restore. Never change it on a live site.

Create `~/domains/aloeandsoul.com/aloeandsoul/.env` (`nano` works) with the key and at least these values. Use passwords made of letters and digits only.

```dotenv
APP_NAME="Aloe & Soul"
APP_ENV=production
APP_KEY=base64:…
APP_DEBUG=false
APP_URL=https://aloeandsoul.com
APP_DISPLAY_TIMEZONE=Africa/Casablanca
LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
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
MAIL_FROM_ADDRESS="no-reply@aloeandsoul.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Protect it: `chmod 600 ~/domains/aloeandsoul.com/aloeandsoul/.env`.

### 3.4 Install

```bash
bash ~/domains/aloeandsoul.com/aloeandsoul/scripts/update.sh
```

`update.sh` will: check PHP ≥ 8.3 → fetch the `production` branch → **back up the database** to `~/backups` → `php artisan down` (from the second run) → switch the code to the new version → `composer install --no-dev --optimize-autoloader --no-scripts` and `package:discover` → check that the compiled assets are there → `migrate --force` (additive migrations only) → `db:seed --force` (creates categories, draft products, policy pages and FAQs only if missing; no users, no prices) → link `public/storage` to the uploads → `optimize` → `up`. It ends with `✓ Now on …` and the command to go back.

Then point the web root at the app (first time only):

```bash
cd ~/domains/aloeandsoul.com
ln -s aloeandsoul/public public_html
```

> If Hostinger refuses the symlink (rare on Cloud plans), use the fallback in §9.

### 3.5 Create the owner account

```bash
cd ~/domains/aloeandsoul.com/aloeandsoul
php artisan admin:create          # prompts for name, email and password (not echoed)
```

Sign in at `https://aloeandsoul.com/admin/login`, then **Profile → Set up two-factor authentication**. There are no default credentials; nothing else creates admin users.

### 3.5b Product information, photos and prices (first deployment only)

`database/assets/products/<product>/` holds, for each of the 26 seeded products, the product information in English (`details.md`) and French (`details.fr.md`), its official photographs and its Moroccan retail price and reference. Fill the catalogue once:

```bash
php artisan catalog:import --dry-run   # preview
php artisan catalog:import             # takes several minutes (about 120 photos); products stay unpublished
```

Only empty fields are filled (in each language), and products that already have photos keep them, so it is safe to run again. `--overwrite` replaces texts, prices and references (and removes sale prices); `--replace-images` replaces the photos. Five products get no price (C9 and four products missing from the Moroccan catalogue): the command lists them; set their prices in Admin → Products. Then review each product, add stock and publish. If it stops on memory, run `php -d memory_limit=512M artisan catalog:import`.

### 3.6 Cron (scheduler + queue)
hPanel → *Advanced* → **Cron Jobs** → *Custom*, every minute. Use the path that `which php` prints (PHP 8.3) and your user name:

```
* * * * * /opt/alt/php83/usr/bin/php /home/u123456789/domains/aloeandsoul.com/aloeandsoul/artisan schedule:run >> /dev/null 2>&1
```

The scheduler works through queued jobs (one at a time, up to 50 seconds a minute), prunes abandoned carts and old audit entries, clears expired password-reset tokens and deletes expired rate-limit rows, all inside this one PHP process. Check it: `php artisan schedule:list`.

### 3.7 HTTPS
hPanel → *Security* → **SSL**: install the free certificate and enable **Force HTTPS**. The app sends HSTS on HTTPS responses and secure-only cookies (`SESSION_SECURE_COOKIE=true`).

### 3.8 Hostinger CDN / proxy
Keep Hostinger CDN off unless you configure this: with a CDN or Cloudflare in front, visitors' IPs arrive in forwarded headers. Set `TRUSTED_PROXIES` in `.env` to the proxy IP ranges (or `*` only if the origin is not reachable directly), otherwise rate limits apply to the proxy's IP and can block real customers.

## 4. Smoke tests after every deployment

```bash
cd ~/domains/aloeandsoul.com/aloeandsoul
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

1. Commit and push to `main`.
2. GitHub → **Actions**: wait for the green tick on *Build production branch*. If it fails, the server keeps the previous version; fix the build first.
3. On the server:

```bash
bash ~/domains/aloeandsoul.com/aloeandsoul/scripts/update.sh
```

Visitors see the maintenance page for the minute it takes. `.env`, uploaded images and logs are not in git and stay as they are.

`update.sh` runs the migrations and the safe `db:seed`, which only adds what is missing (for example French names and texts for the original categories, pages and FAQ, and never over an edit).

Migrations must be **additive** (new tables/columns, nullable or defaulted). Never run `migrate:fresh`, `migrate:reset`, `db:wipe`, `DemoSeeder` or `E2eSeeder` on production (the seeders refuse to run there). Never edit files on the server: the next update replaces them.

## 7. Rollback

Each update ends with the command to go back, e.g.:

```bash
bash ~/domains/aloeandsoul.com/aloeandsoul/scripts/update.sh 4acd126    # code back to that version
```

Earlier versions: `git -C ~/domains/aloeandsoul.com/aloeandsoul reflog`. Migrations are not reversed. If a migration caused the problem, restore the backup taken just before it:

```bash
php artisan down
gunzip -c ~/backups/db-<timestamp>.sql.gz | mysql -h localhost -u u123456789_aloe -p u123456789_aloe
php artisan up
```

## 8. Backups and restore

- **Automatic**: hPanel → *Files* → **Backups** (daily/weekly depending on plan) covers files and databases. Download one monthly and keep it off-site.
- **Before each update**: `update.sh` writes `~/backups/db-<timestamp>.sql.gz` (last 10 kept).
- **Uploads** live in `aloeandsoul/storage/app/public`; include them in your off-site copy:
  `tar -czf ~/backups/uploads-$(date +%F).tar.gz -C ~/domains/aloeandsoul.com/aloeandsoul/storage app/public`
- **Restore test** (do it once before launch): create a scratch database in hPanel, import a backup into it with `mysql … < dump.sql`, and check the row counts of `orders` and `products`.

## 9. If `public_html` cannot be a symlink

Keep the app in `~/domains/aloeandsoul.com/aloeandsoul` as above, copy only the **contents** of `aloeandsoul/public/` into `public_html/`, then edit `public_html/index.php` so both `__DIR__.'/../…'` paths point to `__DIR__.'/../aloeandsoul/…'`. Re-copy `public/build` after each update, and create `public_html/storage` as a symlink to `aloeandsoul/storage/app/public`. The application code still stays outside the web root.

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
| 500 error, blank page | `tail -50 aloeandsoul/storage/logs/laravel-*.log`; quote the request ID shown on the error page |
| Maintenance page stays after an update | The update stopped: read its error, fix it, run `update.sh` again (or `php artisan up`) |
| "Please provide a valid cache path" | `mkdir -p storage/framework/{cache/data,sessions,views}` in `aloeandsoul/` and check permissions (755 dirs, 644 files) |
| `public/build is missing` / assets 404 | The server is not on a `production` build: check GitHub → Actions, then run `update.sh` again |
| Images 404 | In `aloeandsoul/`: `ln -s ../storage/app/public public/storage` |
| `Permission denied (publickey)` on fetch | The deploy key is missing on GitHub or in `~/.ssh/config` (§2.4) |
| `connect to host github.com port 22: Connection timed out` | Add `Hostname ssh.github.com` and `Port 443` under `Host github.com` in `~/.ssh/config` |
| "The Process class relies on proc_open" | Something ran Composer with its scripts, or a scheduled task as a separate command: use `update.sh`, and `Schedule::call` in `routes/console.php` |
| Composer errors about PHP version | SSH `php` is not 8.3: see §2.2 |
| Password reset emails not arriving | Check `MAIL_*` values; test with `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@example.com'))` |
| Changes to `.env` ignored | `php artisan optimize:clear && php artisan optimize` |
