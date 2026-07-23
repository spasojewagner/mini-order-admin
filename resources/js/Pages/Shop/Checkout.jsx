import { Head, Link, useForm } from '@inertiajs/react';

export default function Checkout({ items, total }) {
    const { data, setData, post, processing, errors } = useForm({
        note: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('checkout.store'));
    }

    return (
        <>
            <Head title="Poručivanje" />

            <div className="max-w-3xl mx-auto p-6">
                <Link href={route('cart.index')} className="text-sm text-blue-600 hover:underline">
                    ← Nazad na korpu
                </Link>

                <h1 className="mt-4 mb-6 text-2xl font-bold text-gray-900">Poručivanje</h1>

                {items.length === 0 ? (
                    <div className="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                        Korpa je prazna.{' '}
                        <Link href={route('shop.index')} className="text-blue-600 hover:underline">
                            Idi u prodavnicu
                        </Link>
                    </div>
                ) : (
                    <>
                        <div className="rounded-lg bg-white p-6 shadow">
                            <h2 className="mb-4 font-semibold text-gray-900">Pregled porudžbine</h2>

                            <table className="w-full text-sm">
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2">
                                                {item.name} × {item.quantity}
                                            </td>
                                            <td className="py-2 text-right font-medium">
                                                {item.subtotal.toFixed(2)} RSD
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="mt-4 text-right text-xl font-bold text-gray-900">
                                Ukupno: {total.toFixed(2)} RSD
                            </div>
                        </div>

                        <form onSubmit={submit} className="mt-6 rounded-lg bg-white p-6 shadow">
                            <label className="block text-sm font-medium text-gray-700">
                                Napomena (opciono)
                            </label>
                            <textarea
                                value={data.note}
                                onChange={(e) => setData('note', e.target.value)}
                                rows="3"
                                className="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                                placeholder="Npr. dostava posle 17h..."
                            />
                            {errors.note && (
                                <div className="mt-1 text-sm text-red-600">{errors.note}</div>
                            )}

                            <button
                                type="submit"
                                disabled={processing}
                                className="mt-4 w-full rounded-md bg-blue-600 px-6 py-3 text-white hover:bg-blue-700 disabled:bg-gray-300"
                            >
                                {processing ? 'Šaljem...' : 'Poruči'}
                            </button>
                        </form>
                    </>
                )}
            </div>
        </>
    );
}