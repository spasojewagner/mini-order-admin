import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ product }) {
    const { data, setData, post, processing } = useForm({
        quantity: 1,
    });

    function addToCart(e) {
        e.preventDefault();
        post(route('cart.add', product.id));
    }

    return (
        <>
            <Head title={product.name} />

            <div className="max-w-3xl mx-auto p-6">
                <div className="flex items-center justify-between">
                    <Link href={route('shop.index')} className="text-sm text-blue-600 hover:underline">
                        ← Nazad na prodavnicu
                    </Link>
                    <Link href={route('cart.index')} className="text-sm text-blue-600 hover:underline">
                        Korpa
                    </Link>
                </div>

                <div className="mt-4 rounded-lg bg-white p-6 shadow">
                    <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
                    <div className="mt-1 text-sm text-gray-500">SKU: {product.sku || '—'}</div>

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

                    {product.stock_quantity > 0 && (
                        <form onSubmit={addToCart} className="mt-6 flex items-end gap-3">
                            <div>
                                <label className="block text-sm text-gray-700">Količina</label>
                                <input
                                    type="number"
                                    min="1"
                                    max={product.stock_quantity}
                                    value={data.quantity}
                                    onChange={(e) => setData('quantity', parseInt(e.target.value) || 1)}
                                    className="mt-1 w-24 rounded-md border-gray-300 shadow-sm"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:bg-gray-300"
                            >
                                Dodaj u korpu
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </>
    );
}