import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function DepartmentSelectCell({ hod, departments }) {
    const [deptId, setDeptId] = useState(
        hod.department_id != null ? String(hod.department_id) : '',
    );

    useEffect(() => {
        setDeptId(
            hod.department_id != null ? String(hod.department_id) : '',
        );
    }, [hod.department_id]);

    const initial =
        hod.department_id != null ? String(hod.department_id) : '';
    const dirty = deptId !== initial;

    const save = () => {
        if (!deptId) {
            return;
        }
        router.put(route('admin.hods.department.update', hod.id), {
            department_id: parseInt(deptId, 10),
        });
    };

    return (
        <div className="flex max-w-xs flex-col gap-1">
            <select
                value={deptId}
                onChange={(e) => setDeptId(e.target.value)}
                className="rounded border border-theme-line bg-theme-mesh px-2 py-1 text-xs text-theme-strong"
            >
                {departments.map((d) => (
                    <option key={d.id} value={d.id}>
                        {d.name}
                        {d.school?.name ? ` (${d.school.name})` : ''}
                    </option>
                ))}
            </select>
            <button
                type="button"
                disabled={!dirty}
                onClick={save}
                className="self-start rounded bg-theme-highlight px-2 py-1 text-xs text-theme-strong hover:bg-slate-600 disabled:opacity-40"
            >
                Save department
            </button>
        </div>
    );
}

export default function Index({ hods = [], departments = [] }) {
    const { flash } = usePage().props;

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Heads of Department
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('admin.hods.create')}
                            className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                        >
                            Create HOD
                        </Link>
                        <Link
                            href={route('admin.lecturers')}
                            className="rounded-md border border-theme-line px-4 py-2 text-sm font-medium text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Academic staff
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="HOD management" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {flash?.error ? (
                    <div className="mb-4 rounded border border-rose-500/40 bg-rose-950/40 px-4 py-3 text-sm text-rose-200">
                        {flash.error}
                    </div>
                ) : null}

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Username</th>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Department</th>
                                    <th className="px-4 py-3">Active</th>
                                    <th className="px-4 py-3">Change dept</th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {hods.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No HOD accounts yet.
                                        </td>
                                    </tr>
                                )}
                                {hods.map((hod) => (
                                    <tr key={hod.id} className="hover:bg-theme-elevated">
                                        <td className="px-4 py-3 font-medium text-theme-strong">
                                            {hod.username}
                                        </td>
                                        <td className="px-4 py-3">{hod.name}</td>
                                        <td className="px-4 py-3">{hod.email}</td>
                                        <td className="px-4 py-3 text-xs">
                                            {hod.department ? (
                                                <>
                                                    {hod.department.name}
                                                    {hod.department.school
                                                        ? ` (${hod.department.school.name})`
                                                        : ''}
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {hod.is_active ? (
                                                <span className="rounded bg-emerald-900/40 px-2 py-0.5 text-xs text-emerald-200">
                                                    Yes
                                                </span>
                                            ) : (
                                                <span className="rounded bg-slate-700 px-2 py-0.5 text-xs text-slate-200">
                                                    No
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {hod.is_active ? (
                                                <DepartmentSelectCell
                                                    hod={hod}
                                                    departments={departments}
                                                />
                                            ) : (
                                                <span className="text-xs text-theme-dim">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {hod.is_active ? (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        if (
                                                            confirm(
                                                                'Deactivate this HOD account? They will no longer be able to sign in.',
                                                            )
                                                        ) {
                                                            router.post(
                                                                route(
                                                                    'admin.hods.deactivate',
                                                                    hod.id,
                                                                ),
                                                            );
                                                        }
                                                    }}
                                                    className="rounded bg-rose-800 px-2 py-1 text-xs text-white hover:bg-rose-700"
                                                >
                                                    Deactivate
                                                </button>
                                            ) : (
                                                <span className="text-xs text-theme-dim">
                                                    Deactivated
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
