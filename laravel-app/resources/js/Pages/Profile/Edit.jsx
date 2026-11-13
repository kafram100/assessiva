import AdminLayout from '@/Layouts/AdminLayout';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

const roleLayouts = {
    super_admin: AdminLayout,
};

export default function Edit({ mustVerifyEmail, status }) {
    const user = usePage().props.auth.user;
    const Layout = roleLayouts[user.role] ?? AuthenticatedLayout;

    const [activeTab, setActiveTab] = useState('personal'); // 'personal', 'security', 'danger'

    const headerTitleClass = 'text-xl font-semibold leading-tight text-theme-strong';

    const renderContent = () => {
        if (activeTab === 'personal') {
            return (
                <div className="border border-theme-line bg-theme-glass p-4 shadow sm:rounded-lg sm:p-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </div>
            );
        }
        if (activeTab === 'security') {
            return (
                <div className="border border-theme-line bg-theme-glass p-4 shadow sm:rounded-lg sm:p-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>
            );
        }
        if (activeTab === 'danger') {
            return (
                <div className="border border-theme-line bg-theme-glass p-4 shadow sm:rounded-lg sm:p-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            );
        }
        return null;
    };

    return (
        <Layout header={<h2 className={headerTitleClass}>Account Settings</h2>}>
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-8 sm:px-6 lg:px-8">
                    
                    {/* Tabs Navigation */}
                    <div className="flex space-x-1 rounded-xl bg-theme-highlight p-1 shadow-sm border border-theme-line">
                        <button
                            onClick={() => setActiveTab('personal')}
                            className={`flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all duration-200 ${
                                activeTab === 'personal'
                                    ? 'bg-sky-600 text-white shadow-lg shadow-sky-900/20'
                                    : 'text-theme-muted hover:bg-white/[0.04] hover:text-theme-strong'
                            }`}
                        >
                            Personal Info
                        </button>
                        <button
                            onClick={() => setActiveTab('security')}
                            className={`flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all duration-200 ${
                                activeTab === 'security'
                                    ? 'bg-sky-600 text-white shadow-lg shadow-sky-900/20'
                                    : 'text-theme-muted hover:bg-white/[0.04] hover:text-theme-strong'
                            }`}
                        >
                            Security
                        </button>
                        <button
                            onClick={() => setActiveTab('danger')}
                            className={`flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all duration-200 ${
                                activeTab === 'danger'
                                    ? 'bg-red-600 text-white shadow-lg shadow-red-900/20'
                                    : 'text-theme-muted hover:bg-red-500/10 hover:text-red-500'
                            }`}
                        >
                            Danger Zone
                        </button>
                    </div>

                    {/* Tab Content */}
                    <div className="mt-8">
                        {renderContent()}
                    </div>

                </div>
            </div>
        </Layout>
    );
}
