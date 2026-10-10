import { rsd } from '@/lib/format';

export default function Totals({ totals }) {
    return (
        <dl className="space-y-1 text-sm">
            <div className="flex justify-between text-slate-600"><dt>Net</dt><dd>{rsd(totals.net)}</dd></div>
            <div className="flex justify-between text-slate-600"><dt>VAT {totals.vat_rate}%</dt><dd>{rsd(totals.vat)}</dd></div>
            <div className="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold text-slate-900"><dt>Total</dt><dd>{rsd(totals.gross)}</dd></div>
        </dl>
    );
}
