import AdminPagination from '@/Components/AdminPagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function fmt(dt) {
    if (!dt) {
        return '-';
    }
    return new Date(dt).toLocaleString();
}

function scoreCell(score, total) {
    if (score === null || score === undefined || total === null || total === undefined) {
        return '-';
    }
    return `${score}/${total}`;
}

export default function Quizzes({ attempts, filters = {} }) {
    const [q, setQ] = useState(filters.q ?? '');

    useEffect(() => {
        setQ(filters.q ?? '');
    }, [filters.q]);

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.quizzes'),
            { q: q.trim() || undefined },
            { preserveState: true, replace: true },
        );
    };

    const clearSearch = () => {
        setQ('');
        router.get(route('admin.quizzes'), {}, { replace: true });
    };

    const hasFilter = Boolean((filters.q ?? '').trim());
    const rows = attempts.data ?? [];

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    All quizzes taken
                </h2>
            }
        >
            <Head title="All quizzes" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.dashboard')}
                    className="inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to admin
                </Link>

                <form
                    onSubmit={applySearch}
                    className="flex flex-wrap items-end gap-3 rounded-lg border border-theme-line bg-theme-glass p-4"
                >
                    <div className="min-w-[200px] flex-1">
                        <label
                            htmlFor="quiz-search"
                            className="block text-xs font-medium uppercase tracking-wide text-theme-dim"
                        >
                            Search
                        </label>
                        <input
                            id="quiz-search"
                            type="search"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Student index, name, email, or exam / course…"
                            className="mt-1 block w-full rounded-md border border-theme-line bg-theme-mesh px-3 py-2 text-sm text-theme-strong placeholder:text-theme-dim"
                        />
                    </div>
                    <button
                        type="submit"
                        className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                    >
                        Search
                    </button>
                    {hasFilter ? (
                        <button
                            type="button"
                            onClick={clearSearch}
                            className="rounded-md border border-theme-line px-4 py-2 text-sm font-medium text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Clear
                        </button>
                    ) : null}
                </form>

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Submitted</th>
                                    <th className="px-4 py-3">Student</th>
                                    <th className="px-4 py-3">Index</th>
                                    <th className="px-4 py-3">Exam</th>
                                    <th className="px-4 py-3">Course</th>
                                    <th className="px-4 py-3">Lecturer</th>
                                    <th className="px-4 py-3">Dept / school</th>
                                    <th className="px-4 py-3 text-end">Score</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            {hasFilter
                                                ? 'No completed attempts match your search.'
                                                : 'No completed attempts yet.'}
                                        </td>
                                    </tr>
                                )}
                                {rows.map((a) => {
                                    const ex = a.exam;
                                    const lec = ex?.user;
                                    return (
                                        <tr
                                            key={a.id}
                                            className="hover:bg-theme-elevated"
                                        >
                                            <td className="whitespace-nowrap px-4 py-3 text-xs text-theme-dim">
                                                {fmt(a.submit_time)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-theme-strong">
                                                    {a.student_fullname ?? '-'}
                                                </div>
                                                {a.student_email ? (
                                                    <div className="mt-0.5 max-w-[200px] truncate text-xs text-theme-dim">
                                                        {a.student_email}
                                                    </div>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {a.student_index}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-theme-strong">
                                                {ex?.id ? (
                                                    <Link
                                                        href={route(
                                                            'admin.exams.show',
                                                            ex.id,
                                                        )}
                                                        className="text-sky-400 hover:text-sky-300 hover:underline"
                                                    >
                                                        {ex.title ?? '-'}
                                                    </Link>
                                                ) : (
                                                    (ex?.title ?? '-')
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {ex?.course_code || ex?.course_name ? (
                                                    <span className="text-theme-muted">
                                                        {ex.course_code ? (
                                                            <span className="font-mono text-theme-dim">
                                                                {ex.course_code}
                                                            </span>
                                                        ) : null}
                                                        {ex.course_code && ex.course_name ? (
                                                            <span className="text-slate-600">
                                                                {' '}
                                                                ·{' '}
                                                            </span>
                                                        ) : null}
                                                        {ex.course_name ?? ''}
                                                    </span>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {lec?.username ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {lec?.department ? (
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
                                            <td className="px-4 py-3 text-end text-xs tabular-nums">
                                                {scoreCell(a.score, a.total_marks)}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination links={attempts.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
