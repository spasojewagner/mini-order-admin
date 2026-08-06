import { Head, Link, router } from '@inertiajs/react';
import ShopLayout from '@/Layouts/ShopLayout';

export default function Cart({ items, total }) {
    function updateQuantity(productId, quantity) {
        if (quantity < 1) return;
        router.patch(route('cart.update', productId), { quantity }, {
            preserveScroll: true,
        });
    }

    function removeItem(productId) {
        router.delete(route('cart.remove', productId), {
            preserveScroll: true,
        });
    }

    return (
        <ShopLayout>
            <Head title="Korpa" />

            <div className="max-w-4xl mx-auto p-6">
                <Link href={route('shop.index')} className="text-sm text-blue-600 hover:underline">
                    ← Nastavi kupovinu
                </Link>

                <h1 className="mt-4 mb-6 text-2xl font-bold text-gray-900">Korpa</h1>

                {items.length === 0 ? (
                    <div className="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                        Korpa je prazna.{' '}
                        <Link href={route('shop.index')} className="text-blue-600 hover:underline">
                            Idi u prodavnicu
                        </Link>
                    </div>
                ) : (
                    <>
                        <div className="overflow-hidden rounded-lg bg-white shadow">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        <th className="p-3">Proizvod</th>
                                        <th className="p-3 w-28">Cena</th>
                                        <th className="p-3 w-32">Količina</th>
                                        <th className="p-3 w-28">Ukupno</th>
                                        <th className="p-3 w-16"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-t">
                                            <td className="p-3">
                                                <div className="font-medium text-gray-900">{item.name}</div>
                                                <div className="text-xs text-gray-500">{item.sku || '—'}</div>
                                                {item.quantity > item.stock && (
                                                    <div className="mt-1 text-xs text-red-600">
                                                        Na stanju samo {item.stock} kom
                                                    </div>
                                                )}
                                            </td>
                                            <td className="p-3">{item.price.toFixed(2)}</td>
                                            <td className="p-3">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    value={item.quantity}
                                                    onChange={(e) =>
                                                        updateQuantity(item.id, parseInt(e.target.value) || 1)
                                                    }
                                                    className="w-20 rounded border-gray-300 text-sm"
                                                />
                                            </td>
                                            <td className="p-3 font-medium">{item.subtotal.toFixed(2)}</td>
                                            <td className="p-3">
                                                <button
                                                    onClick={() => removeItem(item.id)}
                                                    className="text-red-600 hover:underline"
                                                >
                                                    Ukloni
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-xl font-bold text-gray-900">
                                Ukupno: {total.toFixed(2)} RSD
                            </div>
                            <Link
                                href={route('checkout.index')}
                                className="rounded-md bg-blue-600 px-6 py-2 text-white hover:bg-blue-700"
                            >
                                Nastavi na poručivanje
                            </Link>
                        </div>
                    </>
                )}
            </div>
        </ShopLayout>
    );
}