import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Availability from '@/Components/Availability';
import ShopLayout from '@/Layouts/ShopLayout';
import { rsd } from '@/lib/format';

function ProductCard({ product }) {
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);
    const out = product.availability === 'out';

    const add = () => {
        setAdding(true);
        router.post(route('cart.add', product.id), { quantity }, { preserveScroll: true, onFinish: () => setAdding(false) });
    };

    return (
        <div className="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div className="text-xs font-medium uppercase tracking-wide text-slate-400">{product.category}</div>
            <Link href={route('shop.show', product.id)} className="mt-1 font-semibold text-slate-900 hover:text-indigo-700">
                {product.name}
            </Link>
            <div className="mt-0.5 text-xs text-slate-400">{product.sku}</div>
            <div className="mt-auto flex items-end justify-between pt-4">
                <div>
                    <div className="text-lg font-bold text-slate-900">{rsd(product.price)}</div>
                    <Availability value={product.availability} stock={product.stock} />
                </div>
                <div className="flex items-center gap-1">
                    <input
                        type="number"
                        min="1"
                        value={quantity}
                        onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value || '1', 10)))}
                        className="w-16 rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        aria-label={`Quantity of ${product.name}`}
                        disabled={out}
                    />
                    <button
                        type="button"
                        onClick={add}
                        disabled={out || adding}
                        className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        Add
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Index({ products, categories, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const go = (params) =>
        router.get(route('shop.index'), { search, category: filters.category, ...params }, { preserveState: true, replace: true });

    return (
        <ShopLayout>
            <Head title="Catalog" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Catalog</h1>
                    <p className="mt-1 text-sm text-slate-500">Order by the pack. We confirm orders within one working day.</p>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        go({ page: undefined });
                    }}
                    className="flex w-full gap-2 sm:w-auto"
                >
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name or SKU"
                        className="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-72"
                    />
                    <button type="submit" className="rounded-md bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-700">
                        Search
                    </button>
                </form>
            </div>

            <div className="mt-6 flex flex-wrap gap-2">
                {[null, ...categories].map((category) => (
                    <button
                        key={category ?? 'all'}
                        type="button"
                        onClick={() => go({ category, page: undefined })}
                        className={`rounded-full px-3 py-1 text-sm ring-1 ring-inset ${
                            (filters.category ?? null) === category
                                ? 'bg-indigo-600 text-white ring-indigo-600'
                                : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'
                        }`}
                    >
                        {category ?? 'All products'}
                    </button>
                ))}
            </div>

            {products.data.length === 0 ? (
                <div className="mt-8 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    No products match your search.
                </div>
            ) : (
                <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {products.data.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            )}

            {products.last_page > 1 && (
                <div className="mt-8 flex flex-wrap gap-1">
                    {products.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || '#'}
                            preserveScroll
                            className={`rounded-md px-3 py-1.5 text-sm ${
                                link.active ? 'bg-indigo-600 text-white' : link.url ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' : 'cursor-default text-slate-300'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </ShopLayout>
    );
}
