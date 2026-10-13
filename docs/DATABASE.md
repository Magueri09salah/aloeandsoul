# Database

MySQL 8 / MariaDB 10.6+ (`utf8mb4_unicode_ci`, InnoDB). All schema changes are Laravel migrations in `database/migrations`. Timestamps are stored in UTC.

## Money strategy

- Every monetary amount is an **unsigned integer in minor units** (`*_minor`), e.g. `149.50 MAD` → `14950`. No floats, no decimals in arithmetic.
- Precision follows the currency's minor unit (MAD/EUR/USD = 2). `App\Support\Money` handles arithmetic and percentage discounts with integer rounding (half-up, applied once per order).
- Catalogue prices are denominated in the **store currency** (`settings.currency`). Each order stores its own `currency`, so changing the store currency never reinterprets past orders.

## Entity overview

```
categories 1─* products 1─* product_variants 1─* inventory_movements
                    │                 │
                    └─* product_images│
                                      │
carts 1─* cart_items *────────────────┘
                                      │ (restrict delete)
customers 1─* orders 1─* order_items ─┘
                 │  1─* order_events
                 │  *─1 shipping_zones (nullable, snapshotted)
                 │  *─1 coupons 1─* coupon_redemptions
users (admins) ── audit_logs, order_events.user_id, inventory_movements.user_id
settings · pages · faqs · contact_messages · newsletter_subscribers
```

## Why these models (and not others)

| Model | Justification |
|---|---|
| `User` | Admin accounts only, with a `role`. No shopper accounts (guest checkout). |
| `Category`, `Product`, `ProductVariant`, `ProductImage` | Every product has ≥ 1 variant so price, SKU and stock live in one place even for single-option products (C9 flavours need variants). |
| `InventoryMovement` | Ledger explains every stock change (order, cancel, return, manual adjustment). |
| `Cart`, `CartItem` | Server-side guest cart so prices/stock are always re-read from the DB and the cart survives browser restarts. |
| `Customer` | Keyed by phone: needed for order history per customer, repeat-order risk signals and one-per-customer coupons. Minimal fields. |
| `Order`, `OrderItem` | Immutable snapshots of customer details, prices and product names. |
| `OrderEvent` | Single timeline for status changes, payment records and internal notes (replaces separate `OrderStatusHistory`/notes tables). |
| `ShippingZone` | Configurable delivery areas and fees. |
| `Coupon`, `CouponRedemption` | Usage limits enforced transactionally. |
| `Setting` | Key/value JSON for store, contact, home and story content. Avoids a full CMS. |
| `Page`, `Faq` | Editable legal/support pages and FAQ entries. |
| `BlogPost` | Owner-written posts; `status` + `published_at` give draft / published / scheduled. |
| `Collaborator` | People and brands shown on the home page. |
| `ContactMessage`, `NewsletterSubscriber` | Inbound contact and consented opt-ins. |
| `AuditLog` | Administrative accountability. |
| *Not created:* `Address` | No accounts, so no reusable address book. |

## Tables

### users
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar(120) | |
| email | varchar(255) **unique** | |
| password | varchar(255) | bcrypt (12 rounds) |
| role | varchar(20) | `owner` / `manager` / `staff` |
| is_active | bool, default true | Inactive users cannot log in; their sessions are deleted |
| last_login_at | timestamp null | |
| remember_token, timestamps | | |

### categories
`id`, `name` (100), `slug` (120, **unique**), `tagline` (160, null), `description` (text, null), `tint` (7), `motif` (20), `sort_order` (smallint), `is_active` (bool), `seo_title` (70, null), `seo_description` (170, null), timestamps.

### products
| Column | Type | Notes |
|---|---|---|
| category_id | FK → categories, **restrict** | |
| name | varchar(160) | |
| slug | varchar(180) **unique** | |
| summary | varchar(255) null | One-line card text |
| description | text null | Markdown, rendered with HTML stripped |
| specifications | json null | `[{label, value}]`, e.g. Volume / 1 L |
| option_label | varchar(40) null | Variant dimension name, e.g. "Flavour" |
| status | varchar(12) | `draft` / `published` / `archived` |
| is_featured, is_bestseller | bool | |
| sort_order | int | Manual "featured" ordering |
| seo_title, seo_description | varchar null | |
| published_at | timestamp null | |
| Indexes | `(status, category_id, sort_order)`, `(status, is_featured)`, `(status, is_bestseller)` | |

