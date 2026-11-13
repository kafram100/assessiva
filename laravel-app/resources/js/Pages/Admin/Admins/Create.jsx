import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import React, { useState } from 'react';

export default function Create() {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        permissions: [],
    });

    const availablePermissions = [
        { id: 'manage_students', label: 'Manage Students / Users' },
        { id: 'export_users', label: 'Export Users Data' },
        { id: 'manage_questions', label: 'Manage Question Bank' },
        { id: 'manage_billing', label: 'Manage Billing' },
        { id: 'manage_settings', label: 'Manage Settings' },
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
        post(route('admin.admins.store'));
    };

    return (
        <AdminLayout
            header={
                <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
                    Add Restricted Admin
                </h2>
            }
        >
            <Head title="Add Admin" />

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

                        <div>
                            <label className="block text-sm font-medium text-theme-strong">Password</label>
                            <div className="relative mt-1">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    className="block w-full rounded-md border-theme-line bg-theme-surface pr-10 text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute inset-y-0 right-0 flex items-center pr-3 text-theme-muted hover:text-theme-strong"
                                >
                                    {showPassword ? (
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="h-5 w-5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    ) : (
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="h-5 w-5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    )}
                                </button>
                            </div>
                            {errors.password && <p className="mt-1 text-sm text-rose-500">{errors.password}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-theme-strong">Confirm Password</label>
                            <div className="relative mt-1">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    className="block w-full rounded-md border-theme-line bg-theme-surface pr-10 text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                />
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
                                {processing ? 'Saving...' : 'Create Admin'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
