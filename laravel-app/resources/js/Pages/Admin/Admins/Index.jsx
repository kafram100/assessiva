import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import React from 'react';

export default function Index({ admins }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this admin?')) {
            destroy(route('admin.admins.destroy', id));
        }
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Administrators
                    </h2>
                    <Link
                        href={route('admin.admins.create')}
                        className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                    >
                        Add Admin
                    </Link>
                </div>
            }
        >
            <Head title="Administrators" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="overflow-x-auto rounded-lg border border-theme-line bg-theme-elevated">
                    <table className="min-w-full divide-y divide-theme-line">
                        <thead className="bg-theme-surface">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-theme-dim">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-theme-dim">Email</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-theme-dim">Role</th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-theme-dim">Permissions</th>
                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-theme-dim">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-theme-line bg-theme-elevated">
                            {admins.map((admin) => (
                                <tr key={admin.id}>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-theme-strong">{admin.name}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-theme-muted">{admin.email}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-theme-muted">
                                        {admin.id === 1 ? (
                                            <span className="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                                Super Admin
                                            </span>
                                        ) : (
                                            <span className="inline-flex rounded-full bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-900 dark:text-sky-300">
                                                Restricted Admin
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-theme-muted">
                                        {admin.id === 1 ? 'All Permissions' : (admin.permissions?.length > 0 ? admin.permissions.join(', ') : 'None')}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        {admin.id !== 1 && (
                                            <div className="flex justify-end gap-3">
                                                <Link
                                                    href={route('admin.admins.edit', admin.id)}
                                                    className="text-sky-600 hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-300"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(admin.id)}
                                                    className="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
