# Aloe & Soul — Product Requirements Document

| | |
|---|---|
| Status | v1.0 — foundation (living document) |
| Owner | Aloe & Soul (founder) |
| Platform | Laravel 12 + Inertia.js + React + TypeScript, MySQL, Hostinger |
| Payment | **Cash on delivery (COD) only** |

> Items marked **[CLIENT]** are business decisions or facts that only the client can supply. The software makes each of them configurable instead of guessing.

---

## 1. Context and goals

Aloe & Soul is a founder-led wellness, beauty, fitness and nutrition brand. The founder spent more than twenty years in London and now lives and works in Morocco (Marrakech, Essaouira, Casablanca). The current site (`aloeandsoul.com`, built on Zyro) is a three-post blog with a contact form and an empty "Store" page; it cannot take orders.

### Business goals

1. **Take orders online** for the four product categories, paid in cash on delivery.
2. **Tell the founder's story** credibly, so the brand is recognisable and trusted rather than a generic reseller.
3. **Let a non-technical owner run the shop**: products, prices, stock, orders, delivery fees, content, without a developer.
4. **Be cheap and reliable to host** on the client's existing Hostinger account.

### Success measures (to instrument post-launch)

- Order completion rate from checkout page view → placed order.
- Share of orders confirmed vs. cancelled / failed delivery (COD quality).
- Time from order placed → confirmed (operational responsiveness).
- Organic sessions to product and category pages.

### Non-goals (explicitly out of scope)

- Online payments of any kind (cards, PayPal, Stripe, wallets). No gateway SDKs, no card fields, no "payment pending" checkout state.
- Customer accounts / login for shoppers (guest checkout only).
- Marketplace, multi-vendor, subscriptions, loyalty points.
- A separate frontend app (Next.js), a Node API, or microservices.

---

## 2. Market assumptions

Evidence from the current site: phone `+212 6 12 03 34 30`, cities Marrakech / Essaouira / Casablanca, French-language contact form. The working assumption is therefore **Morocco, MAD (dirham), English-first UI**, but none of it is hard-coded:

| Setting | Default | Where configured |
|---|---|---|
| Country (phone validation, address) | `MA` | Admin → Settings → Store |
| Currency | `MAD` (2 minor units) | Admin → Settings → Store |
| Delivery areas and fees | none until configured **[CLIENT]** | Admin → Delivery zones |
| Free-delivery threshold | per zone, optional **[CLIENT]** | Admin → Delivery zones |
| UI language | English | French is a post-MVP item (§15) **[CLIENT]** |

Checkout is **closed** until at least one active delivery zone exists, so the shop cannot take an order with an unknown delivery fee.

---

## 3. Brand and experience principles

1. **Founder-led, honest.** The founder's own words carry the story. No invented biography, testimonials, awards, ratings, customer counts or delivery guarantees.
2. **Editorial, not template.** Varied compositions, generous whitespace, real photography. Products are presented calmly, never as a discount wall.
3. **No health claims.** Product copy is factual (name, size, format, how supplied). The founder's rosacea experience is her story, not evidence that a product treats anything. Supplements use neutral descriptions only.
4. **COD made obvious.** Every step says the customer pays in cash when the order arrives. No surprise fees: the delivery fee is shown before the order is placed.
5. **Fast on mid-range phones over mobile data.** Most traffic is expected on mobile.
6. **Accessible by default** (WCAG 2.2 AA target).

---

## 4. Personas and journeys

| Persona | Need | Primary journey |
|---|---|---|
| **Returning buyer** (knows the products, often via the founder personally or WhatsApp) | Re-order quickly | Home/search → product → add to cart → checkout (≤ 2 minutes) |
| **Discovering browser** (arrived from Instagram or search) | Understand who's behind the brand, trust it | Home → Our story → category → product → cart |
| **Gift buyer** | Order for someone else, deliver to another address | Product → checkout with a different name/address |
| **Owner / admin** | See new orders, call to confirm, dispatch, record cash collected | Dashboard → order → confirm → … → delivered → payment collected |
| **Helper / staff** **[CLIENT]** | Process orders without access to settings or prices | Orders list → order → status update |

