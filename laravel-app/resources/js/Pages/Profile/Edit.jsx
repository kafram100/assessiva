import AdminLayout from '@/Layouts/AdminLayout';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import HodLayout from '@/Layouts/HodLayout';
import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

const roleLayouts = {
    admin: AdminLayout,
    hod: HodLayout,
    lecturer: LecturerLayout,
};

export default function Edit({ mustVerifyEmail, status }) {
    const user = usePage().props.auth.user;
    const Layout = roleLayouts[user.role] ?? AuthenticatedLayout;

    const headerTitleClass =
        'text-xl font-semibold leading-tight text-theme-strong';

    return (
        <Layout
            header={<h2 className={headerTitleClass}>Profile</h2>}
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="border border-theme-line bg-theme-glass p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="border border-theme-line bg-theme-glass p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="border border-theme-line bg-theme-glass p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </Layout>
    );
}
