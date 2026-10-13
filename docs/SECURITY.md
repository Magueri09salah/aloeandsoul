# Security

How Aloe & Soul protects customers, orders and the admin. Every control below is implemented and, where marked ✓T, covered by an automated test.

## Threat model (summary)

| Asset | Main threats | Key controls |
|---|---|---|
| Orders & stock | Price manipulation, overselling, duplicate orders, fake orders | Server-side pricing, row locks + conditional decrement, idempotency keys, rate limits, risk flags |
| Customer data (name, phone, address) | Leakage via guessable URLs, admin compromise, logs | No order data reachable by URL alone, session-bound confirmation, reference+phone lookup, PII redaction in audit/logs, anonymisation |
| Admin | Credential stuffing, privilege escalation, session theft | Fortify auth, login throttling, optional TOTP 2FA, per-action authorisation, secure cookies, history encryption, deactivation kills sessions |
| Site integrity | XSS, CSRF, malicious uploads, clickjacking | Escaped React output, sanitised Markdown, CSRF tokens, image re-encoding, CSP and frame-ancestors |

## Input validation

- All input is validated on the server with Form Requests (`app/Http/Requests`) or controller validation; HTML attributes are convenience only.
- Normalisation before validation: trimming, lower-casing emails, upper-casing coupon codes, E.164 phone numbers (`App\Support\Phone`, libphonenumber).
- Bounds everywhere: string lengths, quantities (1–99, then clamped to stock and the per-line maximum), prices (`Money::parse` rejects more than the currency's decimals), pagination, enums (`Rule::enum`).
- Catalogue filters are whitelisted; invalid values are ignored rather than echoed (✓T `ignores malformed filter values`).
- Variant ids in the product form must belong to the product being edited (✓T).

## Prices, totals and stock

- Prices are read from the database at every step; anything the browser sends about prices is ignored (✓T `ignores prices and totals sent by the browser`).
- `PricingService` is the only place totals are computed. Integers in minor units; no floats.
- The checkout form sends the total the shopper saw; if the server's total differs, no order is placed (✓T).
- Order placement runs in one transaction: cart row lock → variant rows locked in id order → stock re-checked → `UPDATE … SET stock = stock - n WHERE stock >= n` → ledger (✓T multi-process concurrency test on MySQL).
- Duplicate submissions: per-page idempotency key (UUID), stored in the session and as a unique column; a key only retrieves an order placed by the same session (✓T).

## Authentication and sessions

- Laravel Fortify under `/admin`; **registration disabled** (✓T). The first owner is created with `php artisan admin:create` (password typed interactively, never on the command line).
- Passwords: bcrypt (12 rounds), minimum 12 characters with mixed case and a number, checked against known breaches (k-anonymity `uncompromised()`).
- Login throttling: 5/min per email+IP and 20/min per IP (✓T). Password reset requests: 5 per 15 minutes per IP.
- Same error for wrong password and deactivated account (✓T). Failed logins are logged with a hashed email only.
- Optional TOTP two-factor authentication with recovery codes; the dashboard checklist nudges owners to enable it. The QR code is delivered as an image data URI (no injected SVG markup).
- Sessions: database driver, `SESSION_ENCRYPT=true`, `HttpOnly`, `SameSite=Lax`, `Secure` in production; session regenerated on login and invalidated on logout.
- Deactivating an admin deletes their session rows and remember token immediately (✓T); `EnsureAdminIsActive` also signs out a deactivated user mid-session (✓T).
- Admin pages use Inertia history encryption so data cannot be recovered from the browser history after logout.
- Password reset tokens are part of the emailed link (standard for Laravel). They are single-use, expire after 60 minutes, are stored hashed, and the reset page loads no third-party resources; `Referrer-Policy: strict-origin-when-cross-origin` prevents the path leaking cross-site.

## Authorisation

- Roles: owner, manager, staff (`App\Enums\AdminRole::permissions()`); one Gate per `App\Enums\Permission`.
- **Every admin route** has a `can:` middleware **and** every action re-checks with `Gate::authorize()` or `FormRequest::authorize()` (✓T role matrix).
- No `Gate::before` super-user bypass. The last active owner cannot be demoted or deactivated (✓T).
- Customers never have accounts; an order confirmation is visible only to the session that placed it (✓T); tracking requires reference **and** phone number with a generic failure message (✓T) and is rate-limited.

## Rate limiting

Persistent store: `CACHE_STORE=database` (shared-hosting friendly; never in-memory).

| Limiter | Limit | Applied to |
|---|---|---|
| `checkout` | 6/min and 30/h per IP | `POST /checkout` (✓T) |
| `cart` | 60/min per IP | cart mutations, coupon apply |
| `search` | 60/min per IP | `/shop`, category pages |
| `forms` | 3/min and 20/day per IP | contact, newsletter (✓T) |
| `track-order` | 10 per 10 min per IP | order lookup |
| `login` | 5/min per email+IP, 20/min per IP | Fortify login (✓T) |
| `two-factor` | 5/min | 2FA challenge |
| password reset | 5 per 15 min per IP | `ThrottlePasswordResetRequests` |

Inertia (browser) requests that hit a limit are sent back to the form with a visible message and `Retry-After`; other clients receive `429 Too Many Requests`. Forwarded IP headers are only trusted for proxies listed in `TRUSTED_PROXIES`.

## XSS, CSRF and headers

- React escapes all output. The **only** HTML injection point is `SafeHtml`, enforced by an ESLint rule; it renders Markdown converted on the server by CommonMark with `html_input=strip` and `allow_unsafe_links=false` (✓T script tags, `javascript:` links and event handlers are removed).
- Admin-entered links (announcement bar) must be site-relative or `https://` (✓T).
- CSRF: Laravel's token check on every web route (✓T wiring); Inertia sends the `XSRF-TOKEN` automatically. Expired tokens show "This page expired" instead of an error page.
- Headers (`SecurityHeaders` middleware, ✓T): `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, HSTS on HTTPS, and outside local development a CSP: `default-src 'self'`, scripts `'self'` + per-request nonce, `object-src 'none'`, `frame-ancestors 'none'`, `form-action 'self'`, `base-uri 'self'`. `style-src` allows `'unsafe-inline'` for Radix positioning styles only.
- Cart, checkout, tracking and admin responses: `Cache-Control: no-store, private` and `X-Robots-Tag: noindex, nofollow` (✓T).

## File uploads

- Accepted: JPEG, PNG, WebP, checked by content (MIME sniffing), not extension; SVG rejected (✓T); max 8 MB; min width 400 px; max 6000 × 6000 px.
- Every upload is decoded and **re-encoded to WebP** by GD, so the original bytes (EXIF location data, polyglot payloads) are never stored or served (✓T a PHP file disguised as JPEG is rejected).
- Random ULID filenames; files live on the `public` disk under `storage/app/public` (served via the `public/storage` symlink); no user-controlled paths.

## Abuse and COD fraud

- Honeypot fields on contact and newsletter forms (✓T).
- Order risk flags (never automatic rejection): repeat phone in 24 h, repeat connection in 24 h, previous failed delivery/return, unusually large quantity (✓T). Shown prominently on the order page with call/WhatsApp buttons.
- IP addresses are stored with orders, messages and subscribers only as an HMAC (`ip_hash`) (✓T); raw IPs appear only in the admin audit log and in the server log entry for a login lockout.

## Secrets, logging and errors

- `.env` is git-ignored; `.env.example` contains names and placeholders only.
- `APP_DEBUG=false` in production: errors render a friendly page with a request ID (`X-Request-Id`), details stay in `storage/logs`.
- Every log line carries the request ID (Laravel Context). Order lifecycle events (`order.placed`, `order.status_changed`) log ids and totals, not personal data.
- Admin audit log (`audit_logs`, owner-only view, kept 2 years) records logins, product/category/coupon/zone/content/settings/user changes, stock adjustments, order transitions and payments, customer anonymisation, subscriber exports. Passwords, tokens, phones, emails and addresses are redacted.

## Privacy

- Data minimisation: checkout asks only for what delivery needs; email optional.
- Consent: privacy-policy acceptance timestamp on every order; newsletter stores consent time and wording; CSV export neutralises spreadsheet formulas.
- Right to erasure: owners can anonymise a customer and all their orders; totals are kept for accounting (✓T).
- No third-party scripts, fonts or trackers are loaded.

## Operational checklist (production)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`
- [ ] HTTPS enforced; `SESSION_SECURE_COOKIE=true`
- [ ] Web root points to `public/` only (see DEPLOYMENT.md)
- [ ] `storage/` and `bootstrap/cache/` writable, nothing else
- [ ] Owner account created with `admin:create`; 2FA enabled
- [ ] Database user limited to this database
- [ ] Hostinger daily backups enabled; restore tested once
- [ ] Never run `DemoSeeder`, `E2eSeeder`, `migrate:fresh` or `db:wipe` in production (the seeders refuse)
