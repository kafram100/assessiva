import AdminPagination from '@/Components/AdminPagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Checkbox from '@/Components/Checkbox';
import DangerButton from '@/Components/DangerButton';

function fmt(dt) {
    if (!dt) {
        return '-';
    }
    return new Date(dt).toLocaleString();
}

export default function Index({ students, search }) {
    const { auth } = usePage().props;
    const permissions = auth.admin_permissions || [];
    const canExport = permissions.includes('*') || permissions.includes('export_users');
    
    const [selected, setSelected] = useState([]);

    const toggleAll = (e) => {
        if (e.target.checked) {
            setSelected(students.data.map((s) => s.id));
        } else {
            setSelected([]);
        }
    };

    const toggleOne = (id) => {
        if (selected.includes(id)) {
            setSelected(selected.filter((i) => i !== id));
        } else {
            setSelected([...selected, id]);
        }
    };

    const deleteUsers = (ids) => {
        if (
            !confirm(
                `Are you sure you want to delete ${ids.length} user(s)? This action cannot be undone.`,
            )
        ) {
            return;
        }

        router.delete(route('admin.students.delete'), {
            data: { ids },
            preserveScroll: true,
            onSuccess: () => setSelected([]),
        });
    };

    const allSelected =
        students.data.length > 0 && selected.length === students.data.length;

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    All users
                </h2>
            }
        >
            <Head title="Users" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.dashboard')}
                    className="mb-6 inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to admin
                </Link>

                <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                    <form
                        method="get"
                        action={route('admin.students')}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <div>
                            <label className="block text-xs text-theme-dim">
                                Search
                            </label>
                            <input
                                type="text"
                                name="q"
                                defaultValue={search}
                                placeholder="Username, name, email…"
                                className="mt-1 rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong"
                            />
                        </div>
                        <button
                            type="submit"
                            className="btn-ai-primary !rounded-md !px-4 !py-2 !text-sm"
                        >
                            Search
                        </button>
                    </form>

                    <div className="flex flex-wrap items-center gap-2">
                        {selected.length > 0 && (
                            <DangerButton
                                onClick={() => deleteUsers(selected)}
                                className="!py-2"
                            >
                                Delete Selected ({selected.length})
                            </DangerButton>
                        )}
                        {canExport && (
                            <>
                                <a
                                    href={`${route('admin.export.user_emails')}${selected.length > 0 ? `?ids=${selected.join(',')}` : ''}`}
                                    className="rounded border border-theme-line bg-theme-surface px-3 py-2 text-sm font-medium text-theme-strong hover:bg-theme-elevated"
                                >
                                    Export Emails (CSV)
                                </a>
                                <a
                                    href={`${route('admin.export.user_phones')}${selected.length > 0 ? `?ids=${selected.join(',')}` : ''}`}
                                    className="rounded border border-theme-line bg-theme-surface px-3 py-2 text-sm font-medium text-theme-strong hover:bg-theme-elevated"
                                >
                                    Export Phones (TXT)
                                </a>
                            </>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3 w-12 text-center">
                                        <Checkbox
                                            checked={allSelected}
                                            onChange={toggleAll}
                                        />
                                    </th>
                                    <th className="px-4 py-3">Username</th>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Phone</th>
                                    <th className="px-4 py-3 text-center">Practice Sessions</th>
                                    <th className="px-4 py-3">Joined On</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {students.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No users found.
                                        </td>
                                    </tr>
                                )}
                                {students.data.map((row) => (
                                    <tr key={row.id} className="hover:bg-theme-elevated">
                                        <td className="px-4 py-3 text-center">
                                            <Checkbox
                                                checked={selected.includes(row.id)}
                                                onChange={() => toggleOne(row.id)}
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-xs">{row.username}</td>
                                        <td className="px-4 py-3">{row.name ?? '-'}</td>
                                        <td className="px-4 py-3">{row.phone ?? '-'}</td>
                                        <td className="px-4 py-3 text-center">{row.practice_sessions_count ?? 0}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-xs text-theme-dim">
                                            {fmt(row.created_at)}
                                        </td>
                                        <td className="px-4 py-3">{row.email}</td>
                                        <td className="px-4 py-3 text-right">
                                            <button
                                                onClick={() => deleteUsers([row.id])}
                                                className="text-[var(--color-danger)] hover:opacity-80 transition"
                                                title="Delete User"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination links={students.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
