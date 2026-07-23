import { Head, Link } from '@inertiajs/react';

const STATUS_LABELS = {
    draft: 'U pripremi',
    new: 'Nova',
    confirmed: 'Potvrđena',
    in_progress: 'U obradi',
    shipped: 'Poslata',
    cancelled: 'Otkazana',
};

export default function MyOrders({ orders }) {
    return (
        <>
            <Head title="Moje porudžbine" />

            <div className="max-w-4xl mx-auto p-6">
                <div className="flex items-center justify-between">
                    <Link href={route('shop.index')} className="text-sm text-blue-600 hover:underline">
                        ← Prodavnica
                    </Link>
                </div>

                <h1 className="mt-4 mb-6 text-2xl font-bold text-gray-900">Moje porudžbine</h1>

                {orders.data.length === 0 ? (
                    <div className="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                        Nemate porudžbina.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th className="p-3">Broj</th>
                                    <th className="p-3">Datum</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3 text-right">Ukupno</th>
                                    <th className="p-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.data.map((order) => (
                                    <tr key={order.id} className="border-t">
                                        <td className="p-3 font-medium">#{order.id}</td>
                                        <td className="p-3">
                                            {new Date(order.created_at).toLocaleDateString('sr-RS')}
                                        </td>
                                        <td className="p-3">
                                            {STATUS_LABELS[order.status] || order.status}
                                        </td>
                                        <td className="p-3 text-right font-medium">
                                            {Number(order.total_amount).toFixed(2)} RSD
                                        </td>
                                        <td className="p-3 text-right">
                                            <Link
                                                href={route('my-orders.show', order.id)}
                                                className="text-blue-600 hover:underline"
                                            >
                                                Detalji
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}