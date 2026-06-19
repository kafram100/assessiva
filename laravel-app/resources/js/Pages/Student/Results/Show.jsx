import PerformanceSummaryCard from '@/Components/Student/PerformanceSummaryCard';
import QuestionFeedbackCard from '@/Components/Student/QuestionFeedbackCard';
import RevisionSuggestionCard from '@/Components/Student/RevisionSuggestionCard';
import GuestLayout from '@/Layouts/GuestLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, usePage } from '@inertiajs/react';

const emptyFeedback = {
    released: false,
    flags: {
        show_ai_feedback: false,
        show_expected_answers: false,
        show_revision_suggestions: false,
    },
    score_summary: null,
    revision_plan_summary: null,
    questions: [],
    aggregated_missing_concepts: [],
    aggregated_detected_concepts: [],
    learning_themes: {
        to_strengthen: [],
        demonstrated: [],
    },
};

export default function Show({
    attempt,
    exam,
    revisionPlanExists,
    feedback: feedbackProp,
}) {
    const feedback = feedbackProp ?? emptyFeedback;
    const released = exam.results_released;
    const { flash } = usePage().props;
    const flashError = flash?.error ?? null;

    const summary = feedback.score_summary;
    const finalForPct =
        summary != null ? Number(summary.final_score) : Number(attempt.score ?? 0);
    const totalForPct =
        summary != null
            ? Number(summary.total_marks)
            : Number(attempt.total_marks ?? 0);

    const scoreVisible =
        released &&
        (summary != null ||
            (attempt.score != null && attempt.total_marks != null));

    const pct =
        scoreVisible && totalForPct > 0
            ? Math.round((finalForPct / totalForPct) * 100)
            : null;

    const showDetail = released && feedback.released;
    const flags = feedback.flags ?? emptyFeedback.flags;
    const revisionSuggestionsEnabled =
        !!exam.show_student_revision_suggestions;

    return (
        <GuestLayout>
            <Head title="Your result" />
            <div className="space-y-6">
                <div>
                    <p className="text-xs uppercase tracking-wide text-theme-dim">
                        Result
                    </p>
                    <h1 className="text-xl font-semibold text-theme-strong">
                        {exam.title}
                    </h1>
                    {exam.course_name ? (
                        <p className="text-sm text-theme-dim">{exam.course_name}</p>
                    ) : null}
                </div>

                {flashError ? (
                    <div className="rounded-lg border border-amber-500/40 bg-amber-950/30 px-4 py-3 text-sm text-amber-100">
                        {flashError}
                    </div>
                ) : null}

                {showDetail && summary ? (
                    <PerformanceSummaryCard
                        scoreSummary={summary}
                        learningThemes={feedback.learning_themes}
                        showAi={flags.show_ai_feedback}
                    />
                ) : null}

                <div className="rounded-xl border border-theme-line bg-theme-glass p-4">
                    <dl className="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-theme-dim">Index</dt>
                            <dd className="font-mono text-theme-strong">
                                {attempt.student_index}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-theme-dim">Name</dt>
                            <dd className="text-theme-strong">
                                {attempt.student_name || '-'}
                            </dd>
                        </div>
                        {scoreVisible ? (
                            <div>
                                <dt className="text-theme-dim">Final score</dt>
                                <dd className="text-lg font-bold text-slate-50">
                                    {finalForPct.toFixed(2)} / {totalForPct}
                                    {pct != null ? (
                                        <span className="ml-2 text-sm font-normal text-theme-dim">
                                            ({pct}%)
                                        </span>
                                    ) : null}
                                </dd>
                            </div>
                        ) : null}
                        <div>
                            <dt className="text-theme-dim">Status</dt>
                            <dd className="text-theme-strong">{attempt.status}</dd>
                        </div>
                        <div className="sm:col-span-2">
                            <dt className="text-theme-dim">Results</dt>
                            <dd>
                                {released ? (
                                    <span className="inline-flex rounded-md bg-emerald-500/15 px-2 py-1 text-xs font-semibold text-emerald-800 dark:text-emerald-200">
                                        Released
                                    </span>
                                ) : (
                                    <span className="inline-flex rounded-md bg-amber-500/15 px-2 py-1 text-xs font-semibold text-amber-100">
                                        Not released yet
                                    </span>
                                )}
                            </dd>
                        </div>
                    </dl>
                </div>

                {!released && attempt.status === 'completed' ? (
                    <div className="rounded-xl border border-emerald-500/30 bg-emerald-950/25 px-4 py-4 text-center text-sm text-emerald-50">
                        Your exam has been submitted successfully. Results will be
                        available when released by your lecturer.
                    </div>
                ) : null}

                {showDetail && feedback.questions && feedback.questions.length > 0 ? (
                    <div className="space-y-3">
                        <h2 className="text-sm font-semibold uppercase tracking-widest text-theme-dim">
                            Question-by-question feedback
                        </h2>
                        {feedback.questions.map((item) => (
                            <QuestionFeedbackCard
                                key={item.question_id}
                                item={item}
                                flags={flags}
                            />
                        ))}
                    </div>
                ) : null}

                {showDetail && revisionSuggestionsEnabled ? (
                    <RevisionSuggestionCard
                        revisionPlanSummary={feedback.revision_plan_summary}
                        aiEnabled={!!flags.show_ai_feedback}
                        revisionEnabled={revisionSuggestionsEnabled}
                    />
                ) : null}

                {released ? (
                    <>
                        {revisionSuggestionsEnabled ? (
                            <Link
                                href={route(
                                    'student.results.revision-plan',
                                    attempt.id,
                                )}
                                className="block"
                            >
                                <PrimaryButton
                                    type="button"
                                    className="w-full justify-center"
                                >
                                    View AI revision plan
                                </PrimaryButton>
                            </Link>
                        ) : null}
                        <div className="grid gap-2 sm:grid-cols-3">
                            <Link
                                href={route(
                                    'student.intelligence.learning-twin',
                                    attempt.id,
                                )}
                                className="rounded-lg border border-theme-line bg-theme-glass py-3 text-center text-xs font-semibold text-sky-700 hover:border-sky-500/50 dark:text-sky-200"
                            >
                                My learning twin
                            </Link>
                            <Link
                                href={route(
                                    'student.intelligence.academic-health',
                                    attempt.id,
                                )}
                                className="rounded-lg border border-theme-line bg-theme-glass py-3 text-center text-xs font-semibold text-sky-700 hover:border-sky-500/50 dark:text-sky-200"
                            >
                                Academic health
                            </Link>
                            <Link
                                href={route(
                                    'student.intelligence.recommendations',
                                    attempt.id,
                                )}
                                className="rounded-lg border border-theme-line bg-theme-glass py-3 text-center text-xs font-semibold text-sky-700 hover:border-sky-500/50 dark:text-sky-200"
                            >
                                Recommendations
                            </Link>
                        </div>
                    </>
                ) : (
                    <p className="text-center text-sm text-theme-dim">
                        When your lecturer releases results, you will see your score
                        here, detailed feedback, and learning tools where enabled.
                    </p>
                )}

                {released && revisionSuggestionsEnabled && revisionPlanExists ? (
                    <p className="text-center text-xs text-theme-dim">
                        Your plan is saved - refreshing will not regenerate it.
                    </p>
                ) : null}

                <p className="text-center text-xs text-theme-dim">
                    <a
                        href={route('student.entry')}
                        className="font-medium text-sky-400 underline-offset-2 hover:text-sky-300 hover:underline"
                    >
                        Back to exam entry
                    </a>
                    <span className="mx-2 text-theme-dim">·</span>
                    <Link
                        href={route('student.results.lookup-form')}
                        className="text-sky-400 hover:text-sky-300"
                    >
                        Look up a different exam
                    </Link>
                </p>
            </div>
        </GuestLayout>
    );
}