---

## 5. Public pages

| Route | Purpose | Indexed |
|---|---|---|
| `/` | Brand story + product discovery | Yes |
| `/shop` | Full catalogue with search, filter, sort, pagination | Yes (unfiltered only) |
| `/shop/{category}` | `sport-fitness`, `beauty`, `hygiene`, `nutrition-supplements` | Yes |
| `/products/{slug}` | Product detail | Yes (published only) |
| `/cart` | Full cart page (drawer is the primary cart UI) | No |
| `/checkout` | Guest COD checkout | No |
| `/checkout/confirmation/{reference}` | Order placed (only in the session that placed it) | No |
| `/track-order` | Look up status by order reference + phone | No |
| `/our-story` | Founder story, editorial feature | Yes |
| `/faq`, `/contact`, `/shipping-returns` | Support | Yes |
| `/privacy-policy`, `/terms`, `/legal-notice` | Legal **[CLIENT approval]** | Yes |
| `/sitemap.xml`, `/robots.txt` | Crawlers | — |

Legacy URLs from the Zyro site are permanently redirected (`/my-wellness-journey`, `/busnises-journey`, `/my-freedom-journey` → `/our-story#…`; `/page` → `/shop`; `/blog-list` → `/our-story`).

---

## 6. Catalogue

### Categories (seeded, editable)

| Slug | Name | Seeded products (client-provided names, **drafts**) |
|---|---|---|
| `sport-fitness` | Sport & Fitness | Heat Lotion, Arginine, C9 (Vanilla / Chocolate variants), Vanilla Protein Ultra, Chocolate Protein Ultra |
| `beauty` | Beauty | Marine Collagen, Aloe Cellulose Bio Mask, Eye Contour Cream, Intense Hydrating Care, Three-Step Routine, Firming Complex |
| `hygiene` | Hygiene | Deodorant, Toothpaste, Shampoo, After-Shampoo / Conditioner, Aloe Body Wash, Aloe Body Lotion |
| `nutrition-supplements` | Nutrition & Supplements | Aloe Vera Gel, Aloe Mango, Aloe Peach, Aloe Berry, Bee Propolis, Arctic Sea, Forever Vision, Forever Daily, Forever Calcium |

All 26 seeded products are **drafts with no price, no stock, no images and no description**. They are invisible to shoppers until an admin completes and publishes them. A product **cannot be published** without: a price on every active variant, at least one image, and a description.

### Product model (summary — see DATABASE.md)

- Product: name, slug, category, short summary, description (Markdown, sanitised), specifications (label/value list, e.g. *Volume: 1 L*), status (`draft` / `published` / `archived`), flags (featured, bestseller), SEO title/description, ordered images.
- Variant (every product has ≥ 1): option label (e.g. *Flavour*), name (e.g. *Vanilla*), SKU (unique), price, optional sale price, stock, low-stock threshold, track-stock flag, active flag.

### Discovery

- **Search**: name, summary, SKU (case-insensitive `LIKE`; full-text is post-MVP if the catalogue grows past a few hundred items).
- **Filters**: category, availability (in stock), on sale, price range.
- **Sort**: featured (default), newest, price low→high, price high→low, name A–Z.
- **Pagination**: 12 per page, server-side, real `?page=` links (crawlable).
- Filtered and searched listings are `noindex,follow` with canonical to the unfiltered URL.

### Acceptance criteria

- [ ] Each of the four category URLs renders its own title, description, product grid and metadata.
- [ ] Draft and archived products never appear in listings, search, sitemap or related products.
- [ ] Archived product URL returns **410** with links back to the category; draft returns **404**.
- [ ] Out-of-stock products remain visible, labelled "Out of stock", and cannot be added to the cart.
- [ ] Filter/sort state is in the URL and survives refresh and sharing.

---

## 7. Cart

