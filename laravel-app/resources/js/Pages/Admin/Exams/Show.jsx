import AdminPagination from '@/Components/AdminPagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

function fmt(dt) {
    if (!dt) {
        return '—';
    }
    return new Date(dt).toLocaleString();
}

function scheduleBadge(exam, nowIso) {
    const now = new Date(nowIso);
    const start = exam.start_datetime ? new Date(exam.start_datetime) : null;
    const end = exam.end_datetime ? new Date(exam.end_datetime) : null;
    if (!exam.is_published) {
        return { label: 'Draft', cls: 'bg-slate-600 text-theme-strong' };
    }
    if (start && now < start) {
        return { label: 'Upcoming', cls: 'bg-sky-700 text-white' };
    }
    if (end && now > end) {
        return { label: 'Ended', cls: 'bg-theme-surface-soft text-theme-muted' };
    }
    return { label: 'In window', cls: 'bg-emerald-700 text-white' };
}

export default function Show({ exam, attempts, now }) {
    const u = exam.user;
    const badge = scheduleBadge(exam, now);
    const errors = usePage().props.errors ?? {};
    const studentIndexError = Array.isArray(errors.student_index)
        ? errors.student_index[0]
        : errors.student_index;

    const resetForIndex = (studentIndex) => {
        if (
            !confirm(
                'Delete all attempts for this student on this exam? They will be able to start again (within the allowed number of attempts).',
            )
        ) {
            return;
        }
        router.post(
            route('admin.exams.reset-student-attempts', exam.id),
            { student_index: studentIndex },
        );
    };

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    Exam attempts
                </h2>
            }
        >
            <Head title={`Attempts — ${exam.title}`} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.exams')}
                    className="inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← All exams
                </Link>

                {studentIndexError && (
                    <div className="rounded border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-200">
                        {studentIndexError}
                    </div>
                )}

                <div className="rounded-lg border border-theme-line bg-theme-glass p-6">
                    <h2 className="text-lg font-bold text-theme-strong">
                        {exam.title}
                    </h2>
                    <div className="mt-4 grid gap-2 text-sm text-theme-dim md:grid-cols-2">
                        <div>
                            <span className="text-theme-dim">Course:</span>{' '}
                            <span className="text-theme-muted">
                                {exam.course_name}
                                {exam.course_code
                                    ? ` (${exam.course_code})`
                                    : ''}
                            </span>
                        </div>
                        <div>
                            <span className="text-theme-dim">Lecturer:</span>{' '}
                            <span className="text-theme-muted">
                                {u?.username ?? '—'}
                            </span>
                            {u?.email && (
                                <span className="text-theme-dim">
                                    {' '}
                                    · {u.email}
                                </span>
                            )}
                        </div>
                        <div>
                            <span className="text-theme-dim">Dept / school:</span>{' '}
                            <span className="text-theme-muted">
                                {u?.department ? (
                                    <>
                                        {u.department.name}
                                        {u.department.school
                                            ? ` — ${u.department.school.name}`
                                            : ''}
                                    </>
                                ) : (
                                    '—'
                                )}
                            </span>
                        </div>
                        <div>
                            <span className="text-theme-dim">Window:</span>{' '}
                            <span className="text-theme-muted">
                                {fmt(exam.start_datetime)} →{' '}
                                {fmt(exam.end_datetime)}
                            </span>
                        </div>
                        <div className="md:col-span-2">
                            <span
                                className={`inline-block rounded px-2 py-0.5 text-xs ${badge.cls}`}
                            >
                                {badge.label}
                            </span>
                            {exam.exam_type && (
                                <span className="ms-2 rounded border border-theme-line px-2 py-0.5 text-xs text-theme-dim">
                                    {exam.exam_type}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="border-b border-theme-line px-4 py-3 text-sm text-theme-muted">
                        <span className="font-medium text-theme-strong">
                            Student attempts ({attempts.total})
                        </span>
                        <p className="mt-2 text-xs text-theme-dim">
                            This exam allows{' '}
                            <strong className="text-theme-muted">
                                {exam.attempts_allowed}
                            </strong>{' '}
                            attempt(s) per registered index. Use{' '}
                            <strong className="text-theme-muted">Reset</strong> to
                            clear attempts for an index so the student can start
                            again.
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Student</th>
                                    <th className="px-4 py-3">Index</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Score</th>
                                    <th className="px-4 py-3">Started</th>
                                    <th className="px-4 py-3">Submitted</th>
                                    <th className="px-4 py-3 text-end">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {attempts.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No attempts yet for this exam.
                                        </td>
                                    </tr>
                                )}
                                {attempts.data.map((a) => (
                                    <tr key={a.id} className="hover:bg-theme-elevated">
                                        <td className="px-4 py-3">
                                            {a.student_fullname ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {a.student_index}
                                        </td>
                                        <td
                                            className="max-w-[10rem] truncate px-4 py-3 text-xs text-theme-dim"
                                            title={a.student_email ?? ''}
                                        >
                                            {a.student_email ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {a.status === 'ongoing' && (
                                                <span className="rounded bg-amber-900/50 px-2 py-0.5 text-xs text-amber-200">
                                                    Ongoing
                                                </span>
                                            )}
                                            {a.status === 'completed' && (
                                                <span className="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-900 dark:bg-emerald-900/50 dark:text-emerald-200">
                                                    Completed
                                                </span>
                                            )}
                                            {a.status !== 'ongoing' &&
                                                a.status !== 'completed' && (
                                                    <span className="rounded bg-theme-highlight px-2 py-0.5 text-xs">
                                                        {a.status}
                                                    </span>
                                                )}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {a.status === 'completed'
                                                ? `${a.score}/${a.total_marks}`
                                                : '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-xs text-theme-dim">
                                            {fmt(a.start_time)}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-xs text-theme-dim">
                                            {fmt(a.submit_time)}
                                        </td>
                                        <td className="px-4 py-3 text-end">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    resetForIndex(
                                                        a.student_index,
                                                    )
                                                }
                                                className="rounded border border-amber-700 px-2 py-1 text-xs text-amber-200 hover:bg-amber-900/30"
                                            >
                                                Reset attempts
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination links={attempts.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
