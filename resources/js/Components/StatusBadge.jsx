const COLORS = {
    gray: 'bg-slate-100 text-slate-700 ring-slate-200',
    info: 'bg-sky-50 text-sky-700 ring-sky-200',
    primary: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    warning: 'bg-amber-50 text-amber-800 ring-amber-200',
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    danger: 'bg-rose-50 text-rose-700 ring-rose-200',
};

export default function StatusBadge({ label, color = 'gray', className = '' }) {
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${COLORS[color] || COLORS.gray} ${className}`}>
            {label}
        </span>
    );
}
