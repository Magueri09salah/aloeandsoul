# Aloe & Soul — Design System

Creative direction: **premium natural wellness × contemporary editorial lifestyle × understated luxury**, grounded in where the founder actually is: Moroccan light, riad architecture, the Essaouira dunes, and the aloe plant in the logo.

Tokens live in [`resources/css/app.css`](../resources/css/app.css) (`@theme`) and are the single source of truth. This document explains them.

> **November 2026: the storefront was restyled** in a calmer grocery-boutique layout (two-row header with a search bar, product rows with arrows, rounded product tiles, olive footer). Its values are in **§12** and replace §2–§11 on the storefront where they differ. The admin keeps the values below.

---

## 1. Sources

| Source | What it gave us |
|---|---|
| Logo (`database/assets/logo.jpg`) | Watercolour aloe with a figure and a rising sun inside a fine gold circle; olive classical-roman wordmark ≈ `#5C5A31` with a gold ampersand ≈ `#9D823F`; tagline "Nourish the body • Inspire the soul"; paper-white ground `#FFFBF8` |
| Current site CSS | Ivory `#FBF9F4`, aloe-ink `#232B22`, gold `#C89F56`, sand `#E3DAC9`; Libre Caslon + Karla |
| Founder photography | Warm desert light, white linen, pale sky — the imagery sets the warmth, so the UI stays calm |

We **kept** the ivory, the aloe-ink and Libre Caslon (brand continuity) and **replaced** the bright gold with the logo's quieter dune tone, and Karla with Hanken Grotesk (better at small UI sizes and in dense admin tables).

## 2. Principles

1. **One bold gesture.** The riad arch frames founder imagery (home hero, story). Everything else is quiet.
2. **Photography carries warmth; UI carries order.** No gradients, no glass, no decorative shadows.
3. **Text in sentence case.** No tracked all-caps eyebrows. The wordmark is the only all-caps element.
4. **Structure means something.** Rules separate content types, numbers appear only on true sequences (checkout steps, story timeline, ritual steps).
5. **Calm commerce.** Prices and actions are clear and close to the product; no countdowns, no urgency banners.

---

## 3. Colour

### Core palette

| Token | Hex | Use |
|---|---|---|
| `ivory` | `#FBF9F4` | Page background |
| `ink` | `#232B22` | Text, primary buttons, dark sections |
| `aloe` | `#4A5E4C` | Links, focus ring, selected states, success |
| `dune` | `#A08A66` | Logo mark, decorative rules, large display accents only |
| `dune-deep` | `#6E5C40` | Small text that needs warmth (prices on sale, captions) |
| `sand` | `#EDE5D8` | Section blocks, product image background (default) |
| `stone` | `#615F57` | Secondary text |
| `line` | `#DDD6CA` | Borders, dividers |
| `clay` | `#A23B2C` | Errors, destructive actions |
| `white` | `#FFFFFF` | Inputs, admin surfaces |

### Contrast (WCAG 2.2)

| Pair | Ratio | Allowed for |
|---|---|---|
| ink on ivory | 13.9 : 1 | All text |
| aloe on ivory | 6.7 : 1 | All text, links |
| stone on ivory / sand / any tint | 6.1 / 5.1 / ≥ 4.9 : 1 | Body and secondary text |
| dune-deep on ivory | 6.1 : 1 | All text |
| dune on ivory | 3.2 : 1 | **Large text (≥ 24 px) and decoration only** |
| ivory on ink | 13.9 : 1 | Buttons, dark sections |
| clay on white / ivory | 6.6 / 6.2 : 1 | Error text |
| category accents on their tint | ≥ 5.0 : 1 | All text |

### Category tints

Each category gets a tint used for its header band and as the product-image backdrop, so a category is recognisable at a glance without breaking the system.

| Category | Tint | Accent | Motif |
|---|---|---|---|
| Sport & Fitness | `#EDE3D1` (dune light) | `dune-deep` | Horizon line |
| Beauty | `#EFDFD6` (rose clay) | `#7E5042` | Circle |
| Hygiene | `#DFE6E4` (Essaouira sea mist) | `#4B6464` | Wave |
| Nutrition & Supplements | `#E1E7DA` (aloe light) | `aloe` | Leaf |