- Server-side cart, identified by a random token in an encrypted, HTTP-only cookie (30 days). No account needed. Abandoned carts pruned after 30 days.
- Cart drawer (opened after "Add to cart") and full `/cart` page.
- Quantity per line: 1 – 10 (configurable max) and never above available stock.
- Every cart render re-reads current price and stock from the database; lines that became unavailable are flagged and excluded from checkout.

### Acceptance criteria

- [ ] Adding, updating and removing items works without JavaScript errors and without full page reloads.
- [ ] Prices shown in the cart always equal current database prices.
- [ ] Requesting more than available stock is rejected with a clear message and the quantity is clamped.
- [ ] Empty cart shows a helpful state with links to categories.

---

## 8. Checkout (COD)

### Fields

| Field | Required | Rules |
|---|---|---|
| Full name | Yes | 2–120 chars |
| Phone | Yes | Valid for the store country; stored in E.164 (e.g. `+212612345678`) |
| Delivery area | Yes | One of the active delivery zones (determines fee) |
| City | Yes | 2–80 chars |
| Address | Yes | 5–255 chars |
| Email | No | Valid email, used only for order updates |
| Delivery notes | No | ≤ 500 chars |
| Discount code | No | Validated server-side |
| Privacy consent | Yes | Must be accepted; timestamp stored on the order |

### Rules

- Totals are **always computed on the server**: `subtotal − discount + delivery fee = total payable on delivery`. Browser-submitted prices are ignored. The form also sends the total the shopper saw; if it no longer matches (price changed meanwhile), the order is **not** placed and the shopper is shown the updated summary.
- **Idempotency**: each checkout page view issues a one-time key; a resubmission with the same key returns the original order instead of creating a duplicate (unique DB constraint as the final guard).
- **Atomicity**: order, line items, stock reservation, coupon redemption and status history are written in one database transaction with row locks on the affected variants. Any failure rolls back everything.
- Payment method stored as `COD`; payment status starts `pending` and is independent of fulfilment status.
- Checkout is throttled per IP (see SECURITY.md) and is closed if the admin disables ordering or no delivery zone is active.

### Acceptance criteria

- [ ] A valid checkout creates exactly one order with server-computed totals and redirects to confirmation.
- [ ] Double-click / refresh / back-and-resubmit never creates a second order.
- [ ] Ordering more than available stock (including two shoppers racing for the last unit) fails cleanly for one of them; stock never goes negative.
- [ ] Invalid phone, missing address, unaccepted consent are rejected with field-level messages.
- [ ] No payment card fields or gateway scripts exist anywhere in the codebase.

---

## 9. Order lifecycle (COD)

### Fulfilment status

```
PENDING_CONFIRMATION ──► CONFIRMED ──► PROCESSING ──► READY_TO_SHIP ──► SHIPPED ──► DELIVERED ──► RETURNED
        │                    │              │                │              │
        └────────────────────┴──────────────┴────────────────┴──► CANCELLED │
                                                                            └──► FAILED_DELIVERY ──► SHIPPED (retry)
                                                                                        └──────────► RETURNED
```

- Any transition not drawn above is rejected by the server.
- Cancelling (only possible before shipping) **releases reserved stock**.
- `RETURNED` lets the admin choose whether goods go back into stock (default: yes).
- Every transition records who, when, from, to and an optional note.

### Payment collection status (independent)

| Status | Meaning | Allowed when |
|---|---|---|
| `pending` | Not yet collected | Default |
| `collected` | Courier collected cash | Order is `DELIVERED` |
| `not_collected` | Will not be collected | Order is `CANCELLED`, `FAILED_DELIVERY` or `RETURNED` |
| `refunded` | Cash returned to customer | Was `collected`, order now `RETURNED` |

Dashboard revenue counts **only** `DELIVERED` + `collected` orders. "Order value" (all non-cancelled orders) is shown separately and labelled as such.

### Inventory policy

**Stock is reserved (decremented) when the order is placed**, inside the order transaction. This prevents overselling between placement and phone confirmation, which for COD can take hours. Stock is released automatically on cancellation, and on return if the admin marks goods as resellable. Every change is written to an inventory movement ledger.

