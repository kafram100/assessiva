import Flashcard from '@/Components/RevisionPlan/Flashcard';
import PracticeQuestionCard from '@/Components/RevisionPlan/PracticeQuestionCard';
import RevisionNoteCard from '@/Components/RevisionPlan/RevisionNoteCard';
import RiskLevelBadge from '@/Components/RevisionPlan/RiskLevelBadge';
import StudyPlanTimeline from '@/Components/RevisionPlan/StudyPlanTimeline';
import WeakTopicBadge from '@/Components/RevisionPlan/WeakTopicBadge';
import GuestLayout from '@/Layouts/GuestLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link } from '@inertiajs/react';

export default function Show({
    attempt,
    exam,
    revisionPlan,
    generationPending,
    message,
}) {
    const pending = generationPending || !revisionPlan;

    return (
        <GuestLayout>
            <Head title="AI revision plan" />
            <div className="space-y-8">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-theme-dim">
                            Revision plan
                        </p>
                        <h1 className="text-xl font-semibold text-theme-strong">
                            {exam.title}
                        </h1>
                        {exam.course_name ? (
                            <p className="text-sm text-theme-dim">
                                {exam.course_name}
                            </p>
                        ) : null}
                    </div>
                    <Link href={route('student.results.show', attempt.id)}>
                        <PrimaryButton type="button" className="bg-theme-highlight hover:bg-slate-600">
                            Back to result
                        </PrimaryButton>
                    </Link>
                </div>

                <div className="flex flex-wrap items-center gap-3 rounded-xl border border-theme-line bg-theme-glass p-4">
                    <div>
                        <p className="text-xs uppercase text-theme-dim">Score</p>
                        <p className="text-lg font-bold text-slate-50">
                            {attempt.score} / {attempt.total_marks}
                        </p>
                    </div>
                    {!pending && revisionPlan?.risk_level ? (
                        <RiskLevelBadge level={revisionPlan.risk_level} />
                    ) : null}
                </div>

                {pending ? (
                    <div className="rounded-xl border border-amber-500/30 bg-amber-950/20 p-6 text-center">
                        <p className="text-sm font-medium text-amber-100">
                            {message ||
                                'Revision plan is being prepared. Please check again later.'}
                        </p>
                    </div>
                ) : (
                    <>
                        <section>
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                Weak topics
                            </h2>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {(revisionPlan.weak_topics || []).map((t) => (
                                    <WeakTopicBadge key={t} topic={t} />
                                ))}
                                {!(revisionPlan.weak_topics || []).length ? (
                                    <p className="text-sm text-theme-dim">
                                        No weak topics flagged — keep practicing
                                        broadly.
                                    </p>
                                ) : null}
                            </div>
                        </section>

                        {revisionPlan.summary ? (
                            <section className="rounded-xl border border-theme-line bg-gradient-to-br from-theme-glass to-theme-surface-soft p-5 dark:from-slate-900/60 dark:to-slate-800/20">
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Coach summary
                                </h2>
                                <p className="mt-2 text-sm leading-relaxed text-theme-muted">
                                    {revisionPlan.summary}
                                </p>
                            </section>
                        ) : null}

                        {(revisionPlan.revision_notes || []).length ? (
                            <section>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Revision notes
                                </h2>
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    {(revisionPlan.revision_notes || []).map(
                                        (n, i) => (
                                            <RevisionNoteCard
                                                key={i}
                                                topic={n.topic}
                                                note={n.note}
                                            />
                                        ),
                                    )}
                                </div>
                            </section>
                        ) : null}

                        {(revisionPlan.flashcards || []).length ? (
                            <section>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Flashcards
                                </h2>
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    {(revisionPlan.flashcards || []).map(
                                        (c, i) => (
                                            <Flashcard
                                                key={i}
                                                front={c.front}
                                                back={c.back}
                                            />
                                        ),
                                    )}
                                </div>
                            </section>
                        ) : null}

                        {(revisionPlan.practice_questions || []).length ? (
                            <section>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Practice questions
                                </h2>
                                <div className="mt-3 space-y-3">
                                    {(
                                        revisionPlan.practice_questions || []
                                    ).map((q, i) => (
                                        <PracticeQuestionCard key={i} {...q} />
                                    ))}
                                </div>
                            </section>
                        ) : null}

                        {(revisionPlan.study_plan || []).length ? (
                            <section>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Study plan
                                </h2>
                                <div className="mt-3">
                                    <StudyPlanTimeline
                                        days={revisionPlan.study_plan}
                                    />
                                </div>
                            </section>
                        ) : null}

                        {(revisionPlan.recommended_actions || []).length ? (
                            <section>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Recommended next steps
                                </h2>
                                <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-theme-muted">
                                    {(
                                        revisionPlan.recommended_actions || []
                                    ).map((a, i) => (
                                        <li key={i}>{a}</li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}

                        {(revisionPlan.failed_questions || []).length ? (
                            <section>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-theme-dim">
                                    Areas to revisit (from your attempt)
                                </h2>
                                <ul className="mt-3 space-y-2 text-sm text-theme-dim">
                                    {(revisionPlan.failed_questions || []).map(
                                        (f, i) => (
                                            <li
                                                key={i}
                                                className="rounded-lg border border-theme-line bg-theme-glass p-3"
                                            >
                                                <span className="font-medium text-theme-muted">
                                                    {f.topic}
                                                </span>
                                                : {f.question}
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </section>
                        ) : null}

                        {revisionPlan.generated_at ? (
                            <p className="text-center text-xs text-slate-600">
                                Generated {revisionPlan.generated_at}
                            </p>
                        ) : null}
                    </>
                )}
            </div>
        </GuestLayout>
    );
}