---

## 4. Typography

| Role | Family | Weights | Notes |
|---|---|---|---|
| Display | **Libre Caslon Display** | 400 | Headlines ≥ 32 px only. High contrast, so never small. |
| Editorial text | **Libre Caslon Text** | 400, 400 italic, 700 | Story body, pull quotes, product descriptions on desktop |
| Interface | **Hanken Grotesk** (variable) | 400–650 | Navigation, product info, forms, buttons, all of admin |

All fonts are self-hosted from `@fontsource` packages (no Google Fonts request: faster, no third-party data transfer). `font-display: swap` with metric-compatible fallbacks (`Georgia` / `system-ui`) and `size-adjust` keeps layout shift low.

### Scale (major third, 16 px base)

| Token | Size | Line height | Usage |
|---|---|---|---|
| `text-xs` | 12 | 1.5 | Legal fine print, badges |
| `text-sm` | 14 | 1.5 | Secondary UI, table cells |
| `text-base` | 16 | 1.6 | UI body |
| `text-lg` | 18 | 1.6 | Lead paragraphs |
| `text-editorial` | 19 → 20 | 1.7 | Serif story body |
| `text-2xl` | 25 | 1.3 | Card titles (serif) |
| `text-3xl` | 31 | 1.2 | Section titles |
| `text-display-sm` | clamp(32, 4vw, 44) | 1.1 | Page titles |
| `text-display` | clamp(40, 6vw, 72) | 1.04 | Section statements |
| `text-display-xl` | clamp(48, 8.5vw, 112) | 0.98 | Hero only |

Measure: editorial text max **66ch**, UI paragraphs max **72ch**. Display text tracking `-0.01em`.

---

## 5. Space, layout, shape

- **Spacing** uses Tailwind's 4 px scale. Section rhythm: `py-20` mobile → `py-32` desktop. Within a component, steps of 4/8/12/16/24.
- **Container**: `max-w-[1320px]`, gutters 16 px (mobile), 24 px (≥ 640), 40 px (≥ 1024). Editorial column `max-w-[66ch]`.
- **Breakpoints**: Tailwind defaults (`sm 640`, `md 768`, `lg 1024`, `xl 1280`). Designed mobile-first at 375 px.
- **Alignment**: left-aligned by default. Centre only short statements (tagline, empty states).
- **Radius hierarchy** (deliberately different per role):
  - Buttons, chips, quantity steppers: `rounded-full` (echoes the logo circle).
  - Inputs, admin cards, dropdowns: `6px`.
  - Product and editorial imagery: `0` (photography edge to edge), except the **arch** (`border-radius: 999px 999px 0 0`) reserved for founder imagery.
- **Shadows**: none on the storefront. One shadow token (`shadow-overlay`) for drawers, menus and dialogs only.
- **Borders**: 1 px `line`. Used for inputs, table rows and to separate the summary in checkout — not around product cards.

## 6. Components (primitives in `resources/js/components/ui`)

| Primitive | Variants / states |
|---|---|
| `Button` | `primary` (ink), `secondary` (outline ink), `ghost`, `link`, `destructive`; sizes `sm 36`, `md 44`, `lg 52`; loading (spinner + `aria-busy`), disabled |
| `Input`, `Textarea`, `Select`, `Checkbox`, `Switch` | default, focus (2 px aloe ring, 2 px offset), invalid (`aria-invalid` → clay border + message), disabled |
| `Field` | Label + control + hint + error, wired with `aria-describedby` |
| `Badge` | neutral, success, warning, danger, info (admin statuses); `sale`, `out-of-stock`, `bestseller` (storefront) |
| `Sheet` | Right (cart), left (mobile nav), bottom (mobile filters) |
| `Dialog`, `AlertDialog` | Confirm destructive admin actions |
| `QuantityStepper` | Pill with −/+ buttons (44 px targets), min/max enforcement, announces value |
| `Price` | Formats minor units for the page language (`290 MAD`, `289.50 MAD` in English; `1 077 MAD`, `289,50 MAD` in French); sale price shows the original struck through with accessible text |
| Language switch (`components/store/language-switcher.tsx`) | Header: one link to the other language, its name written in that language (*Français* / *English*), shortened to *FR* / *EN* under 640 px. Mobile menu and footer: both languages, the current one underlined. A full page load, so the whole document changes language |
| `ResponsiveImage` | `srcset`/`sizes`, explicit width/height (no CLS), lazy by default, `priority` for LCP |
| `Skeleton` | Product card and table row placeholders |
| `Toaster` (sonner) | Success/error feedback for async actions |