### product_variants
| Column | Type | Notes |
|---|---|---|
| product_id | FK → products, **cascade** | |
| name | varchar(80) null | null for a single default variant |
| sku | varchar(64) null **unique** | Multiple NULLs allowed |
| price_minor | int unsigned null | null = not yet priced (cannot publish) |
| sale_price_minor | int unsigned null | must be < price |
| stock | int unsigned | Never negative (conditional update) |
| track_stock | bool default true | false = made to order / unlimited |
| low_stock_threshold | smallint unsigned null | falls back to store setting |
| is_active | bool | |
| sort_order | smallint | |

### product_images
`product_id` (cascade), `path` (base path, no extension), `widths` (json, generated sizes), `width`, `height` (original), `alt` (160, null), `sort_order`, timestamps.

### inventory_movements
`product_variant_id` (cascade), `change` (int, signed), `stock_after` (int unsigned), `reason` (`order_placed` / `order_cancelled` / `order_returned` / `adjustment` / `restock`), `order_id` (null, nullOnDelete), `user_id` (null, nullOnDelete), `note` (255, null), `created_at`. Index `(product_variant_id, created_at)`.

### carts / cart_items
- `carts`: `token` (char 64, **unique**), `coupon_code` (40, null), timestamps. Pruned 30 days after `updated_at`.
- `cart_items`: `cart_id` (cascade), `product_variant_id` (cascade), `quantity` (smallint unsigned), timestamps. **Unique** `(cart_id, product_variant_id)`.

### customers
`phone` (20, E.164, **unique**), `name` (120), `email` (255, null), `city` (80, null), `first_order_at`, `last_order_at`, `anonymized_at` (null), timestamps. Index `name`.

### shipping_zones
`name` (80), `fee_minor` (int unsigned), `free_over_minor` (int unsigned, null), `estimate` (80, null), `is_active`, `sort_order`, timestamps.

### coupons / coupon_redemptions
- `coupons`: `code` (40, **unique**, uppercase), `description` (160, null), `type` (`percent` / `fixed`), `value` (int unsigned — whole percent 1–100, or minor units), `min_subtotal_minor` (null), `max_uses` (null), `uses_count` (int unsigned), `once_per_customer` (bool), `starts_at`, `ends_at` (null), `is_active`, timestamps.
- `coupon_redemptions`: `coupon_id` (restrict), `order_id` (cascade, **unique**), `customer_id` (null, nullOnDelete), `amount_minor`, `released_at` (null — set when the order is cancelled), `created_at`.

### orders
| Column | Type | Notes |
|---|---|---|
| reference | varchar(20) **unique** | `AS-2610-K7Q3X` (random) |
| idempotency_key | char(36) **unique** | One per checkout page view |
| customer_id | FK null, nullOnDelete | |
| customer_name, customer_phone, customer_email | snapshot | |
| city, address, delivery_notes | snapshot | |
| shipping_zone_id | FK null, nullOnDelete | |
| shipping_zone_name | varchar(80) | snapshot |
| currency | char(3) | |
| subtotal_minor, discount_minor, shipping_minor, total_minor | int unsigned | Server-computed, immutable |
| coupon_id | FK null, nullOnDelete | |
| coupon_code | varchar(40) null | snapshot |
| payment_method | varchar(10) | always `cod` |
| payment_status | varchar(16) | `pending` / `collected` / `not_collected` / `refunded` |
| payment_collected_at, payment_collected_minor, payment_recorded_by | | |
| status | varchar(24) | `OrderStatus` |
| confirmed_at, shipped_at, delivered_at, cancelled_at | timestamp null | |
| courier_name, tracking_number | varchar null | Delivery info recorded by admin |
| risk_flags | json null | e.g. `["repeat_phone_1h"]` |
| privacy_consent_at | timestamp | |
| ip_hash | char(64) null | HMAC of IP for abuse signals (no raw IP stored) |
| Indexes | `(status, created_at)`, `created_at`, `customer_phone`, `(payment_status, status)` | |

