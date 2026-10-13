# Architecture

One Laravel 12 application. Laravel owns routing, auth, validation, pricing, stock, orders and persistence. React (via Inertia.js v3) renders pages in the browser. There is no separate API server and no Node process in production.

```
Browser ──HTTP──► LiteSpeed/Apache (Hostinger) ──► public/index.php ──► Laravel
                                                                         │
             Inertia JSON (navigations) / full HTML (first load)         │
   ◄─────────────────────────────────────────────────────────────────────┤
                                                                         ▼
                                                  Services (pricing, cart, checkout,
                                                  inventory, orders, media, settings)
                                                                         │
                                                                         ▼
                                                       MySQL / MariaDB (Hostinger)
```

## Stack and versions

| Layer | Choice | Version | Why |
|---|---|---|---|
| Runtime | PHP | 8.3 (prod), `config.platform.php = 8.3.0` | Hostinger offers 8.3; Pest 4 dev tooling needs ≥ 8.3 |
| Framework | Laravel | 12.x | Required by the brief. **Note:** Laravel 12 receives security fixes only until **24 Feb 2027**; plan the 12 → 13 upgrade before then (code avoids APIs removed in 13). |
| Auth backend | Laravel Fortify | 1.41 | Maintained, headless; login throttling, password reset, session regeneration. Registration disabled. |
| Bridge | Inertia.js (Laravel 3.5, React 3.9) | v3 | Server routing + React pages without an API layer |
| UI | React 19, TypeScript 6 (strict) | | |
| Styling | Tailwind CSS 4 + shadcn/ui pattern on Radix primitives (`radix-ui`) | | Accessible primitives, owned source |
| Build | Vite 8, laravel-vite-plugin 3 | | Assets built locally/CI; only `public/build` ships |
| DB | MySQL 8 / MariaDB 10.6+ | | Hostinger default; local dev on MySQL 8.4 |
| Images | intervention/image 3 (GD) | | Re-encodes uploads to WebP, strips metadata |
| Phone | giggsey/libphonenumber-for-php-lite 9 | | Country-aware validation and E.164 normalisation |
| Tests | Pest 4, Vitest 5 + Testing Library, Playwright 1.64 | | |
| Static analysis | Larastan 3, TypeScript `tsc --noEmit`, ESLint 10, Pint, Prettier | | |

**Deliberately not used:** axios (Inertia v3 has its own client), Ziggy (would publish admin route names to every visitor; we use plain URLs), Zod (all validation is server-side and returned inline by Inertia within one round trip; a second schema in the browser would duplicate rules), Framer Motion (CSS transitions cover the motion spec), any analytics/tracking SDK, any payment SDK.

## Directory layout

```
app/
  Enums/            OrderStatus (with transition map), PaymentStatus, ProductStatus,
                    AdminRole, Permission, InventoryReason, CouponType, OrderEventType
  Models/           Eloquent models (see DATABASE.md)
  Services/
    Cart/           CartService — resolves cart from cookie, add/update/remove, view model
    Pricing/        PricingService, Quote (DTO), CouponEvaluator
    Checkout/       PlaceOrder (transactional), CheckoutException, ReferenceGenerator
    Orders/         OrderWorkflow (status transitions + side effects), PaymentRecorder, RiskAssessor
    Inventory/      InventoryService — reserve / release / adjust with ledger
    Media/          ImageProcessor — validate, re-encode, responsive sizes
    Settings/       Settings — typed, cached key/value store
    Seo/            Seo (meta DTO), StructuredData (JSON-LD builders)
    Audit/          AuditLogger
  Support/          Money, Phone
  Http/
    Controllers/Storefront/*   Controllers/Admin/*
    Requests/Storefront/*      Requests/Admin/*
    Middleware/     HandleInertiaRequests, SecurityHeaders, AssignRequestId, EnsureAdminIsActive
    Presenters/     Array shapes sent to React (ProductPresenter, OrderPresenter, …)
  Console/Commands/ admin:create
resources/js/
  app.tsx, ssr.tsx
  pages/            Inertia pages (storefront at root, admin under pages/admin)
  layouts/          StoreLayout, AdminLayout, AuthLayout
  components/ui/    Design-system primitives (Button, Field, Sheet, …)
  components/store/ Storefront compositions (ProductCard, CartDrawer, …)
  components/admin/ Admin compositions (DataTable, StatusBadge, ImageManager, …)
  lib/              money.ts, utils.ts
  types/            Shared prop types
```

## Request lifecycle for a mutation

Every state-changing endpoint follows the same nine steps (from the brief), implemented as:

