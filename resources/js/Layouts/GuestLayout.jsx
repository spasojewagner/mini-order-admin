import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { company } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <Link href="/" className="flex items-center gap-2">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white">
                    {(company?.name || 'S').charAt(0)}
                </span>
                <span className="leading-tight">
                    <span className="block font-semibold text-slate-900">{company?.name}</span>
                    <span className="block text-xs text-slate-500">Wholesale ordering portal</span>
                </span>
            </Link>

            <div className="mt-6 w-full max-w-md rounded-xl border border-slate-200 bg-white px-6 py-6 shadow-sm">{children}</div>

            <p className="mt-6 text-xs text-slate-400">
                Staff? Sign in to the <a href="/admin" className="text-indigo-600 hover:underline">back office</a>.
            </p>
        </div>
    );
}
