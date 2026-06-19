import ExportExcelButton from '@/Components/ExportExcelButton';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm } from '@inertiajs/react';

export default function Stats({
    exam,
    attempts,
    avgScore,
    highest,
    lowest,
    proctoringSnapshotCounts,
}) {
    const resetForm = useForm({ student_index: '' });

    const submitReset = (e) => {
        e.preventDefault();
        resetForm.post(
            route('admin.management.exams.reset-student-attempts', exam.id),
        );
    };

    const bulkRevisionPlans = () => {
        if (
            !confirm(
                'Generate AI revision plans for all completed attempts that do not have one yet? This may take a while.',
            )
        ) {
            return;
        }
        router.post(
            route('admin.management.exams.revision-plans.generate-all', exam.id),
        );
    };

    const releaseResults = () => {
        if (
            !confirm(
                'Send results emails to all completed attempts that have a student email, and mark results as released?',
            )
        ) {
            return;
        }
        router.post(route('admin.management.exams.results.release', exam.id));
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            Results: {exam.title}
                        </h2>
                        <p className="text-sm text-theme-dim">
                            Code{' '}
                            <span className="font-mono font-semibold">
                                {exam.exam_code}
                            </span>
                        </p>
                    </div>
                    <Link
                        href={route('admin.management.exams.show', exam.id)}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Back to exam
                    </Link>
                </div>
            }
        >
            <Head title={`Stats · ${exam.title}`} />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-3">
                        <a
                            href={route(
                                'lecturer.exams.stats.download',
                                exam.id,
                            )}
                            className="inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted shadow-sm hover:bg-theme-highlight/80"
                        >
                            Download CSV
                        </a>
                        <ExportExcelButton
                            href={route(
                                'lecturer.exams.results.export',
                                exam.id,
                            )}
                        />
                        <button
                            type="button"
                            onClick={releaseResults}
                            className="inline-flex items-center rounded-md border border-transparent bg-emerald-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-emerald-600"
                        >
                            Release results (email)
                        </button>
                        {exam.results_released && (
                            <span className="self-center text-xs font-medium text-emerald-800">
                                Results marked released
                            </span>
                        )}
                        {exam.results_released ? (
                            <button
                                type="button"
                                onClick={bulkRevisionPlans}
                                className="inline-flex items-center rounded-md border border-sky-700/60 bg-sky-950/40 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-sky-100 hover:bg-sky-900/50"
                            >
                                Generate missing revision plans
                            </button>
                        ) : null}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-4 shadow">
                            <p className="text-xs uppercase text-theme-dim">
                                Average
                            </p>
                            <p className="text-xl font-bold text-theme-strong">
                                {avgScore != null ? avgScore : '-'}
                            </p>
                        </div>
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-4 shadow">
                            <p className="text-xs uppercase text-theme-dim">
                                Highest
                            </p>
                            <p className="text-xl font-bold text-theme-strong">
                                {highest != null ? highest : '-'}
                            </p>
                        </div>
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-4 shadow">
                            <p className="text-xs uppercase text-theme-dim">
                                Lowest
                            </p>
                            <p className="text-xl font-bold text-theme-strong">
                                {lowest != null ? lowest : '-'}
                            </p>
                        </div>
                    </div>

                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <h3 className="text-base font-semibold text-theme-strong">
                            Reset attempts by index
                        </h3>
                        <p className="mt-1 text-sm text-theme-dim">
                            Deletes all attempts (and related proctoring logs) for
                            the given student index so they can sit again.
                        </p>
                        <form
                            onSubmit={submitReset}
                            className="mt-4 flex max-w-xl flex-col gap-3 sm:flex-row sm:items-end"
                        >
                            <div className="flex-1">
                                <label
                                    htmlFor="student_index"
                                    className="block text-xs font-medium text-theme-muted"
                                >
                                    Student index
                                </label>
                                <input
                                    id="student_index"
                                    type="text"
                                    value={resetForm.data.student_index}
                                    onChange={(e) =>
                                        resetForm.setData(
                                            'student_index',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-theme-line shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                {resetForm.errors.student_index && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {resetForm.errors.student_index}
                                    </p>
                                )}
                            </div>
                            <PrimaryButton disabled={resetForm.processing}>
                                Reset
                            </PrimaryButton>
                        </form>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-theme-line bg-theme-glass shadow">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-surface-soft/60">
                                <tr>
                                    <th className="px-4 py-3 text-left font-semibold text-theme-muted">
                                        Student
                                    </th>
                                    <th className="px-4 py-3 text-left font-semibold text-theme-muted">
                                        Index
                                    </th>
                                    <th className="px-4 py-3 text-right font-semibold text-theme-muted">
                                        Score
                                    </th>
                                    <th className="px-4 py-3 text-center font-semibold text-theme-muted">
                                        Flags
                                    </th>
                                    <th className="px-4 py-3 text-center font-semibold text-theme-muted">
                                        Media
                                    </th>
                                    <th className="px-4 py-3 text-right font-semibold text-theme-muted">
                                        Submitted
                                    </th>
                                    <th className="px-4 py-3 text-right font-semibold text-theme-muted">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme">
                                {attempts.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-theme-dim"
                                        >
                                            No completed attempts yet.
                                        </td>
                                    </tr>
                                )}
                                {attempts.map((a) => (
                                    <tr key={a.id}>
                                        <td className="px-4 py-3 text-theme-strong">
                                            {a.student_fullname || '-'}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-theme-muted">
                                            {a.student_index}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            <div className="inline-block text-right">
                                                <div className="font-semibold text-theme-strong">
                                                    {a.score} / {a.total_marks}
                                                </div>
                                                <div className="text-[11px] leading-tight text-theme-dim">
                                                    raw{' '}
                                                    {a.raw_score != null
                                                        ? Number(a.raw_score).toFixed(2)
                                                        : '-'}
                                                    {Number(a.proctoring_penalty_total ?? 0) >
                                                    0 ? (
                                                        <>
                                                            {' '}
                                                            · penalty −
                                                            {Number(
                                                                a.proctoring_penalty_total,
                                                            ).toFixed(2)}
                                                        </>
                                                    ) : null}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center text-xs text-theme-dim">
                                            CE {a.cheat_events_count ?? 0} · CI{' '}
                                            {a.cheating_incidents_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-center text-xs text-theme-dim">
                                            {proctoringSnapshotCounts[a.id] ??
                                                0}{' '}
                                            files
                                        </td>
                                        <td className="px-4 py-3 text-right text-theme-dim">
                                            {a.submit_time
                                                ? new Date(
                                                      a.submit_time,
                                                  ).toLocaleString()
                                                : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route(
                                                    'lecturer.grades.attempt',
                                                    a.id,
                                                )}
                                                className="font-medium text-sky-700 hover:text-sky-900"
                                            >
                                                Grade
                                            </Link>
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
