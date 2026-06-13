import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

function StatusBadge() {
    return (
        <span className="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900 dark:bg-amber-900/50 dark:text-amber-200">
            Pending
        </span>
    );
}

export default function Pending({ lecturers = [] }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Pending lecturer requests
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('admin.lecturers')}
                            className="rounded-md border border-theme-line px-4 py-2 text-sm font-medium text-theme-muted hover:bg-theme-surface-soft"
                        >
                            All staff
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Pending lecturers" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="border-b border-theme-line px-4 py-3 text-sm text-theme-muted">
                        {lecturers.length} pending request
                        {lecturers.length === 1 ? '' : 's'}
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Staff ID</th>
                                    <th className="px-4 py-3">Phone</th>
                                    <th className="px-4 py-3">Department</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {lecturers.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No pending lecturer requests.
                                        </td>
                                    </tr>
                                )}
                                {lecturers.map((lec) => (
                                    <tr
                                        key={lec.id}
                                        className="hover:bg-theme-elevated"
                                    >
                                        <td className="px-4 py-3 font-medium text-theme-strong">
                                            {lec.name}
                                        </td>
                                        <td className="px-4 py-3">{lec.email}</td>
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {lec.staff_id ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">{lec.phone ?? '—'}</td>
                                        <td className="px-4 py-3 text-xs">
                                            {lec.department ? (
                                                <>
                                                    {lec.department.name}
                                                    {lec.department.school
                                                        ? ` · ${lec.department.school.name}`
                                                        : ''}
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge />
                                        </td>
                                        <td className="px-4 py-3">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    router.post(
                                                        route(
                                                            'admin.lecturers.approve',
                                                            lec.id,
                                                        ),
                                                    )
                                                }
                                                className="me-2 rounded bg-emerald-700 px-2 py-1 text-xs text-white hover:bg-emerald-600"
                                            >
                                                Approve
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            'Reject this lecturer request?',
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
