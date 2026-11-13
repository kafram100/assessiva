import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

function PermissionBadge({ perm }) {
    const labels = {
        manage_students:  'Students',
        manage_questions: 'Questions',
        manage_billing:   'Billing',
        manage_settings:  'Settings',
        manage_admins:    'Admins',
        export_users:     'Export Users',
        '*':              'All',
    };
    return (
        <span className="inline-flex items-center rounded-full border border-ai-accent/20 bg-ai-accent/10 px-2 py-0.5 text-[11px] font-medium text-ai-accent">
            {labels[perm] ?? perm}
        </span>
    );
}

export default function Index({ admins = [] }) {
    const { delete: destroy, processing } = useForm();

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to remove this admin?')) {
            destroy(route('admin.admins.destroy', id));
        }
    };

    return (
        <AdminLayout
            header={
                <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
                    Administrators
                </h2>
            }
        >
            <Head title="Administrators" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Action bar */}
                <div className="mb-6 flex items-center justify-between gap-4">
                    <p className="text-sm text-theme-dim">
                        {admins.length} administrator{admins.length !== 1 ? 's' : ''} in total
                    </p>
                    <Link
                        href={route('admin.admins.create')}
                        className="btn-ai-primary flex items-center gap-2"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        Add Admin
                    </Link>
                </div>

                {/* ── Mobile card view ── */}
                <div className="space-y-3 md:hidden">
                    {admins.map((admin) => (
                        <div key={admin.id} className="rounded-xl border border-theme-line bg-theme-glass p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                {/* Avatar + name */}
                                <div className="flex items-center gap-3 min-w-0">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-ai-accent to-ai-accent-deep text-sm font-bold text-white shadow">
                                        {(admin.name ?? '?').slice(0, 1).toUpperCase()}
                                    </div>
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold text-theme-strong">{admin.name}</p>
                                        <p className="truncate text-xs text-theme-dim">{admin.email}</p>
                                    </div>
                                </div>

                                {/* Role badge */}
                                {admin.id === 1 || admin.role === 'super_admin' ? (
                                    <span className="shrink-0 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        Super Admin
                                    </span>
                                ) : (
                                    <span className="shrink-0 rounded-full bg-sky-500/15 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:text-sky-400">
                                        Restricted
                                    </span>
                                )}
                            </div>

                            {/* Permissions */}
                            <div className="mt-3">
                                <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-theme-dim">Permissions</p>
                                <div className="flex flex-wrap gap-1.5">
                                    {admin.id === 1 || admin.role === 'super_admin' ? (
                                        <PermissionBadge perm="*" />
                                    ) : admin.permissions?.length > 0 ? (
                                        admin.permissions.map((p) => <PermissionBadge key={p} perm={p} />)
                                    ) : (
                                        <span className="text-xs text-theme-dim italic">No permissions</span>
                                    )}
                                </div>
                            </div>

                            {/* Actions */}
                            {admin.id !== 1 && admin.role !== 'super_admin' && (
                                <div className="mt-4 flex justify-end gap-2 border-t border-theme-line pt-3">
                                    <Link
                                        href={route('admin.admins.edit', admin.id)}
                                        className="rounded-lg border border-theme-line px-3 py-1.5 text-xs font-medium text-ai-accent hover:bg-white/[0.04]"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        onClick={() => handleDelete(admin.id)}
                                        disabled={processing}
                                        className="rounded-lg border border-red-500/20 px-3 py-1.5 text-xs font-medium text-red-500 hover:bg-red-500/10 disabled:opacity-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            )}
                        </div>
                    ))}

                    {admins.length === 0 && (
                        <div className="rounded-xl border border-theme-line bg-theme-glass py-12 text-center text-sm text-theme-dim">
                            No administrators yet.
                        </div>
                    )}
                </div>

                {/* ── Desktop table view ── */}
                <div className="hidden overflow-hidden rounded-xl border border-theme-line bg-theme-glass shadow-sm md:block">
                    <table className="min-w-full divide-y divide-theme-line text-sm">
                        <thead className="bg-theme-surface-soft">
                            <tr>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-theme-dim">Admin</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-theme-dim">Role</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-theme-dim">Permissions</th>
                                <th className="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-theme-dim">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-theme-line">
                            {admins.map((admin) => (
                                <tr key={admin.id} className="transition-colors hover:bg-white/[0.02]">
                                    {/* Name + email */}
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-ai-accent to-ai-accent-deep text-sm font-bold text-white shadow">
                                                {(admin.name ?? '?').slice(0, 1).toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-theme-strong">{admin.name}</p>
                                                <p className="text-xs text-theme-dim">{admin.email}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {/* Role */}
                                    <td className="whitespace-nowrap px-6 py-4">
                                        {admin.id === 1 || admin.role === 'super_admin' ? (
                                            <span className="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                Super Admin
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-sky-500/15 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:text-sky-400">
                                                Restricted Admin
                                            </span>
                                        )}
                                    </td>

                                    {/* Permissions */}
                                    <td className="px-6 py-4">
                                        <div className="flex flex-wrap gap-1.5">
                                            {admin.id === 1 || admin.role === 'super_admin' ? (
                                                <PermissionBadge perm="*" />
                                            ) : admin.permissions?.length > 0 ? (
                                                admin.permissions.map((p) => <PermissionBadge key={p} perm={p} />)
                                            ) : (
                                                <span className="text-xs text-theme-dim italic">None</span>
                                            )}
                                        </div>
                                    </td>

                                    {/* Actions */}
                                    <td className="whitespace-nowrap px-6 py-4 text-right">
                                        {admin.id !== 1 && admin.role !== 'super_admin' ? (
                                            <div className="flex justify-end gap-3">
                                                <Link
                                                    href={route('admin.admins.edit', admin.id)}
                                                    className="text-sm font-medium text-ai-accent hover:text-ai-accent-deep transition-colors"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(admin.id)}
                                                    disabled={processing}
                                                    className="text-sm font-medium text-red-500 hover:text-red-700 transition-colors"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-theme-dim italic">Protected</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {admins.length === 0 && (
                        <div className="py-16 text-center text-theme-dim">
                            No administrators yet.
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
