import { Head, Link, router } from '@inertiajs/react';
import StatusBadge from '@/Components/StatusBadge';
import ShopLayout from '@/Layouts/ShopLayout';
import Totals from '@/Pages/Shop/Totals';
import { date, dateTime, rsd } from '@/lib/format';

function Steps({ steps }) {
    return (
        <ol className="grid grid-cols-5 gap-2">
            {steps.map((step) => (
                <li key={step.label} className="text-center">
                    <div className={`h-1.5 rounded-full ${step.done ? 'bg-indigo-600' : 'bg-slate-200'}`} />
                    <div className={`mt-2 text-xs font-medium ${step.done ? 'text-slate-900' : 'text-slate-400'}`}>{step.label}</div>
                    <div className="text-[11px] text-slate-400">{step.at ? dateTime(step.at) : ''}</div>
                </li>
            ))}
        </ol>
    );
}

export default function MyOrderDetail({ order }) {
    const cancel = () => {
        if (confirm(`Cancel order ${order.number}?`)) {
            router.post(route('my-orders.cancel', order.id), {}, { preserveScroll: true });
        }
    };

    return (
        <ShopLayout>
            <Head title={order.number} />
            <Link href={route('my-orders.index')} className="text-sm text-indigo-600 hover:underline">← My orders</Link>

            <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-bold text-slate-900">{order.number}</h1>
                    <StatusBadge label={order.status_label} color={order.status_color} />
                </div>
                <div className="flex gap-2">
                    {order.invoice_url && (
                        <a href={order.invoice_url} target="_blank" rel="noreferrer" className="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                            Invoice {order.invoice_number}
                        </a>
                    )}
                    {order.can_cancel && (
                        <button type="button" onClick={cancel} className="rounded-md px-3 py-2 text-sm font-medium text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50">
                            Cancel order
                        </button>
                    )}
                </div>
            </div>
            <p className="mt-1 text-sm text-slate-500">Placed {date(order.created_at)}</p>

            <div className="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                {order.status === 'cancelled' ? (
                    <p className="text-sm text-rose-700">This order was cancelled.</p>
                ) : (
                    <Steps steps={order.steps} />
                )}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Product</th>
                                <th className="px-4 py-3 text-right">Price</th>
                                <th className="px-4 py-3 text-right">Qty</th>
                                <th className="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {order.items.map((item) => (
                                <tr key={item.id}>
                                    <td className="px-4 py-3">{item.product_name}</td>
                                    <td className="px-4 py-3 text-right text-slate-600">{rsd(item.unit_price)}</td>
                                    <td className="px-4 py-3 text-right">{item.quantity}</td>
                                    <td className="px-4 py-3 text-right font-medium">{rsd(item.subtotal)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <aside className="space-y-6">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <Totals totals={order.totals} />
                        {order.note && <p className="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-600"><span className="font-medium">Note:</span> {order.note}</p>}
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="mb-3 text-sm font-semibold text-slate-900">History</h2>
                        <ul className="space-y-2 text-sm">
                            {order.history.map((h) => (
                                <li key={h.id} className="flex justify-between gap-3">
                                    <span className="text-slate-700">{h.text}</span>
                                    <span className="shrink-0 text-xs text-slate-400">{dateTime(h.at)}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </aside>
            </div>
        </ShopLayout>
    );
}
