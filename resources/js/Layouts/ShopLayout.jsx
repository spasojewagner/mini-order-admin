import { Link, usePage } from '@inertiajs/react';

export default function ShopLayout({ children }) {
    const { auth, cartCount, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white shadow">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <Link href={route('shop.index')} className="text-lg font-bold text-gray-900">
                        Prodavnica
                    </Link>

                    <div className="flex items-center gap-5 text-sm">
                        <Link href={route('cart.index')} className="text-gray-700 hover:text-blue-600">
                            Korpa
                            {cartCount > 0 && (
                                <span className="ml-1 rounded-full bg-blue-600 px-2 py-0.5 text-xs text-white">
                                    {cartCount}
                                </span>
                            )}
                        </Link>

                        {auth?.user ? (
                            <>
                                <Link href={route('my-orders.index')} className="text-gray-700 hover:text-blue-600">
                                    Moje porudžbine
                                </Link>
                                <Link href={route('account')} className="text-gray-700 hover:text-blue-600">
                                    {auth.user.name}
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link href={route('login')} className="text-gray-700 hover:text-blue-600">
                                    Prijava
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700"
                                >
                                    Registracija
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </nav>

            {/* Flash poruke */}
            {flash?.success && (
                <div className="mx-auto mt-4 max-w-6xl px-6">
                    <div className="rounded-md bg-green-50 p-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                </div>
            )}
            {flash?.error && (
                <div className="mx-auto mt-4 max-w-6xl px-6">
                    <div className="rounded-md bg-red-50 p-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                </div>
            )}

            <main>{children}</main>
        </div>
    );
}