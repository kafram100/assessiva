import HodLayout from '@/Layouts/HodLayout';
import { Head, Link } from '@inertiajs/react';

function PaginationLinks({ links }) {
    if (!links?.length) {
        return null;
    }
    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((link, i) => (
                <span key={i}>
                    {link.url ? (
                        <Link
                            href={link.url}
                            preserveState
                            className={`inline-block rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-ai-accent font-semibold text-white'
                                    : 'bg-theme-highlight text-theme-muted hover:bg-theme-surface-soft'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            className="inline-block rounded bg-theme-highlight px-3 py-1 text-sm text-theme-dim"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </span>
            ))}
        </div>
    );
}

function statusBadge(status) {
    const map = {
        approved:
            'bg-emerald-100 text-emerald-900 ring-emerald-400/40 dark:bg-emerald-900/60 dark:text-emerald-200 dark:ring-emerald-800',
        pending:
            'bg-amber-100 text-amber-900 ring-amber-400/40 dark:bg-amber-900/60 dark:text-amber-200 dark:ring-amber-800',
        rejected:
            'bg-rose-100 text-rose-900 ring-rose-400/40 dark:bg-rose-900/60 dark:text-rose-200 dark:ring-rose-800',
    };
    const cls =
        map[status] ??
        'bg-indigo-100 text-indigo-900 ring-indigo-400/40 dark:bg-indigo-900 dark:text-indigo-200 dark:ring-indigo-800';
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${cls}`}
        >
            {status}
        </span>
    );
}

export default function Index({ hasDepartment, lecturers }) {
    const rows = lecturers?.data ?? [];

    return (
        <HodLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Lecturer directory
                </h2>
            }
        >
            <Head title="HOD — Lecturers" />

            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                {!hasDepartment ? (
                    <div className="rounded-lg border border-amber-400/50 bg-amber-50 p-6 text-amber-900 dark:border-amber-800/80 dark:bg-amber-950/40 dark:text-amber-100">
                        <p className="text-sm">
                            No department is assigned to your account, so this
                            directory is unavailable.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-lg border border-theme-line bg-theme-glass">
                            <table className="min-w-full divide-y divide-theme text-sm text-theme-muted">
                                <thead className="bg-theme-highlight">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Username
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Email
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-right font-semibold text-theme-strong">
                                            Exams
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-theme">
                                    {rows.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-4 py-8 text-center text-theme-dim"
                                            >
                                                No lecturers in this department
                                                yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        rows.map((row) => (
                                            <tr
                                                key={row.id}
                                                className="hover:bg-theme-highlight/80"
                                            >
                                                <td className="px-4 py-3 font-mono text-xs text-theme-strong">
                                                    {row.username}
                                                </td>
                                                <td className="px-4 py-3 text-theme-strong">
                                                    {row.name}
                                                </td>
                                                <td className="max-w-[200px] truncate px-4 py-3 text-theme-muted">
                                                    {row.email}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {statusBadge(row.status)}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums text-theme-muted">
                                                    {row.exams_count ?? 0}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <PaginationLinks links={lecturers?.links} />
                    </>
                )}
            </div>
        </HodLayout>
    );
}
