import { Head } from '@inertiajs/react';
import ShopLayout from '@/Layouts/ShopLayout';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <ShopLayout>
            <Head title="Account" />
            <h1 className="text-2xl font-bold text-slate-900">Account</h1>

            <div className="mt-6 space-y-6">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} className="max-w-xl" />
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </ShopLayout>
    );
}
