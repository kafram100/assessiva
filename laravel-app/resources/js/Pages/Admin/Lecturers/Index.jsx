import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function StatusBadge({ status }) {
    if (status === 'pending') {
        return (
            <span className="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900 dark:bg-amber-900/50 dark:text-amber-200">
                Pending
            </span>
        );
    }
    if (status === 'approved') {
        return (
            <span className="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-900 dark:bg-emerald-900/50 dark:text-emerald-200">
                Approved
            </span>
        );
    }
    if (status === 'suspended') {
        return (
            <span className="rounded bg-orange-100 px-2 py-0.5 text-xs text-orange-900 dark:bg-orange-900/50 dark:text-orange-200">
                Suspended
            </span>
        );
    }
    if (status === 'rejected') {
        return (
            <span className="rounded bg-rose-100 px-2 py-0.5 text-xs text-rose-900 dark:bg-rose-900/50 dark:text-rose-200">
                Rejected
            </span>
        );
    }
    return (
        <span className="rounded bg-slate-200 px-2 py-0.5 text-xs text-slate-800 dark:bg-slate-700 dark:text-slate-200">
            {status}
        </span>
    );
}



function RoleBadge({ role }) {
    return (
        <span
            className="rounded px-2 py-0.5 text-xs font-semibold uppercase bg-theme-highlight text-theme-muted"
        >
            {role}
        </span>
    );
}

function DepartmentAssignCell({ staff, departments }) {
    const [deptId, setDeptId] = useState(
        staff.department_id != null ? String(staff.department_id) : '',
    );

    useEffect(() => {
        setDeptId(
            staff.department_id != null ? String(staff.department_id) : '',
        );
    }, [staff.department_id]);

    const save = () => {
        router.put(route('admin.lecturers.department.update', staff.id), {
            department_id: deptId === '' ? null : parseInt(deptId, 10),
        });
    };

    if (departments.length === 0) {
        return (
            <div className="mt-2 border-t border-theme-line pt-2 text-xs text-amber-200/90">
                Create a department first to assign staff.
                <Link
                    href={route('admin.departments.create')}
                    className="ms-1 font-semibold text-sky-400 hover:text-sky-300"
                >
                    Add department
                </Link>
            </div>
        );
    }

    return (
        <div className="mt-2 flex flex-col gap-1 border-t border-theme-line pt-2">
            <label className="text-[0.65rem] font-medium uppercase tracking-wide text-theme-dim">
                Set department
            </label>
            <select
                id={`dept-${staff.id}`}
                value={deptId}
                onChange={(e) => setDeptId(e.target.value)}
                className="max-w-[240px] rounded border border-theme-line bg-theme-mesh px-2 py-1 text-xs text-theme-strong"
            >
                <option value="">- No department -</option>
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
                className="self-start rounded bg-theme-highlight px-2 py-1 text-xs font-medium text-theme-strong hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Save department
            </button>
        </div>
    );
}

export default function Index({ lecturers, departments = [] }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Academic staff
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href={route('admin.lecturers.pending')}
                            className="rounded-md border border-amber-500/40 bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-200 hover:bg-amber-500/20"
                        >
                            Pending requests
                        </Link>

                        <Link
                            href={route('admin.lecturers.create')}
                            className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                        >
                            Add staff
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Staff" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="border-b border-theme-line px-4 py-3 text-sm font-medium text-theme-muted">
                        Lecturer accounts
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Username</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Role</th>
                                    <th className="px-4 py-3">
                                        Department / school
                                    </th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {lecturers.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No academic staff accounts.
                                        </td>
                                    </tr>
                                )}
                                {lecturers.map((lec) => (
                                    <tr
                                        key={lec.id}
                                        className="hover:bg-theme-elevated"
                                    >
                                        <td className="px-4 py-3 font-medium text-theme-strong">
                                            {lec.username}
                                        </td>
                                        <td className="px-4 py-3">{lec.email}</td>
                                        <td className="px-4 py-3">
                                            <RoleBadge role={lec.role} />
                                        </td>
                                        <td className="px-4 py-3 text-xs text-theme-dim">
                                            {lec.department ? (
                                                <>
                                                    {lec.department.name}
                                                    {lec.department.school
                                                        ? ` (${lec.department.school.name})`
                                                        : ''}
                                                </>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={lec.status} />
                                            {lec.is_active === false ? (
                                                <span className="ms-1 text-[0.65rem] font-semibold uppercase text-amber-300">
                                                    Inactive
                                                </span>
                                            ) : null}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            {lec.status === 'pending' && (
                                                <>
                                                    <button
                                                        type="button"
                                                        disabled={false}
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'admin.lecturers.approve',
                                                                    lec.id,
                                                                ),
                                                            )
                                                        }
                                                        className="me-2 rounded bg-emerald-700 px-2 py-1 text-xs text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-40"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (
                                                                confirm(
                                                                    'Reject this account?',
                                                                )
                                                            ) {
                                                                router.post(
                                                                    route(
                                                                        'admin.lecturers.reject',
                                                                        lec.id,
                                                                    ),
                                                                );
                                                            }
                                                        }}
                                                        className="rounded bg-rose-700 px-2 py-1 text-xs text-theme-strong hover:bg-rose-600"
                                                    >
                                                        Reject
                                                    </button>
                                                </>
                                            )}
                                            {lec.status === 'rejected' && (
                                                <button
                                                    type="button"
                                                    disabled={false}
                                                    onClick={() =>
                                                        router.post(
                                                            route(
                                                                'admin.lecturers.approve',
                                                                lec.id,
                                                            ),
                                                        )
                                                    }
                                                    className="rounded border border-emerald-600 px-2 py-1 text-xs text-emerald-300 hover:bg-emerald-900/30 disabled:cursor-not-allowed disabled:opacity-40"
                                                >
                                                    Approve
                                                </button>
                                            )}
                                            {lec.status === 'approved' &&
                                                lec.role === 'lecturer' &&
                                                lec.is_active !== false && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                if (
                                                                    confirm(
                                                                        'Suspend this lecturer? They will not be able to sign in.',
                                                                    )
                                                                ) {
                                                                    router.post(
                                                                        route(
                                                                            'admin.lecturers.suspend',
                                                                            lec.id,
                                                                        ),
                                                                    );
                                                                }
                                                            }}
                                                            className="mb-2 me-2 rounded bg-orange-800 px-2 py-1 text-xs text-white hover:bg-orange-700"
                                                        >
                                                            Suspend
                                                        </button>

                                                    </>
                                                )}
                                            <DepartmentAssignCell
                                                staff={lec}
                                                departments={departments}
                                            />
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
