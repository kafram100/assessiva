import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

const secondaryBtn =
    'inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted shadow-sm transition duration-150 ease-in-out hover:bg-theme-highlight/80 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-[#0d1117]';

const primaryLink =
    'inline-flex items-center rounded-md border border-transparent bg-theme-highlight px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong transition duration-150 ease-in-out hover:bg-slate-600';

export default function Index({ courses }) {
    const rows = courses ?? [];

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Courses
                    </h2>
                    <Link
                        href={route('admin.management.courses.create')}
                        className={primaryLink}
                    >
                        New course
                    </Link>
                </div>
            }
        >
            <Head title="Courses" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    {rows.length === 0 ? (
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-4 sm:p-8 text-center text-sm text-theme-dim">
                            No courses yet. Create a course to manage class
                            rosters and link exams.
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-theme-line bg-theme-glass shadow">
                            <table className="min-w-full divide-y divide-theme text-sm">
                                <thead>
                                    <tr className="text-left text-xs font-medium uppercase tracking-wide text-theme-dim">
                                        <th className="px-6 py-3">Code</th>
                                        <th className="px-6 py-3">Name</th>
                                        <th className="px-6 py-3">Roster</th>
                                        <th className="px-6 py-3">Exams</th>
                                        <th className="px-6 py-3"> </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-theme">
                                    {rows.map((c) => (
                                        <tr key={c.id}>
                                            <td className="px-6 py-4 font-mono font-medium text-theme-strong">
                                                {c.code}
                                            </td>
                                            <td className="px-6 py-4 text-theme-muted">
                                                {c.name}
                                            </td>
                                            <td className="px-6 py-4 text-theme-dim">
                                                {c.allowed_indexes_count ?? 0}
                                            </td>
                                            <td className="px-6 py-4 text-theme-dim">
                                                {c.exams_count ?? 0}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={route(
                                                        'lecturer.courses.show',
                                                        c.id,
                                                    )}
                                                    className={secondaryBtn}
                                                >
                                                    Open
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
