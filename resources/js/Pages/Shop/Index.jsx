import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ShopLayout from '@/Layouts/ShopLayout';

export default function Index({ products, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    // Pretraga — šalje GET na istu rutu sa search parametrom
    function handleSearch(e) {
        e.preventDefault();
        router.get(route('shop.index'), { search }, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <ShopLayout>
            <Head title="Prodavnica" />

            <div className="max-w-6xl mx-auto p-6">
             <h1 className="mb-6 text-2xl font-bold text-gray-900">Proizvodi</h1>

                <form onSubmit={handleSearch} className="mb-6 flex gap-2">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Pretraži po nazivu ili SKU..."
                        className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <button
                        type="submit"
                        className="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    >
                        Pretraži
                    </button>
                </form>

                {products.data.length === 0 ? (
                    <div className="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                        Nema proizvoda za zadatu pretragu.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {products.data.map((product) => (
                            <Link
                                key={product.id}
                                href={route('shop.show', product.id)}
                                className="rounded-lg bg-white p-4 shadow transition hover:shadow-md"
                            >
                                <div className="font-semibold text-gray-900">{product.name}</div>
                                <div className="mt-1 text-sm text-gray-500">
                                    {product.sku || 'bez SKU'}
                                </div>
                                <div className="mt-3 text-lg font-bold text-gray-900">
                                    {Number(product.price).toFixed(2)} RSD
                                </div>
                                <div className="mt-1 text-sm">
                                    {product.stock_quantity > 0 ? (
                                        <span className="text-green-600">
                                            Na stanju: {product.stock_quantity}
                                        </span>
                                    ) : (
                                        <span className="text-red-600">Nema na stanju</span>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                {/* Paginacija */}
                {products.links.length > 3 && (
                    <div className="mt-6 flex flex-wrap gap-1">
                        {products.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`rounded px-3 py-1 text-sm ${
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : link.url
                                        ? 'bg-white text-blue-600 hover:bg-gray-50'
                                        : 'bg-white text-gray-400 cursor-default'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        
       </ShopLayout>
    );
     
}