import { Head, Link } from '@inertiajs/react';
import ShopLayout from '@/Layouts/ShopLayout';

const STATUS_LABELS = {
    draft: 'U pripremi',
    new: 'Nova',
    confirmed: 'Potvrđena',
    in_progress: 'U obradi',
    shipped: 'Poslata',
    cancelled: 'Otkazana',
};

export default function MyOrderDetail({ order }) {
    return (
        <>
            <Head title={`Porudžbina #${order.id}`} />

            <div className="max-w-3xl mx-auto p-6">
                <Link href={route('my-orders.index')} className="text-sm text-blue-600 hover:underline">
                    ← Moje porudžbine
                </Link>

                <h1 className="mt-4 text-2xl font-bold text-gray-900">
                    Porudžbina #{order.id}
                </h1>

                <div className="mt-4 rounded-lg bg-white p-6 shadow">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="text-gray-500">Status:</span>{' '}
                            <span className="font-medium">
                                {STATUS_LABELS[order.status] || order.status}
                            </span>
                        </div>
                        <div>
                            <span className="text-gray-500">Datum:</span>{' '}
                            <span className="font-medium">
                                {new Date(order.created_at).toLocaleString('sr-RS')}
                            </span>
                        </div>
                    </div>

                    {order.note && (
                        <div className="mt-4 text-sm">
                            <span className="text-gray-500">Napomena:</span> {order.note}
                        </div>
                    )}
                </div>

                <div className="mt-6 overflow-hidden rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th className="p-3">Proizvod</th>
                                <th className="p-3">Cena</th>
                                <th className="p-3">Količina</th>
                                <th className="p-3 text-right">Ukupno</th>
                            </tr>
                        </thead>
                        <tbody>
                            {order.items.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3">{item.product_name}</td>
                                    <td className="p-3">{Number(item.unit_price).toFixed(2)}</td>
                                    <td className="p-3">{item.quantity}</td>
                                    <td className="p-3 text-right font-medium">
                                        {Number(item.subtotal).toFixed(2)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 text-right text-xl font-bold text-gray-900">
                    Ukupno: {Number(order.total_amount).toFixed(2)} RSD
                </div>
            </div>
        </>
    );
}