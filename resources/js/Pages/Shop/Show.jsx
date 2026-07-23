import { Head, Link } from '@inertiajs/react';

export default function Show({ product }) {
    return (
        <>
            <Head title={product.name} />

            <div className="max-w-3xl mx-auto p-6">
                <Link
                    href={route('shop.index')}
                    className="text-sm text-blue-600 hover:underline"
                >
                    ← Nazad na prodavnicu
                </Link>

                <div className="mt-4 rounded-lg bg-white p-6 shadow">
                    <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
                    <div className="mt-1 text-sm text-gray-500">
                        SKU: {product.sku || '—'}
                    </div>

                    <div className="mt-6 text-3xl font-bold text-gray-900">
                        {Number(product.price).toFixed(2)} RSD
                    </div>

                    <div className="mt-2">
                        {product.stock_quantity > 0 ? (
                            <span className="text-green-600">
                                Na stanju: {product.stock_quantity} kom
                            </span>
                        ) : (
                            <span className="text-red-600">Trenutno nema na stanju</span>
                        )}
                    </div>

                    <div className="mt-6">
                        <button
                            disabled={product.stock_quantity === 0}
                            className="rounded-md bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            Dodaj u korpu
                        </button>
                        <p className="mt-2 text-sm text-gray-500">
                            (korpa dolazi u sledećem koraku)
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}