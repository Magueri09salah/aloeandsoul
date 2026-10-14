/** Amounts are always integers in the currency's minor unit (e.g. centimes). */
export type MinorUnits = number;

export interface ImageData {
    src: string;
    srcset: string;
    width: number;
    height: number;
    alt: string;
}

export interface CategoryRef {
    name: string;
    slug: string;
    tint: string;
}

export interface NavCategory extends CategoryRef {
    tagline: string | null;
    motif: Motif;
}

export type Motif = 'horizon' | 'circle' | 'wave' | 'leaf';

export interface ProductCard {
    id: number;
    name: string;
    slug: string;
    summary: string | null;
    category: CategoryRef;
    image: ImageData | null;
    price_minor: MinorUnits | null;
    regular_price_minor: MinorUnits | null;
    price_varies: boolean;
    on_sale: boolean;
    in_stock: boolean;
    is_bestseller: boolean;
    variant_count: number;
    option_label: string | null;
    quick_add_variant_id: number | null;
}

export interface ProductVariant {
    id: number;
    name: string | null;
    sku: string | null;
    price_minor: MinorUnits;
    regular_price_minor: MinorUnits;
    on_sale: boolean;
    in_stock: boolean;
    max_quantity: number;
    low_stock: boolean;
}

export interface ProductDetail extends ProductCard {
    description_html: string | null;
    /** Description, benefits, how to use, ingredients, nutrition: only the filled ones, in order. */
    sections: { id: string; title: string; html: string }[];
    specifications: { label: string; value: string }[];
    /** The size details row ("60 tablets"), in the page language. */
    size: string | null;
    images: ImageData[];
    variants: ProductVariant[];
}

export interface CartLine {
    id: number;
    variant_id: number;
    product_name: string;
    product_slug: string;
    variant_name: string | null;
    option_label: string | null;
    image: ImageData | null;
    tint: string;
    unit_price_minor: MinorUnits | null;
    regular_price_minor: MinorUnits | null;
    quantity: number;
    max_quantity: number;
    line_total_minor: MinorUnits;
    issue: string | null;
}

export interface CartView {
    lines: CartLine[];
    count: number;
    subtotal_minor: MinorUnits;
    has_issues: boolean;
    coupon_code: string | null;
}

export interface Quote {
    currency: string;
    subtotal_minor: MinorUnits;
    discount_minor: MinorUnits;
    shipping_minor: MinorUnits | null;
    total_minor: MinorUnits | null;
    coupon_code: string | null;
    coupon_error: string | null;
    zone_id: number | null;
    free_shipping_remaining_minor: MinorUnits | null;
}

export interface Breadcrumb {
    name: string;
    url: string;
}

export interface SeoProps {
    title: string;
    description: string;
    canonical: string | null;
    robots: string;
}

export interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'manager' | 'staff';
    role_label: string;
    permissions: string[];
    two_factor_enabled: boolean;
}

export interface SharedProps {
    [key: string]: unknown;
    store: {
        name: string;
        tagline: string;
        currency: string;
        country: string;
        locale: string;
        timezone: string;
        ordering_enabled: boolean;
    };
    flash: { success: string | null; error: string | null };
    errors: Record<string, string>;
    seo?: SeoProps;
    // Storefront only
    contact?: {
        email: string;
        phone: string;
        phone_display: string | null;
        whatsapp: string | null;
        locations: string[];
        hours: string;
    };
    social?: Partial<Record<'instagram' | 'facebook' | 'tiktok' | 'youtube', string>>;
    announcement?: { text: string; link_label: string; link_url: string } | null;
    newsletter_enabled?: boolean;
    navigation?: NavCategory[];
    cart?: CartView;
    /** This page in each language: { en: url, fr: url }. */
    alternates?: Record<string, string>;
    // Admin only
    auth?: { user: AdminUser | null };
}

/** Laravel LengthAwarePaginator serialised to JSON. */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Option {
    value: string;
    label: string;
}

export interface PostCard {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    cover: ImageData | null;
    published_at: string | null;
    reading_minutes: number;
}

export interface PostDetail extends PostCard {
    body_html: string;
    updated_at: string | null;
}

export interface Collaborator {
    id: number;
    name: string;
    description: string | null;
    url: string | null;
    image: ImageData | null;
    image_style: 'photo' | 'logo';
}
