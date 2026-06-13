import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ exams, stats }) {
    return (
        <LecturerLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Students & results overview
                </h2>
            }
        >
            <Head title="Students" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-5 shadow">
                            <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                Unique students
                            </p>
                            <p className="mt-2 text-2xl font-bold text-theme-strong">
                                {stats.unique_students}
                            </p>
                        </div>
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-5 shadow">
                            <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                Completed attempts
                            </p>
                            <p className="mt-2 text-2xl font-bold text-theme-strong">
                                {stats.total_attempts}
                            </p>
                        </div>
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-5 shadow">
                            <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                Average score
                            </p>
                            <p className="mt-2 text-2xl font-bold text-theme-strong">
                                {stats.avg_score != null ? stats.avg_score : '—'}
                            </p>
                        </div>
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-5 shadow">
                            <p className="text-xs font-semibold uppercase tracking-wide text-theme-dim">
                                Highest score
                            </p>
                            <p className="mt-2 text-2xl font-bold text-theme-strong">
                                {stats.max_score != null ? stats.max_score : '—'}
                            </p>
                        </div>
                    </div>

                    <div className="overflow-hidden border border-theme-line bg-theme-glass shadow sm:rounded-lg">
                        <div className="border-b border-theme-line px-6 py-4">
                            <h3 className="text-lg font-medium text-theme-strong">
                                Your exams
                            </h3>
                            <p className="mt-1 text-sm text-theme-dim">
                                Open an exam for detailed attempt stats, CSV export,
                                releasing results, and grading.
                            </p>
                        </div>
                        <ul className="divide-y divide-theme">
                            {exams.length === 0 && (
                                <li className="px-6 py-8 text-center text-sm text-theme-dim">
                                    No exams yet.
                                </li>
                            )}
                            {exams.map((exam) => (
                                <li
                                    key={exam.id}
                                    className="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium text-theme-strong">
                                            {exam.title}
                                        </p>
                                        <p className="text-sm text-theme-dim">
                                            {exam.course_name} ·{' '}
                                            <span className="font-mono">
                                                {exam.exam_code}
                                            </span>
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <span className="text-sm text-theme-dim">
                                            {exam.attempts_count} attempt
                                            {exam.attempts_count === 1 ? '' : 's'}
                                        </span>
                                        <Link
                                            href={route(
                                                'lecturer.exams.stats',
                                                exam.id,
                                            )}
                                            className="inline-flex rounded-md bg-theme-surface-soft px-3 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong hover:bg-gray-700"
                                        >
                                            Results & stats
                                        </Link>
                                        <Link
                                            href={route(
                                                'lecturer.exams.show',
                                                exam.id,
                                            )}
                                            className="text-sm font-medium text-sky-700 hover:text-sky-900"
                                        >
                                            Exam details
                                        </Link>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
