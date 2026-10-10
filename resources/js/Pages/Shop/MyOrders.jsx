import { Head, Link } from '@inertiajs/react';
import StatusBadge from '@/Components/StatusBadge';
import ShopLayout from '@/Layouts/ShopLayout';
import { date, rsd } from '@/lib/format';

export default function MyOrders({ orders }) {
    return (
        <ShopLayout>
            <Head title="My orders" />
            <h1 className="text-2xl font-bold text-slate-900">My orders</h1>

            {orders.data.length === 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    No orders yet. <Link href={route('shop.index')} className="text-indigo-600 hover:underline">Start with the catalog</Link>
                </div>
            ) : (
                <div className="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Order</th>
                                <th className="px-4 py-3">Placed</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-right">Lines</th>
                                <th className="px-4 py-3 text-right">Total (net)</th>
                                <th className="px-4 py-3">Invoice</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {orders.data.map((order) => (
                                <tr key={order.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3 font-medium">
                                        <Link href={route('my-orders.show', order.id)} className="text-indigo-700 hover:underline">{order.number}</Link>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{date(order.created_at)}</td>
                                    <td className="px-4 py-3"><StatusBadge label={order.status_label} color={order.status_color} /></td>
                                    <td className="px-4 py-3 text-right text-slate-600">{order.items_count}</td>
                                    <td className="px-4 py-3 text-right font-medium">{rsd(order.total_amount)}</td>
                                    <td className="px-4 py-3">
                                        {order.invoice_url ? (
                                            <a href={order.invoice_url} target="_blank" rel="noreferrer" className="text-indigo-700 hover:underline">{order.invoice_number}</a>
                                        ) : (
                                            <span className="text-slate-300">—</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {orders.last_page > 1 && (
                <div className="mt-6 flex flex-wrap gap-1">
                    {orders.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || '#'}
                            className={`rounded-md px-3 py-1.5 text-sm ${link.active ? 'bg-indigo-600 text-white' : link.url ? 'bg-white ring-1 ring-slate-200' : 'text-slate-300'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </ShopLayout>
    );
}