### Language and layout

- French runs about 15–20 % longer than English: buttons and labels wrap instead of truncating, and fixed widths are avoided in the storefront.
- French typography: a no-break space before `:` `;` `?` `!` and inside « », decimal comma and spaced thousands (`1 077,50`).
- On phones the header holds menu, logo, language, search and cart from 360 px: below 640 px the mark and wordmark are set slightly smaller and the gaps tighter; every button keeps its 44 px target.
- The header logo is two images cut from the client's logo: the mark (aloe, figure and sun, without the circle) and the wordmark as drawn. Both are transparent PNGs with 2× versions; `php database/assets/build-brand-assets.php` regenerates them with the icons and the sharing image.

## 7. Motion

| Token | Value | Use |
|---|---|---|
| `duration-fast` | 150 ms | Hover, press |
| `duration-base` | 250 ms | Menus, accordions |
| `duration-slow` | 400 ms | Drawers |
| `ease-out-soft` | `cubic-bezier(.2,.7,.2,1)` | Everything |

- **One orchestrated moment**: on the home page the hero arch image reveals upward once (clip-path, 700 ms).
- Product images zoom 1.03× on hover (desktop pointer only).
- Cart drawer slides in; the cart count updates with a short scale pulse to confirm the add.
- No scroll-triggered fade-ins on every section.
- Everything above is wrapped in `motion-safe:`; with `prefers-reduced-motion: reduce` transitions become instant.

## 8. Feedback states

| State | Storefront | Admin |
|---|---|---|
| Loading | Button spinner + disabled; skeletons only where data loads after navigation | Same + top progress bar |
| Error (field) | Clay text under the field, field `aria-invalid`, focus moves to the first invalid field | Same |
| Error (page) | Inline alert at top of form summarising what to fix | Same |
| Success | Toast ("Added to cart") or confirmation page | Toast ("Product saved") |
| Empty | Short plain sentence + next action ("Your cart is empty. Browse the shop.") | Same pattern with primary action |

## 9. Focus and accessibility

- Focus ring: `outline 2px solid aloe; outline-offset 2px`, never removed.
- Skip link to main content on every page.
- Touch targets ≥ 44 × 44 px.
- Images: meaningful `alt` (admin-editable per product image); decorative motifs `aria-hidden`.
- Forms: visible labels (no placeholder-only), errors announced via `aria-live="polite"`.

## 10. Storefront vs. admin

Same tokens and typefaces; different information architecture.

| | Storefront | Admin |
|---|---|---|
| Background | ivory | `#F4F2EC` page, white panels |
| Type | Display serif for headlines, sans for UI | Sans throughout; serif only for page titles |
| Density | Generous | Compact tables (40 px rows), 14 px text |
| Navigation | Top bar + mega menu, mobile sheet | Left sidebar (collapsible on mobile) |

## 11. Imagery guidelines

