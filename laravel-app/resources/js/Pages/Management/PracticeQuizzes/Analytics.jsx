import ExportExcelButton from '@/Components/ExportExcelButton';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Analytics({ quiz, stats, weakTopics, questionFailures, trend }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Practice analytics
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <ExportExcelButton
                            href={route(
                                'lecturer.practice-quizzes.analytics.export',
                                quiz.id,
                            )}
                        />
                        <Link
                            href={route(
                                'lecturer.practice-quizzes.edit',
                                quiz.id,
                            )}
                            className="text-sm text-theme-dim underline hover:text-theme-strong"
                        >
                            ← Edit quiz
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Analytics · ${quiz.title}`} />
            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                    <p className="text-theme-dim">{quiz.course_name}</p>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border border-theme-line bg-theme-glass p-5">
                            <div className="text-xs uppercase text-theme-dim">Attempts</div>
                            <div className="mt-1 text-3xl font-bold text-theme-strong">{stats.attempts}</div>
                        </div>
                        <div className="rounded-xl border border-theme-line bg-theme-glass p-5">
                            <div className="text-xs uppercase text-theme-dim">Avg score</div>
                            <div className="mt-1 text-3xl font-bold text-emerald-300">{stats.average_percent}%</div>
                        </div>
                        <div className="rounded-xl border border-theme-line bg-theme-glass p-5">
                            <div className="text-xs uppercase text-theme-dim">Hub status</div>
                            <div className="mt-1 text-sm text-theme-muted">
                                {quiz.is_public_practice && quiz.is_published ? 'Public' : 'Private / draft'}
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-8 lg:grid-cols-2">
                        <div className="rounded-xl border border-theme-line bg-theme-glass p-5">
                            <h3 className="text-lg font-semibold text-theme-strong">Weak topics (practice)</h3>
                            <ul className="mt-3 space-y-2 text-sm text-theme-muted">
                                {weakTopics.length ? (
                                    weakTopics.map((w) => (
                                        <li key={w.topic} className="flex justify-between border-b border-theme-line py-1">
                                            <span>{w.topic}</span>
                                            <span className="text-theme-dim">{w.count}×</span>
                                        </li>
                                    ))
                                ) : (
                                    <li className="text-theme-dim">No weak-topic data yet.</li>
                                )}
                            </ul>
                        </div>
                        <div className="rounded-xl border border-theme-line bg-theme-glass p-5">
                            <h3 className="text-lg font-semibold text-theme-strong">Hardest items</h3>
                            <ul className="mt-3 space-y-2 text-sm text-theme-muted">
                                {questionFailures.length ? (
                                    questionFailures.map((q) => (
                                        <li key={q.question_id} className="border-b border-theme-line py-1">
                                            <span className="text-amber-200/90">{q.misses} misses</span> -{' '}
                                            {q.question_preview}
                                        </li>
                                    ))
                                ) : (
                                    <li className="text-theme-dim">No per-question stats yet.</li>
                                )}
                            </ul>
                        </div>
                    </div>

                    <div className="rounded-xl border border-theme-line bg-theme-glass p-5">
                        <h3 className="text-lg font-semibold text-theme-strong">Engagement (submits per day)</h3>
                        <div className="mt-4 flex max-h-48 flex-wrap gap-2 overflow-y-auto text-xs">
                            {trend.length ? (
                                trend.map((row) => (
                                    <span
                                        key={row.d}
                                        className="rounded bg-theme-surface-soft px-2 py-1 text-theme-muted"
                                        title={row.d}
                                    >
                                        {row.d}: <strong className="text-theme-strong">{row.c}</strong>
                                    </span>
                                ))
                            ) : (
                                <span className="text-theme-dim">No trend data.</span>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