| Step | Where |
|---|---|
| 1. Authenticate | `auth` middleware on `/admin/*`; storefront is public |
| 2. Authorise | Route `can:` middleware **and** `FormRequest::authorize()` / `Gate::authorize()` inside the controller action |
| 3. Validate + normalise | `FormRequest` rules + `prepareForValidation()` (trim, uppercase codes, E.164 phone) |
| 4. Rate limit | Named limiters in `AppServiceProvider` (`throttle:checkout`, …), stored in the database cache |
| 5. Recalculate | `PricingService` reads prices/stock from DB; browser values ignored |
| 6. Transaction | `DB::transaction()` + `lockForUpdate()` on variants/coupons |
| 7. Respond | Inertia redirect with flash, or Inertia validation errors |
| 8. Fail safely | Domain exceptions → field errors; unexpected → generic page with request ID |
| 9. Log | `AuditLogger` for admin actions; structured `Log::info` for order lifecycle |

## Key flows

### Add to cart
`POST /cart/items {variant_id, quantity}` → `CartService::add()` → checks variant is purchasable and clamps to stock and the per-line max → redirect back; `cart` shared prop refreshes the drawer.

### Place order
`POST /checkout` → `PlaceOrderRequest` → `PlaceOrder::handle()`:
1. Return existing order if `idempotency_key` already used.
2. `DB::transaction`: lock variants (`SELECT … FOR UPDATE`, ordered by id to avoid deadlocks) → re-check purchasable + stock → lock coupon row → `PricingService::quote()` → compare with `expected_total` → insert order, items (snapshots), events → decrement stock + ledger → coupon redemption → upsert customer → delete cart.
3. After commit: log, store order id in session for the confirmation page.

### Order status change
`POST /admin/orders/{order}/transitions {to, note, restock}` → `OrderWorkflow::transition()` checks `OrderStatus::canTransitionTo()`, locks the order row, applies side effects (release stock on cancel, optional restock on return, release coupon on cancel, set payment `not_collected` when appropriate), writes an `order_events` row and an audit log entry.

## Authorisation model

`users` are admin accounts only (shoppers never log in). Each user has one `AdminRole`; `AdminRole::permissions()` maps roles to `Permission` values; `Gate::before` is **not** used (no implicit super-user bypass — the owner simply holds every permission). Gates are defined per permission in `AppServiceProvider`.

| Route group | Middleware | In-action check |
|---|---|---|
| `/admin/login`, `/admin/forgot-password`, `/admin/reset-password/*` | `guest`, `throttle:login` / `throttle:password-reset` | Fortify |
| `/admin/*` | `auth`, `admin.active`, `can:<permission>` | `Gate::authorize()` or `FormRequest::authorize()` |
| Storefront | `throttle:<name>` where relevant | Business rules in services |

## Rendering and SEO

Inertia v3 sends the first page as full HTML with the page data in a `<script type="application/json">`. Because the target host cannot run a persistent Node SSR process, **SEO-critical tags are rendered by Blade** from a `Seo` object passed as view data: `<title>`, description, canonical, robots, Open Graph, Twitter, and JSON-LD. Crawlers and social scrapers get them without executing JavaScript; Google renders the React content. An SSR entry (`resources/js/ssr.tsx`) is built and tested so SSR can be switched on (`INERTIA_SSR_ENABLED=true`) on a plan that supports a Node process.

## Hosting constraints and how the design meets them

| Constraint (shared hosting) | Design response |
|---|---|
| No persistent Node | Assets prebuilt; SSR optional |
| No Redis / Memcached | `CACHE_STORE=database` (rate limits, settings cache), `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` |
| No long-running workers | Cron runs `schedule:run` every minute; scheduler runs `queue:work --stop-when-empty` and prunes old carts / sessions |
| Web root is `public_html` | App lives outside the web root; `public_html` is a symlink to `public/` (see DEPLOYMENT.md) |
| CLI PHP version can differ from web PHP | Deployment uses the explicit PHP 8.3 binary path |

## Testing layout

| Folder | Runner | Notes |
|---|---|---|
| `tests/Unit` | Pest | Money, phone, pricing, status/permission rules |
| `tests/Feature` | Pest + `RefreshDatabase` (MySQL `aloeandsoul_test`, or SQLite) | HTTP-level tests through real routes and middleware |
| `tests/Concurrency` | Pest + `DatabaseTruncation` | Spawns parallel PHP processes (`place-order.php`) racing for the same stock; MySQL only |
| `tests/js` | Vitest + Testing Library | UI primitives and money formatting |
| `tests/e2e` | Playwright | Full journeys on a freshly seeded `aloeandsoul_e2e` database (`E2eSeeder`) |

## Conventions

- Money: integers in minor units everywhere (`*_minor` columns, `Money` value object, `formatMoney()` in TS). Never floats.
- Dates: stored UTC, displayed in `Africa/Casablanca` (configurable `APP_DISPLAY_TIMEZONE`).
- Enums: PHP backed enums, mirrored as TS string unions in `resources/js/types`.
- Naming: controllers singular resource + `Controller`; actions as verbs (`PlaceOrder`); React components PascalCase; files kebab-case for UI primitives (shadcn convention).
- Errors: domain exceptions extend `App\Exceptions\DomainException` and carry a user-safe message + field key.
- No `any` in TypeScript; ESLint forbids it.
