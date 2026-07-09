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
                <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
                    All users
                </h2>
            }
        >
            <Head title="Users" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                
                {/* Back link */}
                <Link
                    href={route('admin.dashboard')}
                    className="mb-6 inline-flex items-center gap-1.5 text-sm text-theme-dim transition-colors hover:text-theme-strong"
                >
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to admin
                </Link>

                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <form
                        method="get"
                        action={route('admin.students')}
                        className="flex flex-wrap items-end gap-3 w-full sm:w-auto"
                    >
                        <div className="flex-1 sm:flex-none">
                            <label className="block text-xs font-medium text-theme-dim">
                                Search
                            </label>
                            <input
                                type="text"
                                name="q"
                                defaultValue={search}
                                placeholder="Username, name, email…"
                                className="mt-1 block w-full rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong focus:border-ai-accent focus:ring-ai-accent sm:w-64"
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
                            <div className="flex flex-wrap gap-2">
                                <a
                                    href={`${route('admin.export.user_emails')}${selected.length > 0 ? `?ids=${selected.join(',')}` : ''}`}
                                    className="rounded border border-theme-line bg-white/[0.04] px-3 py-2 text-sm font-medium text-theme-strong transition hover:bg-theme-elevated hover:border-ai-accent/40"
                                >
                                    Export Emails
                                </a>
                                <a
                                    href={`${route('admin.export.user_phones')}${selected.length > 0 ? `?ids=${selected.join(',')}` : ''}`}
                                    className="rounded border border-theme-line bg-white/[0.04] px-3 py-2 text-sm font-medium text-theme-strong transition hover:bg-theme-elevated hover:border-ai-accent/40"
                                >
                                    Export Phones
                                </a>
                            </div>
                        )}
                    </div>
                </div>

                {/* ── Mobile card view ── */}
                <div className="space-y-4 md:hidden">
                    {/* Select All Bar (Mobile) */}
                    {students.data.length > 0 && (
                        <div className="flex items-center gap-3 rounded-lg border border-theme-line bg-theme-glass p-4 shadow-sm">
                            <Checkbox checked={allSelected} onChange={toggleAll} />
                            <span className="text-sm font-medium text-theme-strong">Select All ({students.data.length})</span>
                        </div>
                    )}
                    
                    {students.data.length === 0 && (
                        <div className="py-10 text-center text-sm text-theme-dim rounded-lg border border-theme-line bg-theme-glass">
                            No users found.
                        </div>
                    )}

                    {students.data.map((row) => (
                        <div key={row.id} className={`rounded-xl border transition-colors bg-theme-glass p-4 shadow-sm ${selected.includes(row.id) ? 'border-ai-accent/50 bg-ai-accent/5' : 'border-theme-line'}`}>
                            <div className="flex items-start gap-3">
                                <div className="mt-1">
                                    <Checkbox
                                        checked={selected.includes(row.id)}
                                        onChange={() => toggleOne(row.id)}
                                    />
                                </div>
                                
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between gap-2">
                                        <div className="flex items-center gap-2 min-w-0">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-ai-accent to-ai-accent-deep text-xs font-bold text-white shadow-sm">
                                                {(row.name || row.username || '?').slice(0, 1).toUpperCase()}
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate font-semibold text-theme-strong">{row.name || '-'}</p>
                                                <p className="truncate text-xs text-ai-accent">@{row.username}</p>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => deleteUsers([row.id])}
                                            className="shrink-0 p-1.5 text-red-500 hover:bg-red-500/10 rounded-lg transition"
                                            title="Delete User"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div className="mt-3 grid grid-cols-2 gap-y-2 gap-x-4 text-xs">
                                        <div>
                                            <span className="block text-[10px] font-semibold uppercase text-theme-dim">Email</span>
                                            <span className="text-theme-strong truncate block">{row.email}</span>
                                        </div>
                                        <div>
                                            <span className="block text-[10px] font-semibold uppercase text-theme-dim">Phone</span>
                                            <span className="text-theme-strong">{row.phone || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="block text-[10px] font-semibold uppercase text-theme-dim">Practice Sessions</span>
                                            <span className="text-theme-strong inline-flex items-center rounded-full bg-theme-surface-soft px-2 py-0.5">{row.practice_sessions_count || 0}</span>
                                        </div>
                                        <div>
                                            <span className="block text-[10px] font-semibold uppercase text-theme-dim">Joined</span>
                                            <span className="text-theme-muted">{fmt(row.created_at)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* ── Desktop table view ── */}
                <div className="hidden md:block overflow-hidden rounded-lg border border-theme-line bg-theme-glass shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme-line text-sm">
                            <thead className="bg-white/[0.03] text-left text-xs font-semibold uppercase tracking-wider text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3.5 w-12 text-center">
                                        <Checkbox checked={allSelected} onChange={toggleAll} />
                                    </th>
                                    <th className="px-4 py-3.5">User</th>
                                    <th className="px-4 py-3.5">Contact</th>
                                    <th className="px-4 py-3.5 text-center">Sessions</th>
                                    <th className="px-4 py-3.5">Joined On</th>
                                    <th className="px-4 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme-line">
                                {students.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-10 text-center text-theme-dim">
                                            No users found.
                                        </td>
                                    </tr>
                                )}
                                {students.data.map((row) => (
                                    <tr key={row.id} className={`transition-colors hover:bg-white/[0.02] ${selected.includes(row.id) ? 'bg-ai-accent/5' : ''}`}>
                                        <td className="px-4 py-4 text-center">
                                            <Checkbox checked={selected.includes(row.id)} onChange={() => toggleOne(row.id)} />
                                        </td>
                                        <td className="px-4 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-ai-accent to-ai-accent-deep text-sm font-bold text-white shadow-sm">
                                                    {(row.name || row.username || '?').slice(0, 1).toUpperCase()}
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-theme-strong">{row.name || '-'}</p>
                                                    <p className="text-xs text-ai-accent">@{row.username}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-4">
                                            <p className="text-theme-strong text-xs">{row.email}</p>
                                            <p className="text-theme-muted text-xs">{row.phone || 'No phone'}</p>
                                        </td>
                                        <td className="px-4 py-4 text-center">
                                            <span className="inline-flex items-center justify-center min-w-[2rem] rounded-full bg-theme-surface-soft px-2 py-1 text-xs font-medium text-theme-strong border border-theme-line">
                                                {row.practice_sessions_count || 0}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-4 text-xs text-theme-muted">
                                            {fmt(row.created_at)}
                                        </td>
                                        <td className="px-4 py-4 text-right">
                                            <button
                                                onClick={() => deleteUsers([row.id])}
                                                className="text-red-500 hover:text-red-700 transition"
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
                </div>
                
                <div className="mt-4">
                    <AdminPagination links={students.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
