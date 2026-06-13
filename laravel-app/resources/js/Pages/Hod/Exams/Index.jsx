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

function formatDt(value) {
    if (!value) {
        return '—';
    }
    try {
        return new Date(value).toLocaleString(undefined, {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    } catch {
        return value;
    }
}

export default function Index({ hasDepartment, exams }) {
    const rows = exams?.data ?? [];

    return (
        <HodLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Department exams
                </h2>
            }
        >
            <Head title="HOD — Exams" />

            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                {!hasDepartment ? (
                    <div className="rounded-lg border border-amber-400/50 bg-amber-50 p-6 text-amber-900 dark:border-amber-800/80 dark:bg-amber-950/40 dark:text-amber-100">
                        <p className="text-sm">
                            No department is assigned to your account, so this
                            list is unavailable.
                        </p>
                    </div>
                ) : (
                    <>
                        <p className="mb-4 text-sm text-theme-muted">
                            Read-only list of exams created by lecturers (and
                            HOD accounts) in your department. Open the lecturer
                            workspace to edit or publish an exam.
                        </p>
                        <div className="overflow-x-auto rounded-lg border border-theme-line bg-theme-glass">
                            <table className="min-w-full divide-y divide-theme text-sm text-theme-muted">
                                <thead className="bg-theme-highlight">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Title
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Course
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Owner
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Published
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-theme-strong">
                                            Window
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
                                                No exams found for this
                                                department.
                                            </td>
                                        </tr>
                                    ) : (
                                        rows.map((exam) => (
                                            <tr
                                                key={exam.id}
                                                className="hover:bg-theme-highlight/80"
                                            >
                                                <td className="px-4 py-3 font-medium text-theme-strong">
                                                    {exam.title}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className="font-mono text-xs text-theme-muted">
                                                        {exam.course_code ||
                                                            '—'}
                                                    </span>
                                                    {exam.course_name ? (
                                                        <span className="ms-2 text-theme-dim">
                                                            {exam.course_name}
                                                        </span>
                                                    ) : null}
                                                </td>
                                                <td className="px-4 py-3 text-theme-muted">
                                                    {exam.owner?.username ??
                                                        '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {exam.is_published ? (
                                                        <span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-900 ring-1 ring-inset ring-emerald-400/40 dark:bg-emerald-900/60 dark:text-emerald-200 dark:ring-emerald-800">
                                                            Yes
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-theme-highlight px-2.5 py-0.5 text-xs font-medium text-theme-muted ring-1 ring-inset ring-theme-line dark:bg-indigo-950 dark:text-indigo-300 dark:ring-indigo-800">
                                                            No
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-xs text-theme-dim">
                                                    {formatDt(
                                                        exam.start_datetime,
                                                    )}
                                                    <span className="mx-1 text-ai-accent">
                                                        →
                                                    </span>
                                                    {formatDt(
                                                        exam.end_datetime,
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <PaginationLinks links={exams?.links} />
                    </>
                )}
            </div>
        </HodLayout>
    );
}
