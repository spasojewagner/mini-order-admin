import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Availability from '@/Components/Availability';
import ShopLayout from '@/Layouts/ShopLayout';
import { rsd } from '@/lib/format';

export default function Show({ product }) {
    const [quantity, setQuantity] = useState(1);
    const out = product.availability === 'out';

    return (
        <ShopLayout>
            <Head title={product.name} />

            <Link href={route('shop.index')} className="text-sm text-indigo-600 hover:underline">← Back to catalog</Link>

            <div className="mt-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div className="text-xs font-medium uppercase tracking-wide text-slate-400">{product.category}</div>
                <h1 className="mt-1 text-2xl font-bold text-slate-900">{product.name}</h1>
                <div className="mt-1 text-sm text-slate-500">SKU {product.sku}</div>

                <div className="mt-6 flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <div className="text-3xl font-bold text-slate-900">{rsd(product.price)}</div>
                        <div className="text-sm text-slate-500">per pack, excl. VAT</div>
                        <div className="mt-2"><Availability value={product.availability} stock={product.stock} /></div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="number"
                            min="1"
                            value={quantity}
                            onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value || '1', 10)))}
                            className="w-24 rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            disabled={out}
                        />
                        <button
                            type="button"
                            disabled={out}
                            onClick={() => router.post(route('cart.add', product.id), { quantity })}
                            className="rounded-md bg-indigo-600 px-5 py-2.5 font-medium text-white hover:bg-indigo-700 disabled:bg-slate-300"
                        >
                            Add to cart
                        </button>
                    </div>
                </div>
            </div>
        </ShopLayout>
    );
}
