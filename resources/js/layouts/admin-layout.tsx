import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Boxes,
    ClipboardList,
    FileText,
    Gauge,
    HelpCircle,
    Home,
    Inbox,
    LogOut,
    Mail,
    Menu,
    Package,
    ScrollText,
    Settings,
    Shapes,
    Tag,
    Truck,
    UserCog,
    Users,
    type LucideIcon,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';

import { FlashToaster } from '@/components/ui/flash-toaster';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { useShared } from '@/hooks/use-shared';
import { cn } from '@/lib/utils';

interface NavItem {
    href: string;
    label: string;
    icon: LucideIcon;
    permission: string;
}

const NAV: { heading: string | null; items: NavItem[] }[] = [
    { heading: null, items: [{ href: '/admin', label: 'Overview', icon: Gauge, permission: 'dashboard.view' }] },
    {
        heading: 'Sales',
        items: [
            { href: '/admin/orders', label: 'Orders', icon: ClipboardList, permission: 'orders.view' },
            { href: '/admin/customers', label: 'Customers', icon: Users, permission: 'customers.view' },
            { href: '/admin/coupons', label: 'Discount codes', icon: Tag, permission: 'marketing.manage' },
            { href: '/admin/delivery-zones', label: 'Delivery areas', icon: Truck, permission: 'marketing.manage' },
        ],
    },
    {
        heading: 'Catalogue',
        items: [
            { href: '/admin/products', label: 'Products', icon: Package, permission: 'catalog.view' },
            { href: '/admin/categories', label: 'Categories', icon: Shapes, permission: 'catalog.manage' },
            { href: '/admin/inventory', label: 'Stock', icon: Boxes, permission: 'catalog.view' },
        ],
    },
    {
        heading: 'Content',
        items: [
            { href: '/admin/content/home', label: 'Home page', icon: Home, permission: 'content.manage' },
            { href: '/admin/content/story', label: 'Our story', icon: BookOpen, permission: 'content.manage' },
            { href: '/admin/pages', label: 'Policy pages', icon: FileText, permission: 'content.manage' },
            { href: '/admin/faqs', label: 'FAQ', icon: HelpCircle, permission: 'content.manage' },
            { href: '/admin/messages', label: 'Messages', icon: Inbox, permission: 'content.manage' },
            { href: '/admin/subscribers', label: 'Subscribers', icon: Mail, permission: 'content.manage' },
        ],
    },
    {
        heading: 'Administration',
        items: [
            { href: '/admin/settings', label: 'Settings', icon: Settings, permission: 'settings.manage' },
            { href: '/admin/users', label: 'Team', icon: UserCog, permission: 'users.manage' },
            { href: '/admin/audit-log', label: 'Activity log', icon: ScrollText, permission: 'audit.view' },
        ],
    },
];

function Navigation({ onNavigate }: { onNavigate?: () => void }) {
    const { auth } = useShared();
    const { url } = usePage();
    const permissions = auth?.user?.permissions ?? [];
    const path = url.split('?')[0];

    const isActive = (href: string) => (href === '/admin' ? path === '/admin' : path === href || path?.startsWith(`${href}/`));

    return (
        <nav aria-label="Admin" className="flex-1 overflow-y-auto px-3 py-4">
            {NAV.map((group, gi) => {
                const items = group.items.filter((i) => permissions.includes(i.permission));
                if (items.length === 0) return null;
                return (
                    <div key={gi} className={cn(gi > 0 && 'mt-6')}>
                        {group.heading && <p className="mb-1.5 px-3 text-xs font-medium text-ivory/50">{group.heading}</p>}
                        <ul className="space-y-0.5">
                            {items.map(({ href, label, icon: Icon }) => (
                                <li key={href}>
                                    <Link
                                        href={href}
                                        onClick={onNavigate}
                                        aria-current={isActive(href) ? 'page' : undefined}
                                        className={cn(
                                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm text-ivory/80 transition-colors hover:bg-ivory/10 hover:text-ivory',
                                            isActive(href) && 'bg-ivory/12 font-medium text-ivory',
                                        )}
                                    >
                                        <Icon className="size-4 shrink-0" aria-hidden="true" />
                                        {label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                );
            })}
        </nav>
    );
}

function UserBox() {
    const { auth } = useShared();
    const user = auth?.user;
    if (!user) return null;

    return (
        <div className="border-t border-ivory/10 p-3">
            <Link href="/admin/profile" className="block rounded-md px-3 py-2 hover:bg-ivory/10">
                <span className="block truncate text-sm font-medium text-ivory">{user.name}</span>
                <span className="block text-xs text-ivory/60">{user.role_label}</span>
            </Link>
            <div className="mt-1 flex gap-1">
                <a href="/" target="_blank" rel="noopener" className="flex-1 rounded-md px-3 py-2 text-xs text-ivory/70 hover:bg-ivory/10 hover:text-ivory">
                    View shop
                </a>
                <button
                    type="button"
                    onClick={() => router.post('/admin/logout')}
                    className="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs text-ivory/70 hover:bg-ivory/10 hover:text-ivory"
                >
                    <LogOut className="size-3.5" aria-hidden="true" />
                    Sign out
                </button>
            </div>
        </div>
    );
}

export default function AdminLayout({ children }: { children: ReactNode }) {
    const [open, setOpen] = useState(false);

    const sidebarHeader = (
        <Link href="/admin" className="flex items-center gap-3 px-6 py-5">
            <img src="/brand/logo-mark-96.png" alt="" width={32} height={32} className="size-8 rounded-full bg-ivory p-0.5" />
            <span className="font-display tracking-[0.14em] text-ivory">ALOE &amp; SOUL</span>
        </Link>
    );

    return (
        <div className="min-h-dvh lg:pl-64">
            <Head>
                <meta head-key="robots" name="robots" content="noindex,nofollow" />
            </Head>
            <a href="#admin-main" className="sr-only z-50 rounded bg-ink px-4 py-2 text-ivory focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
                Skip to content
            </a>

            <aside className="fixed inset-y-0 left-0 hidden w-64 flex-col bg-ink lg:flex">
                {sidebarHeader}
                <Navigation />
                <UserBox />
            </aside>

            <div className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-white px-4 lg:hidden">
                <button type="button" onClick={() => setOpen(true)} className="-ml-2 inline-flex size-10 items-center justify-center rounded-md hover:bg-panel" aria-label="Open admin menu">
                    <Menu className="size-5" aria-hidden="true" />
                </button>
                <span className="font-display tracking-[0.14em]">ALOE &amp; SOUL</span>
            </div>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="left" title="Admin menu" hideTitle className="bg-ink text-ivory">
                    {sidebarHeader}
                    <Navigation onNavigate={() => setOpen(false)} />
                    <UserBox />
                </SheetContent>
            </Sheet>

            <main id="admin-main" tabIndex={-1} className="mx-auto max-w-6xl px-4 py-8 outline-none sm:px-6 lg:px-10 lg:py-10">
                {children}
            </main>
            <FlashToaster />
        </div>
    );
}
