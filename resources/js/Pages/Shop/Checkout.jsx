import { Head, Link, useForm } from '@inertiajs/react';
import ShopLayout from '@/Layouts/ShopLayout';
import Totals from '@/Pages/Shop/Totals';
import { rsd } from '@/lib/format';

export default function Checkout({ items, totals }) {
    const { data, setData, post, processing } = useForm({ note: '' });

    return (
        <ShopLayout>
            <Head title="Checkout" />
            <Link href={route('cart.index')} className="text-sm text-indigo-600 hover:underline">← Back to cart</Link>
            <h1 className="mt-3 text-2xl font-bold text-slate-900">Place your order</h1>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('checkout.store'));
                }}
                className="mt-6 grid gap-6 lg:grid-cols-3"
            >
                <div className="space-y-6 lg:col-span-2">
                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <ul className="divide-y divide-slate-100 text-sm">
                            {items.map((item) => (
                                <li key={item.id} className="flex justify-between gap-4 px-4 py-3">
                                    <span><span className="font-medium text-slate-900">{item.quantity} ×</span> {item.name}</span>
                                    <span className="font-medium">{rsd(item.subtotal)}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <label htmlFor="note" className="text-sm font-medium text-slate-700">Note for our team (optional)</label>
                        <textarea
                            id="note"
                            rows={3}
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            placeholder="Delivery window, entrance, purchase order number…"
                            className="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <aside className="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <Totals totals={totals} />
                    <button type="submit" disabled={processing} className="mt-4 w-full rounded-md bg-indigo-600 px-4 py-2.5 font-medium text-white hover:bg-indigo-700 disabled:bg-slate-300">
                        Place order
                    </button>
                    <p className="mt-3 text-xs text-slate-500">We reserve the goods when we confirm the order. You will get an email at every step.</p>
                </aside>
            </form>
        </ShopLayout>
    );
}