- **Founder & lifestyle**: real photography supplied by the client. The current dunes portrait (from the client's existing site) is used on the home hero and story page and can be swapped in admin.
- **Products**: official product images only (bundled Forever photographs, or the client's uploads). Without one, a neutral tinted placeholder with the leaf mark — never a fake packshot. Product tiles show the first image in a 4:5 frame; the product page shows every image whole (`object-contain`) in a square, swipeable carousel over the category tint, with arrows, a counter and thumbnails. Product information below it uses collapsible sections (native `<details>`), the description open.
- **Formats**: uploads are re-encoded to WebP at 480 / 960 / 1600 px wide; originals are not served.

---

## 12. Storefront theme (November 2026)

The storefront `<body class="storefront">` overrides the tokens in `app.css` (`body.storefront { … }`): same token names, new values, so every component follows and the admin (`body.admin`) keeps its own.

### Colour

| Token | Storefront value | Use |
|---|---|---|
| `ivory` | `#F8F6F0` | Page background |
| `ink` | `#4A433D` warm brown-grey | Headings, main text, the cart button on hover |
| `stone` | `#726A63` | Secondary text, outline buttons, the cart button |
| `aloe` | `#5C5A31` logo olive | Announcement bar, footer, focus ring |
| `dune` | `#9D823F` logo gold | Italic accent words in large headings, category motifs, active tab underline |
| `dune-deep` / `primary` | `#85692E` deep gold (white text 5.2 : 1) | Primary buttons, category labels on cards, sale badge, current page |
| `primary-hover` | `#6E5725` | Primary button hover |
| `sand` / `panel` | `#EFEBE3` / `#F2EFE8` | Hero band, founder-story panel, delivery panel |
| `mist` | `#EFEDE7` | Product photo frames (packshots are transparent) |
| `leaf` | `#767849`, the green of the logo's aloe leaves (white text 4.6 : 1) | Home hero band |
| `line` | `#E3DED5` | Borders and dividers |

### Type

- **DM Serif Display** (400 and italic) for headings; **Libre Franklin** (variable, with italics) for everything else, long texts included. Both self-hosted with `@fontsource`.
- **Heading signature**: the last word of a section title is set in italic (`Accented` in `components/store/section-heading.tsx`), e.g. "Shop by *category*". One-word titles stay upright. In the philosophy and founder-story headings the italic word is gold.
- Buttons, small labels (category on a card, badges, dates, "Read more") are **upper case** with slight tracking. In the admin buttons stay in sentence case (`--button-case`).
- Capitals make French labels long (*DÉCOUVRIR LA COLLECTION*). A button that sits in a narrow box takes `wrap` (`<Button wrap>`): it keeps its height while the label fits and breaks onto a second line instead of running out of the box. Used on the product rows, hero, founder story, product page and product tiles; tiles also use smaller button text on phones.

### Layout and components

- **Header**: announcement bar (olive); top row with logo, a search bar, *Our story*, language and a round cart button; a centred second row with all products, the four categories, Blog, FAQ and Contact. The second row folds away once the page scrolls. On phones: menu, logo, language, search and cart; below 360px the logo shows the mark only.
- **Product tile** (`ProductCard`): square photo frame with `rounded-2xl` on `mist`, badges top left (white pills; sale in gold), category in gold capitals, name, price, then a full-width outline button (*Add to cart*, *Choose an option* for products with options, or *Out of stock*).
- **Product rows** (`ProductRail`): title, text, primary button and round arrows on the left (a 19.5rem column); cards in a horizontal scroll-snap row on the right. Below 1024px the button and arrows go under the cards; on phones the button is full width and the arrows are hidden (the row is swiped). Used for the founder's picks, the routine, bestsellers and related products.
- **Home**: hero band (white text on the logo's leaf green, photo on the other half) → round category tiles → *How ordering works* (three icons, divided) → product rows → philosophy (heading left, text and *Our story* link right) → founder story panel → blog → collaborators → FAQ.
- **Shop**: a banner in the category's own tint (the colour of its circle on the home page; sand for all products and search) with the breadcrumb, centred title, description, the category's animated motif on wide screens and the categories as underlined tabs; then a sticky toolbar (*Filters* · product count · sort; on phones the count moves above the grid), a 2/3/4-column grid. Filters open in a panel from the left with collapsible groups (search, availability, price) and *Clear filters* / *Apply*.
- **Product page**: rounded gallery on the left (sticky on desktop); on the right category, name (sans), price and bestseller badge, quantity and *Add to cart*, *View similar products*, a panel with cash on delivery and delivery price, then the information sections as + / − accordions (description open, details, delivery areas).
- **Blog**: banner of sand ripples (`public/brand/dunes-banner-1200.webp`, cut from the founder photograph by `build-brand-assets.php`) with the title in white; cards in three columns with a rounded 5:4 photo, date in capitals, title, three-line excerpt and *Read more*. A post with a cover shows it as a rounded banner with the title over it.
- **Footer**: olive, the store tagline in large serif, newsletter (white field, gold button), three link columns, legal links, languages and a *Payment in cash on delivery* pill.
- **Radius**: buttons and pills `rounded-full`; photos and panels `rounded-2xl` / `rounded-3xl`. No shadows except overlays.
