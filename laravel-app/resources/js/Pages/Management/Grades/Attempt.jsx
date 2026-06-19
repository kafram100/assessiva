import AdminLayout from '@/Layouts/AdminLayout';
import QuestionEvaluationBreakdown from '@/Components/Lecturer/QuestionEvaluationBreakdown';
import PenaltySummaryCard from '@/Components/Proctoring/PenaltySummaryCard';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

function qTypeVal(q) {
    if (!q || typeof q.q_type === 'string') {
        return q?.q_type ?? '';
    }
    if (typeof q.q_type === 'object' && q.q_type && 'value' in q.q_type) {
        return q.q_type.value;
    }
    return '';
}

export default function Attempt({
    attempt,
    exam,
    answers,
    questions,
    proctoringSnapshots,
    revisionPlanExists,
    penaltySummary,
    cheatEventsReview,
    answerContributions = {},
}) {
    const answersByQuestion = useMemo(() => {
        const m = {};
        (answers ?? []).forEach((a) => {
            m[a.question_id] = a;
        });
        return m;
    }, [answers]);

    const form = useForm({ marks: {}, comments: {} });

    useEffect(() => {
        const marks = {};
        const comments = {};
        (answers ?? []).forEach((a) => {
            marks[a.id] =
                a.marks_awarded !== null && a.marks_awarded !== undefined
                    ? String(a.marks_awarded)
                    : '';
            comments[a.id] = a.lecturer_grade_comment ?? '';
        });
        form.reset({ marks, comments });
        // eslint-disable-next-line react-hooks/exhaustive-deps -- sync marks when attempt payload changes
    }, [attempt.id, answers]);

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.management.grades.store', attempt.id));
    };

    const postRevision = (name) => {
        router.post(
            route(name, { exam: exam.id, attempt: attempt.id }),
        );
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            Grade attempt
                        </h2>
                        <p className="text-sm text-theme-dim">
                            {attempt.student_fullname || attempt.student_index} ·{' '}
                            {exam.title}
                        </p>
                    </div>
                    <Link
                        href={route('admin.management.exams.stats', exam.id)}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Results table
                    </Link>
                </div>
            }
        >
            <Head title="Grade attempt" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-theme-dim">
                                    Raw score (before penalties)
                                </dt>
                                <dd className="font-semibold text-theme-strong">
                                    {penaltySummary?.raw_score != null
                                        ? Number(penaltySummary.raw_score).toFixed(2)
                                        : Number(
                                              attempt.raw_score ??
                                                  attempt.score ??
                                                  0,
                                          ).toFixed(2)}{' '}
                                    / {attempt.total_marks}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-theme-dim">
                                    Adjusted score (stored)
                                </dt>
                                <dd className="font-semibold text-theme-strong">
                                    {attempt.score} / {attempt.total_marks}
                                </dd>
                            </div>
                            {Number(attempt.proctoring_penalty_total ?? 0) > 0 ? (
                                <div className="sm:col-span-2">
                                    <dt className="text-theme-dim">
                                        Proctoring penalty (reviewed events)
                                    </dt>
                                    <dd className="font-semibold text-amber-700 dark:text-amber-400">
                                        −
                                        {Number(
                                            attempt.proctoring_penalty_total ?? 0,
                                        ).toFixed(2)}
                                    </dd>
                                </div>
                            ) : null}
                            <div className="sm:col-span-2">
                                <dt className="text-theme-dim">Proctoring flags</dt>
                                <dd className="font-semibold text-theme-strong">
                                    Cheat events: {attempt.cheat_events_count ?? 0}{' '}
                                    · Incidents:{' '}
                                    {attempt.cheating_incidents_count ?? 0}
                                </dd>
                            </div>
                        </dl>
                        {exam.results_released ? (
                            <div className="mt-4 flex flex-wrap gap-2 border-t border-theme-line pt-4">
                                <Link
                                    href={route(
                                        'lecturer.exams.attempts.revision-plan.show',
                                        {
                                            exam: exam.id,
                                            attempt: attempt.id,
                                        },
                                    )}
                                    className="inline-flex items-center rounded-md border border-sky-500/40 bg-sky-500/10 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-sky-800 hover:bg-sky-500/15 dark:border-sky-600/50 dark:bg-sky-950/30 dark:text-sky-200 dark:hover:bg-sky-900/40"
                                >
                                    View AI revision plan
                                </Link>
                                <button
                                    type="button"
                                    onClick={() =>
                                        postRevision(
                                            'lecturer.exams.attempts.revision-plan.generate',
                                        )
                                    }
                                    className="inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted hover:bg-theme-highlight/80"
                                >
                                    Generate plan
                                </button>
                                <button
                                    type="button"
                                    onClick={() =>
                                        postRevision(
                                            'lecturer.exams.attempts.revision-plan.regenerate',
                                        )
                                    }
                                    className="inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted hover:bg-theme-highlight/80"
                                >
                                    Regenerate
                                </button>
                                {revisionPlanExists ? (
                                    <span className="self-center text-xs text-emerald-400">
                                        Plan on file
                                    </span>
                                ) : (
                                    <span className="self-center text-xs text-theme-dim">
                                        No plan yet
                                    </span>
                                )}
                            </div>
                        ) : null}
                    </div>

                    <PenaltySummaryCard summary={penaltySummary} />

                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h3 className="text-base font-semibold text-theme-strong">
                                Integrity events for this attempt
                            </h3>
                            <Link
                                href={route('admin.management.exams.proctoring.penalties.index', exam.id)}
                                className="text-xs font-semibold uppercase tracking-wide text-sky-700 hover:text-sky-900 dark:text-sky-400"
                            >
                                Penalty rules
                            </Link>
                        </div>
                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b text-theme-dim">
                                        <th className="py-2 pr-2">When</th>
                                        <th className="py-2 pr-2">Type</th>
                                        <th className="py-2 pr-2">Severity</th>
                                        <th className="py-2 pr-2">Review</th>
                                        <th className="py-2 pr-2">Penalty</th>
                                        <th className="py-2 pr-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(cheatEventsReview ?? []).map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b border-theme-line text-theme-muted"
                                        >
                                            <td className="py-2 pr-2 text-theme-dim">
                                                {row.created_at
                                                    ? new Date(row.created_at).toLocaleString()
                                                    : '-'}
                                            </td>
                                            <td className="py-2 pr-2 font-mono">
                                                {row.event_type}
                                            </td>
                                            <td className="py-2 pr-2">{row.severity}</td>
                                            <td className="py-2 pr-2">
                                                {row.review_status ?? 'pending'}
                                            </td>
                                            <td className="py-2 pr-2 font-mono">
                                                {row.penalty_applied && row.penalty_marks != null
                                                    ? `−${Number(row.penalty_marks).toFixed(2)}`
                                                    : '-'}
                                            </td>
                                            <td className="py-2 pr-2">
                                                <div className="flex flex-wrap gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'lecturer.exams.proctoring.events.apply-penalty',
                                                                    {
                                                                        exam: exam.id,
                                                                        event: row.id,
                                                                    },
                                                                ),
                                                                {},
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                        className="rounded border border-rose-500/40 bg-rose-950/30 px-2 py-1 text-[10px] font-semibold uppercase text-rose-100 hover:bg-rose-900/40"
                                                    >
                                                        Apply
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'lecturer.exams.proctoring.events.ignore',
                                                                    {
                                                                        exam: exam.id,
                                                                        event: row.id,
                                                                    },
                                                                ),
                                                                {},
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                        className="rounded border border-theme-line bg-theme-surface-soft/60 px-2 py-1 text-[10px] font-semibold uppercase text-theme-muted hover:bg-theme-highlight/80"
                                                    >
                                                        Ignore
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'lecturer.exams.proctoring.events.dismiss',
                                                                    {
                                                                        exam: exam.id,
                                                                        event: row.id,
                                                                    },
                                                                ),
                                                                {},
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                        className="rounded border border-emerald-600/40 bg-emerald-950/30 px-2 py-1 text-[10px] font-semibold uppercase text-emerald-100 hover:bg-emerald-900/40"
                                                    >
                                                        Reviewed
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {(cheatEventsReview ?? []).length === 0 ? (
                                <p className="mt-2 text-sm text-theme-dim">
                                    No integrity events recorded.
                                </p>
                            ) : null}
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {(questions ?? []).map((q) => {
                            const ans = answersByQuestion[q.id];
                            const qt = qTypeVal(q);
                            const needsMarks =
                                qt === 'theory' ||
                                qt === 'file' ||
                                qt === 'fill_in';

                            return (
                                <div
                                    key={q.id}
                                    className="rounded-lg border border-theme-line bg-theme-glass p-5 shadow-sm"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <p className="text-sm font-medium text-theme-strong">
                                            {q.question_text}
                                        </p>
                                        <span className="rounded bg-theme-surface-soft px-2 py-0.5 text-xs uppercase text-theme-dim">
                                            {qt} · {q.marks ?? 0} marks
                                        </span>
                                    </div>

                                    {!ans && (
                                        <p className="mt-3 text-sm text-amber-700">
                                            No answer row for this question.
                                        </p>
                                    )}

                                    {ans && (
                                        <div className="mt-3 space-y-2 text-sm text-theme-muted">
                                            {qt === 'mcq' && (
                                                <p>
                                                    Selected:{' '}
                                                    <span className="font-mono">
                                                        {ans.selected_option ||
                                                            '-'}
                                                    </span>{' '}
                                                    · Correct option:{' '}
                                                    <span className="font-mono">
                                                        {q.correct_option ?? '-'}
                                                    </span>{' '}
                                                    ·{' '}
                                                    {ans.is_correct
                                                        ? 'Match'
                                                        : 'No match'}
                                                </p>
                                            )}
                                            {(qt === 'theory' ||
                                                qt === 'fill_in') && (
                                                <p className="whitespace-pre-wrap rounded bg-theme-surface-soft/60 p-3 text-theme-strong">
                                                    {ans.theory_answer ||
                                                        '(empty)'}
                                                </p>
                                            )}
                                            {qt === 'file' && (
                                                <p>
                                                    File:{' '}
                                                    {ans.file_upload ||
                                                        '(none)'}
                                                </p>
                                            )}

                                            {needsMarks && (
                                                <div className="mt-3 space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <label
                                                            htmlFor={`m-${ans.id}`}
                                                            className="text-xs font-medium text-theme-dim"
                                                        >
                                                            Marks awarded (lecturer
                                                            override)
                                                        </label>
                                                        <input
                                                            id={`m-${ans.id}`}
                                                            type="number"
                                                            min={0}
                                                            max={q.marks ?? 99}
                                                            step="0.25"
                                                            value={
                                                                form.data.marks[
                                                                    ans.id
                                                                ] ?? ''
                                                            }
                                                            onChange={(e) =>
                                                                form.setData(
                                                                    'marks',
                                                                    {
                                                                        ...form
                                                                            .data
                                                                            .marks,
                                                                        [ans.id]:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                            className="w-28 rounded-md border-theme-line text-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label
                                                            htmlFor={`c-${ans.id}`}
                                                            className="text-xs font-medium text-theme-dim"
                                                        >
                                                            Lecturer comment
                                                        </label>
                                                        <textarea
                                                            id={`c-${ans.id}`}
                                                            rows={2}
                                                            value={
                                                                form.data
                                                                    .comments[
                                                                    ans.id
                                                                ] ?? ''
                                                            }
                                                            onChange={(e) =>
                                                                form.setData(
                                                                    'comments',
                                                                    {
                                                                        ...form
                                                                            .data
                                                                            .comments,
                                                                        [ans.id]:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                            className="mt-1 block w-full rounded-md border-theme-line bg-theme-glass text-sm text-theme-strong"
                                                        />
                                                    </div>
                                                </div>
                                            )}

                                            <QuestionEvaluationBreakdown
                                                qType={qt}
                                                question={q}
                                                answer={ans}
                                                contribution={
                                                    answerContributions[ans.id]
                                                }
                                            />
                                        </div>
                                    )}
                                </div>
                            );
                        })}

                        <PrimaryButton disabled={form.processing}>
                            Save grades
                        </PrimaryButton>
                    </form>

                    {proctoringSnapshots?.length > 0 && (
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                            <h3 className="text-base font-semibold text-theme-strong">
                                Proctoring captures
                            </h3>
                            <p className="mt-2 text-sm text-theme-dim">
                                Up to{' '}
                                <strong className="text-theme-muted">four</strong>{' '}
                                camera stills (JPEG) are stored for this
                                attempt. They are taken when the student{' '}
                                <strong className="text-theme-muted">
                                    confirms the camera before starting
                                </strong>{' '}
                                (pre-start check) and when the browser reports
                                selected integrity events (for example tab
                                switch, leaving fullscreen after the grace
                                period, or blocked shortcut / paste attempts),
                                each time a frame is grabbed from the webcam
                                stream. Short video clips may also appear here.
                                If images do not load, ensure{' '}
                                <code className="rounded bg-theme-surface-soft px-1 text-xs">
                                    php artisan storage:link
                                </code>{' '}
                                has been run so{' '}
                                <code className="rounded bg-theme-surface-soft px-1 text-xs">
                                    public/storage
                                </code>{' '}
                                points at uploaded files.
                            </p>
                            <ul className="mt-4 grid gap-4 sm:grid-cols-2">
                                {proctoringSnapshots.map((s) => (
                                    <li key={s.url} className="text-sm">
                                        <p className="mb-1 font-medium text-theme-muted">
                                            {s.label}
                                        </p>
                                        {s.is_video ? (
                                            <video
                                                controls
                                                className="w-full rounded border border-theme-line"
                                                src={s.url}
                                            />
                                        ) : (
                                            <img
                                                src={s.url}
                                                alt={s.label}
                                                className="max-h-48 w-full rounded border border-theme-line object-contain"
                                            />
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
