import { Head, Link, router } from '@inertiajs/react';
import ShopLayout from '@/Layouts/ShopLayout';
import Totals from '@/Pages/Shop/Totals';
import { rsd } from '@/lib/format';

export default function Cart({ items, totals }) {
    const update = (id, quantity) => quantity >= 1 && router.patch(route('cart.update', id), { quantity }, { preserveScroll: true });
    const remove = (id) => router.delete(route('cart.remove', id), { preserveScroll: true });
    const short = items.filter((i) => i.quantity > i.stock || !i.available);

    return (
        <ShopLayout>
            <Head title="Cart" />
            <h1 className="text-2xl font-bold text-slate-900">Cart</h1>

            {items.length === 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    Your cart is empty. <Link href={route('shop.index')} className="text-indigo-600 hover:underline">Browse the catalog</Link>
                </div>
            ) : (
                <div className="mt-6 grid gap-6 lg:grid-cols-3">
                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-4 py-3">Product</th>
                                    <th className="px-4 py-3 text-right">Price</th>
                                    <th className="px-4 py-3">Qty</th>
                                    <th className="px-4 py-3 text-right">Amount</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {items.map((item) => (
                                    <tr key={item.id}>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{item.name}</div>
                                            <div className="text-xs text-slate-400">{item.sku}</div>
                                            {item.quantity > item.stock && (
                                                <div className="mt-1 text-xs text-rose-600">Only {item.stock} in stock</div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right text-slate-600">{rsd(item.price)}</td>
                                        <td className="px-4 py-3">
                                            <input
                                                type="number"
                                                min="1"
                                                defaultValue={item.quantity}
                                                onBlur={(e) => Number(e.target.value) !== item.quantity && update(item.id, Number(e.target.value))}
                                                className="w-20 rounded-md border-slate-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium">{rsd(item.subtotal)}</td>
                                        <td className="px-4 py-3 text-right">
                                            <button type="button" onClick={() => remove(item.id)} className="text-xs text-slate-400 hover:text-rose-600">Remove</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <aside className="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="mb-3 font-semibold text-slate-900">Summary</h2>
                        <Totals totals={totals} />
                        {short.length > 0 && (
                            <p className="mt-3 rounded-md bg-rose-50 p-2 text-xs text-rose-700">Lower the quantities marked in red to place the order.</p>
                        )}
                        <Link
                            href={route('checkout.index')}
                            className={`mt-4 block rounded-md px-4 py-2.5 text-center font-medium text-white ${short.length ? 'pointer-events-none bg-slate-300' : 'bg-indigo-600 hover:bg-indigo-700'}`}
                        >
                            Continue to checkout
                        </Link>
                        <Link href={route('shop.index')} className="mt-3 block text-center text-sm text-slate-500 hover:text-slate-700">Keep shopping</Link>
                    </aside>
                </div>
            )}
        </ShopLayout>
    );
}
