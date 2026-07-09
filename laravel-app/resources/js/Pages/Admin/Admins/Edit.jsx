import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import React from 'react';

export default function Edit({ adminUser }) {
    const { data, setData, put, processing, errors } = useForm({
        name: adminUser.name || '',
        username: adminUser.username || '',
        email: adminUser.email || '',
        password: '',
        password_confirmation: '',
        permissions: adminUser.permissions || [],
    });

    const availablePermissions = [
        { id: 'manage_students', label: 'Manage Students' },
        { id: 'export_users', label: 'Export Users Data' },
        { id: 'manage_questions', label: 'Manage Question Bank' },
        { id: 'manage_billing', label: 'Manage Billing' },
        { id: 'manage_settings', label: 'Manage Settings' },
        { id: 'manage_admins', label: 'Manage Admins' },
    ];

    const handlePermissionChange = (e) => {
        const value = e.target.value;
        const checked = e.target.checked;
        if (checked) {
            setData('permissions', [...data.permissions, value]);
        } else {
            setData('permissions', data.permissions.filter((p) => p !== value));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.admins.update', adminUser.id));
    };

    return (
        <AdminLayout
            header={
                <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
                    Edit Admin: {adminUser.name}
                </h2>
            }
        >
            <Head title="Edit Admin" />

            <div className="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.admins.index')}
                    className="mb-4 inline-flex items-center gap-1.5 text-sm text-theme-dim hover:text-theme-strong transition-colors"
                >
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to list
                </Link>
                <div className="rounded-lg border border-theme-line bg-theme-elevated p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-theme-strong">Name</label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-surface text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            {errors.name && <p className="mt-1 text-sm text-rose-500">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-theme-strong">Username</label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-surface text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                value={data.username}
                                onChange={(e) => setData('username', e.target.value)}
                                required
                            />
                            {errors.username && <p className="mt-1 text-sm text-rose-500">{errors.username}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-theme-strong">Email</label>
                            <input
                                type="email"
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-surface text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            {errors.email && <p className="mt-1 text-sm text-rose-500">{errors.email}</p>}
                        </div>

                        <div className="rounded-md bg-theme-surface-soft p-4 border border-theme-line">
                            <h4 className="text-sm font-medium text-theme-strong mb-2">Change Password</h4>
                            <p className="text-xs text-theme-dim mb-4">Leave blank if you do not want to change the password.</p>
                            
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-theme-strong">New Password</label>
                                    <input
                                        type="password"
                                        className="mt-1 block w-full rounded-md border-theme-line bg-theme-surface text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    {errors.password && <p className="mt-1 text-sm text-rose-500">{errors.password}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-theme-strong">Confirm Password</label>
                                    <input
                                        type="password"
                                        className="mt-1 block w-full rounded-md border-theme-line bg-theme-surface text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-theme-strong mb-2">Permissions</label>
                            <div className="space-y-2">
                                {availablePermissions.map((permission) => (
                                    <label key={permission.id} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            value={permission.id}
                                            checked={data.permissions.includes(permission.id)}
                                            onChange={handlePermissionChange}
                                            className="h-4 w-4 rounded border-theme-line text-sky-600 focus:ring-sky-500"
                                        />
                                        <span className="ml-2 text-sm text-theme-muted">{permission.label}</span>
                                    </label>
                                ))}
                            </div>
                            {errors.permissions && <p className="mt-1 text-sm text-rose-500">{errors.permissions}</p>}
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-50"
                            >
                                {processing ? 'Saving...' : 'Update Admin'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
