import { Link, usePage } from '@inertiajs/react';

function NavLink({ href, active, children }) {
    return (
        <Link
            href={href}
            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }`}
        >
            {children}
        </Link>
    );
}

export default function ShopLayout({ children }) {
    const { auth, cartCount, flash, company } = usePage().props;
    const user = auth?.user;

    return (
        <div className="min-h-screen">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <Link href={route('shop.index')} className="flex items-center gap-2">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                            {(company?.name || 'S').charAt(0)}
                        </span>
                        <span className="leading-tight">
                            <span className="block text-sm font-semibold text-slate-900">{company?.name}</span>
                            <span className="block text-xs text-slate-500">Wholesale ordering portal</span>
                        </span>
                    </Link>

                    <nav className="flex items-center gap-1">
                        <NavLink href={route('shop.index')} active={route().current('shop.*')}>Catalog</NavLink>
                        {user && <NavLink href={route('my-orders.index')} active={route().current('my-orders.*')}>My orders</NavLink>}
                        <Link
                            href={route('cart.index')}
                            className={`ml-1 inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium ${
                                route().current('cart.*') || route().current('checkout.*')
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-slate-900 text-white hover:bg-slate-700'
                            }`}
                        >
                            Cart
                            <span className="rounded-full bg-white/20 px-2 py-0.5 text-xs">{cartCount}</span>
                        </Link>
                        {user ? (
                            <div className="ml-2 flex items-center gap-2 border-l border-slate-200 pl-3 text-sm">
                                <Link href={route('profile.edit')} className="text-slate-600 hover:text-slate-900">{user.name}</Link>
                                <Link href={route('logout')} method="post" as="button" className="text-slate-400 hover:text-slate-700">
                                    Log out
                                </Link>
                            </div>
                        ) : (
                            <div className="ml-2 flex items-center gap-2 border-l border-slate-200 pl-3 text-sm">
                                <Link href={route('login')} className="text-slate-600 hover:text-slate-900">Log in</Link>
                                <Link href={route('register')} className="font-medium text-indigo-600 hover:text-indigo-800">Open an account</Link>
                            </div>
                        )}
                    </nav>
                </div>
            </header>

            {(flash?.success || flash?.error) && (
                <div className="mx-auto mt-4 max-w-6xl px-4 sm:px-6">
                    {flash.success && <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{flash.success}</div>}
                    {flash.error && <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{flash.error}</div>}
                </div>
            )}

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">{children}</main>

            <footer className="mx-auto max-w-6xl px-4 pb-10 text-xs text-slate-400 sm:px-6">
                Prices are per pack and exclude VAT ({company?.vat_rate}%). Orders are confirmed by our sales team on working days.
            </footer>
        </div>
    );
}