### Order references and tracking

- Reference format: `{PREFIX}-{YYMM}-{5 random chars}` (e.g. `AS-2610-K7Q3X`), random and non-sequential; prefix configurable.
- The confirmation page is only viewable in the browser session that placed the order.
- `/track-order` shows status after matching **reference + phone number** (rate-limited). No order data is reachable by guessing a URL.

### Fraud / abuse signals (flags only, never auto-reject)

- Several orders from the same phone in a short window.
- Phone has previous failed deliveries or returns.
- Unusually large quantities.

---

## 10. Admin dashboard (`/admin`)

### Roles

| Capability | Owner | Manager | Staff |
|---|:-:|:-:|:-:|
| Dashboard | ✓ | ✓ | ✓ |
| Orders: view, update status, notes, record payment, packing slip | ✓ | ✓ | ✓ |
| Products, categories, inventory adjustments | ✓ | ✓ | view |
| Customers: view | ✓ | ✓ | ✓ |
| Customers: anonymise | ✓ | — | — |
| Discount codes, delivery zones | ✓ | ✓ | — |
| Content (home, story, FAQ, pages), messages, subscribers | ✓ | ✓ | — |
| Store settings, admin users, audit log | ✓ | — | — |

No public registration. The first owner is created with `php artisan admin:create` on the server.

### Modules and acceptance criteria

- **Overview**: orders today; awaiting confirmation; awaiting fulfilment; delivered; cancelled; order value; collected sales; bestsellers; low stock; recent orders; launch checklist. Date range: today / 7 / 30 / 90 days.
  - [ ] Cancelled and uncollected orders are never counted as sales.
- **Products**: list (search, filter by category/status, sort, paginate), create, edit, variants, image upload + reorder + alt text, SEO fields, publish / archive / restore; delete only when never ordered.
  - [ ] Duplicate SKU / slug rejected with a field error.
  - [ ] Publishing an incomplete product is blocked with a list of what's missing.
- **Orders**: list (search by reference, name, phone; filter by status, payment status, date), detail, valid transitions only, internal notes, history, payment recording, printable packing slip, risk flags.
- **Inventory**: stock per variant, low-stock filter, manual adjustment with reason, movement history.
- **Customers**: list (search name/phone), detail with order history and counts, anonymise (owner).
- **Discount codes**: percentage / fixed, start/end, total usage limit, one-per-customer option, minimum order, active toggle.
- **Delivery zones**: name, fee, free-delivery threshold, estimate text, active, sort order.
- **Content**: announcement bar, home hero and sections, featured products, story chapters, FAQ entries, editable pages, contact details.
- **Settings**: store name, contact, currency, country, order prefix, default low-stock threshold, COD instructions, social links, ordering on/off.
- **Users** (owner): invite/create admin, change role, deactivate.
- **Audit log** (owner): who did what, when.

---

## 11. Content and founder story

- `/our-story` uses the founder's published texts (Wellness, Business and Freedom journeys) **verbatim** as the authoritative copy, editable in admin.
- Facts from the brief that are not yet in the founder's own words (20+ years in London; interior architecture, art and business; Chris Immo, properties and riads) are shown only as short factual timeline entries. **[CLIENT]** The full narrative referred to in the brief was not supplied; the story chapters are editable so it can be added.
- Tagline: **Aloe & Soul — Nourish the Body. Inspire the Soul.**

---

## 12. SEO and analytics

See SEO.md. Summary: server-rendered meta (title, description, canonical, Open Graph, JSON-LD) in the initial HTML of every public page; XML sitemap; robots rules; Product structured data only with real price/availability; no fake ratings. Analytics: none bundled (privacy); a privacy-friendly analytics snippet can be added post-MVP **[CLIENT]**.

---

## 13. Non-functional requirements

