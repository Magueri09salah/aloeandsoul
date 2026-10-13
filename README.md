# Aloe & Soul

Online shop for **Aloe & Soul** — wellness, beauty, fitness and nutrition, founded by a woman whose journey led from London to Marrakech. *Nourish the Body. Inspire the Soul.*

- Storefront with four categories, product variants, search and filters, a founder-story feature, a blog, a collaborators section and policy pages
- Guest checkout, **cash on delivery only** (no payment gateway, no card data)
- Admin for orders, products, stock, customers, discount codes, delivery areas, blog posts, collaborators, content, settings and team roles
- Built to run on Hostinger PHP hosting: no Node.js, Redis or worker process in production

| Docs | |
|---|---|
| [docs/PRD.md](docs/PRD.md) | Requirements, scope, acceptance criteria, open questions for the client |
| [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) | Palette, type, tokens, components, motion, accessibility |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Stack, structure, request flow, key decisions |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema, money strategy, order lifecycle, inventory consistency |
| [docs/SECURITY.md](docs/SECURITY.md) | Threat model and implemented controls |
| [docs/SEO.md](docs/SEO.md) | Metadata, structured data, indexation, performance |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Step-by-step Hostinger deployment, backups, rollback |

## Stack

Laravel 12 (PHP 8.3) · Inertia.js v3 · React 19 · TypeScript 6 (strict) · Tailwind CSS 4 · Radix primitives (shadcn/ui pattern) · Vite 8 · MySQL 8 / MariaDB · Laravel Fortify · Pest 4 · Vitest · Playwright · Larastan.

## Prerequisites

- PHP 8.3+ with `bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, tokenizer, xml, zip`
- Composer 2
- Node.js 22+ and npm
- MySQL 8 (or MariaDB 10.6+). On Windows, Laragon provides PHP 8.3 and MySQL 8.4.

## Local setup

```bash
git clone <repo> aloeandsoul && cd aloeandsoul
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Create two databases (development and tests), e.g. with the MySQL CLI:

```sql
CREATE DATABASE aloeandsoul      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE aloeandsoul_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set `DB_*` in `.env`, then:

```bash
php artisan migrate --seed          # categories, 26 draft products, policy pages, FAQ (safe in any environment)
php artisan db:seed --class=DemoSeeder   # LOCAL ONLY: placeholder prices, stock, images, delivery areas; publishes products
php artisan storage:link
php artisan admin:create            # your local owner account (prompts for the password)
```

### Run

```bash
php artisan serve                   # http://127.0.0.1:8000
npm run dev                         # Vite with hot reload (second terminal)
```

Admin: http://127.0.0.1:8000/admin

## Environment variables

See [.env.example](.env.example); every variable is commented. Production values are listed in [docs/DEPLOYMENT.md §2.5](docs/DEPLOYMENT.md). Never commit `.env`.

Business settings (store name, currency, country, contact details, COD wording, announcement bar, home page and story text, delivery areas, discount codes) are **edited in the admin**, not in `.env`.

## Database

- Schema: `database/migrations` (documented in [docs/DATABASE.md](docs/DATABASE.md)).
- `DatabaseSeeder` is idempotent and creates **no users and no prices**.
- `DemoSeeder` and `E2eSeeder` refuse to run in production.
- Production migrations: `php artisan migrate --force` (additive only). Never `migrate:fresh` on production.

## Creating the first administrator

```bash
php artisan admin:create              # owner by default; --role=manager|staff
```

The password is typed interactively (never passed on the command line). Further accounts: Admin → Team (owners only). There is no public registration and no default account.

## Tests and quality checks

| Command | What it runs |
|---|---|
| `php vendor/bin/pest` | 178 PHP tests: unit (money, phone, pricing, status rules), feature (catalogue, cart, checkout, admin, security), and a **multi-process concurrency test** on MySQL. Uses `aloeandsoul_test`. |
| `npm test` | Vitest + Testing Library component tests |
| `npm run build && npm run test:e2e` | Playwright journeys (desktop + mobile) against a fresh `aloeandsoul_e2e` database; create that database first |
| `npm run typecheck` | `tsc --noEmit` (strict) |
| `npm run lint` | ESLint (no `any`, hooks rules, HTML injection only via `SafeHtml`) |
| `vendor/bin/phpstan analyse` | Larastan level 6 |
| `vendor/bin/pint --test` | PHP code style |
| `npm run check` | typecheck + lint + Vitest + build |

The PHP suite uses MySQL by default (row locking matters). To run it on SQLite instead, set `DB_CONNECTION=sqlite DB_DATABASE=:memory:`; the concurrency test then skips itself.

## Build and deploy

```bash
./scripts/package-release.sh          # builds assets, creates release-*.tar.gz
# upload, then on the server:
bash deploy.sh release-*.tar.gz       # backup DB → migrate → atomic switch; keeps 5 releases
bash rollback.sh                      # previous release
```

Full Hostinger guide: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). Production never runs `npm`.

## Operations

- **Cron**: one entry running `php artisan schedule:run` every minute (queue, cart/audit pruning, expired tokens and rate-limit rows).
- **Logs**: `storage/logs/laravel-YYYY-MM-DD.log` (14 days). Each line carries a request ID; error pages show it so customers can quote it.
- **Backups**: Hostinger automatic backups + a database dump before every deploy (`~/backups`).
- **Order workflow**: Admin → Orders. New orders arrive as *Awaiting confirmation*; confirm by phone/WhatsApp, prepare, hand to the courier, then mark delivered and record the cash collected. Revenue on the dashboard counts **collected cash only**.
- **Stock** is reserved when an order is placed and returned automatically if it is cancelled (or returned, if you choose). Every change appears in Stock → History.
- **Privacy requests**: Admin → Customers → customer → *Remove personal data* (owners).

## Product information, photos and prices

`database/assets/products/<product-slug>/` contains, for each of the 26 catalogue products:

- `details.md`: the product information in English, translated from the official foreverliving.fr product page (summary, description, benefits, how to use with precautions, ingredients, nutrition table, SEO title and description), plus the Forever reference, size and **Moroccan retail price in dirhams** from Forever Living Products Morocco's *Catalogue Général 2026/2027*. Sections start with `# Heading`; the rest is Markdown.
- `01.webp`, `02.webp`…: all the product's official photographs in display order (packshot first, then photos and nutrition labels). `sources.json` lists where each one comes from.

```bash
php artisan catalog:import                              # fill empty fields; attach photos to products without any
php artisan catalog:import --overwrite --replace-images # local demo data: replace demo prices, texts and placeholder photos
```

The first photo is padded to the 4:5 product tile; the others are kept whole and shown in the product page carousel. Products are never published by the import.

Notes for the client (PRD §17):

- Texts and images © Forever Living Products; the health wording is Forever's own. The distributor agreement must allow their use.
- **No price** is set for C9 (in Morocco, references 475/476 are the 9-day *Detox* pack at 1,616 Dhs, with different contents) or for Aloe Bio-Cellulose Mask, Intense Hydrating Care, Aloe Body Wash and Aloe Body Lotion (not in the Moroccan catalogue; French prices are noted in their `details.md`).
- *Heat Lotion* is not sold in France: its text and photo come from the Moroccan catalogue. *Forever Vision* is **Forever iVision**, its current name. The Aloe Vera Gel photos carry a French "Top Santé 2025" award badge, and some nutrition labels in the photos are in French (the English figures are in the *Nutrition information* section).

## Brand assets

`database/assets` holds the source logo and founder photograph (from the client's current website). Regenerate the web renditions in `public/brand` with `php database/assets/build-brand-assets.php`.