### order_items
`order_id` (cascade), `product_id` (**restrict**), `product_variant_id` (**restrict**), `product_name`, `variant_name`, `sku` (snapshots), `unit_price_minor` (price actually charged), `regular_unit_price_minor`, `quantity`, `line_total_minor`. Restrict FKs make it impossible to delete a product or variant that has ever been ordered — archive instead.

### order_events
`order_id` (cascade), `type` (`status` / `payment` / `note` / `delivery`), `from_status`, `to_status` (null), `note` (text, null), `user_id` (null, nullOnDelete), `created_at`. Index `(order_id, created_at)`.

### settings
`key` (varchar 80, PK), `value` (json), `updated_at`. Cached forever under `settings.all`, flushed on write.

### pages, faqs
- `pages`: `slug` (**unique**), `title`, `body` (longtext Markdown), `seo_title`, `seo_description`, `is_published`, `needs_review` (bool — legal text awaiting client approval), timestamps.
- `faqs`: `question` (255), `answer` (text), `sort_order`, `is_published`, `show_on_home`, timestamps.

### blog_posts, collaborators
- `blog_posts`: `title` (160), `slug` (**unique**), `excerpt` (300, null), `body` (longtext Markdown, rendered with HTML stripped), `cover` (json `{path, widths, width, height}`, null), `cover_alt`, `status` (`draft` / `published`), `published_at` (null; in the future = scheduled), `seo_title`, `seo_description`, `user_id` (author, nullOnDelete), timestamps. Index `(status, published_at)`. Public = `status = published AND published_at <= now()`.
- `collaborators`: `name` (120), `description` (255, null), `url` (https only, null), `image` (json, null), `image_style` (`photo` / `logo`), `sort_order`, `is_visible`, timestamps. Index `(is_visible, sort_order)`.

### contact_messages, newsletter_subscribers
- `contact_messages`: `name`, `email`, `phone` (null), `message` (text), `read_at` (null), `ip_hash`, timestamps.
- `newsletter_subscribers`: `email` (**unique**), `consented_at`, `consent_text` (the wording shown), `unsubscribed_at` (null), `ip_hash`, timestamps.

### audit_logs
`user_id` (null, nullOnDelete), `action` (60, e.g. `product.updated`), `subject_type`, `subject_id` (null), `properties` (json — changed fields, PII redacted), `ip_address` (45), `user_agent` (255), `created_at`. Indexes `(subject_type, subject_id)`, `(action, created_at)`.

## Order lifecycle and inventory consistency

1. **Placement** — in one transaction: variants locked `FOR UPDATE` in id order → stock verified → `UPDATE product_variants SET stock = stock - :qty WHERE id = :id AND stock >= :qty` (row count checked) → ledger row `order_placed`. Concurrent checkouts serialise on the variant row lock; the second one sees the reduced stock and fails cleanly.
2. **Cancellation** (any pre-shipping status) — stock incremented back, ledger `order_cancelled`, coupon redemption released.
3. **Return** — admin chooses to restock (default yes) → ledger `order_returned`.
4. **Manual adjustment** — admin sets a new quantity or ± delta with a reason → ledger `adjustment`.

Variants with `track_stock = false` skip steps 1–3.

## Deletion behaviour

| Entity | Behaviour |
|---|---|
| Product / variant with orders | Cannot be deleted (DB restrict + UI). Archive instead. |
| Product without orders | Hard delete allowed (owner/manager, confirmed); images removed from disk. |
| Category with products | Cannot be deleted (restrict). |
| Customer | Never deleted; **anonymised** (name/phone/email/address replaced on customer and their orders, `anonymized_at` set). Order totals remain for accounting. |
| Admin user | Deactivated, not deleted, to keep audit history. |

## Seeders

| Seeder | Runs in | Content |
|---|---|---|
| `DatabaseSeeder` | all environments | 4 categories, 26 draft products (names only), default settings, legal/support pages (`needs_review = true`), factual FAQs. **No users, no prices.** |
| `DemoSeeder` | `local` / `testing` only (refuses in production) | Placeholder prices/stock, publishes products, sample delivery zones and coupon, so the storefront can be developed and tested. |

Production must never run `migrate:fresh`, `migrate:reset`, `db:wipe` or `DemoSeeder`.