| Area | Requirement |
|---|---|
| Accessibility | WCAG 2.2 AA: keyboard operable, visible focus, labelled controls, 4.5:1 text contrast, reduced-motion respected |
| Performance | LCP < 2.5 s and CLS < 0.1 on 4G mid-range mobile for home, category and product pages; storefront JS excludes admin code |
| Security | See SECURITY.md (OWASP-aligned: authz on every mutation, CSRF, rate limits, upload hardening, audit log) |
| Privacy | Collect only what delivery needs; consent timestamp; customer anonymisation; no third-party trackers by default (Morocco law 09-08 / CNDP **[CLIENT]**) |
| Reliability | No partial orders; nightly DB backups (Hostinger) + documented restore |
| Hosting | Runs on Hostinger shared/cloud PHP hosting with no Node.js process in production |

---

## 14. Error handling and edge cases

| Case | Behaviour |
|---|---|
| Product unpublished/archived while in a cart | Line flagged "no longer available", excluded from checkout |
| Price changed between cart view and submit | Order not placed; refreshed summary shown with a message |
| Stock runs out during checkout | Order not placed; message names the product; cart quantity clamped |
| Discount code expired or used up between apply and submit | Order not placed; code removed with a message |
| Double submit / network retry | Same order returned (idempotency) |
| Ordering disabled or no delivery zones | Checkout shows a closed notice; cart still works |
| Too many requests | 429 page/message with retry hint; normal customers never hit it |
| Admin edits a product that has orders | Past orders keep their snapshot of name, SKU, price |
| Server error | Generic error page with request ID; details only in server logs |

---

## 15. Release scope

### MVP (this build)

Everything in §§5–12: storefront, four categories, product pages, cart, COD checkout, confirmation and tracking, founder story, support/legal pages, full admin (overview, products, variants, images, categories, orders, inventory, customers, discount codes, delivery zones, content, settings, users, audit log), contact form, newsletter opt-in with consent, SEO, rate limiting, tests, deployment docs.

### Important post-MVP

- French UI (and Arabic with RTL), with translated product content.
- Order notification by email to the customer and WhatsApp click-to-chat confirmation templates for the admin.
- Two-factor authentication for admin accounts.
- Courier integration (e.g. a Moroccan delivery provider API) for tracking numbers.
- CSV export of orders and customers.

### Optional future

- Product reviews (genuine, moderated, verified-purchase only).
- Journal/blog.
- Bundles and rituals as purchasable sets.
- Privacy-friendly analytics dashboard.

---

## 16. Testing strategy

| Layer | Tool | Covers |
|---|---|---|
| Unit | Pest | Money, pricing, discounts, delivery fee, phone normalisation, status transitions, payment status rules |
| Feature / integration | Pest (SQLite in-memory + MySQL group) | Catalogue visibility, cart, checkout validation, transactional order creation, idempotency, inventory, coupons, rate limits, admin authz, product and order mutations, SEO responses |
| Concurrency | Pest + parallel PHP processes on MySQL | Two+ simultaneous orders for the last unit → exactly one succeeds |
| Component | Vitest + React Testing Library | Quantity stepper, price display, cart line, checkout summary |
| End-to-end | Playwright | Browse → product → cart → checkout → confirmation; invalid checkout; duplicate submit; admin login → edit product → process order |

Tests never run against production; the test environment uses its own database.

---

## 17. Open questions for the client

1. **Distributor terms.** The product names correspond to Forever Living Products. Please confirm your distributor agreement permits selling through your own website, with your own prices and cash on delivery, and using product names and official images.
2. Prices, stock levels, descriptions and official images for each product (or permission to use Forever's official images).
3. Delivery areas, fees, free-delivery threshold and typical delivery times.
4. Return and exchange policy.
5. Legal identity for the legal notice (business name, registration number / ICE, address) and CNDP declaration status.
6. Whether orders should be confirmed by phone/WhatsApp before dispatch (the workflow supports it either way).
7. Language priority: English only at launch, or French too?
8. Social media links (Instagram, Facebook, TikTok, YouTube).
9. The full founder narrative referred to in the brief (Chris Immo, riads, London years), if it should appear beyond the three published journeys.
10. Hostinger plan type (shared Premium/Business, Cloud, or VPS) and whether SSH is enabled.
