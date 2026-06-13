import Flashcard from '@/Components/RevisionPlan/Flashcard';
import PracticeQuestionCard from '@/Components/RevisionPlan/PracticeQuestionCard';
import RevisionNoteCard from '@/Components/RevisionPlan/RevisionNoteCard';
import RiskLevelBadge from '@/Components/RevisionPlan/RiskLevelBadge';
import StudyPlanTimeline from '@/Components/RevisionPlan/StudyPlanTimeline';
import WeakTopicBadge from '@/Components/RevisionPlan/WeakTopicBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ exam, attempt, revisionPlan }) {
    const generate = () => {
        if (
            !confirm(
                'Generate an AI revision plan for this student? This calls the AI service once.',
            )
        ) {
            return;
        }
        router.post(
            route('lecturer.exams.attempts.revision-plan.generate', {
                exam: exam.id,
                attempt: attempt.id,
            }),
        );
    };

    const regenerate = () => {
        if (
            !confirm(
                'Regenerate the revision plan? The previous AI output will be replaced.',
            )
        ) {
            return;
        }
        router.post(
            route('lecturer.exams.attempts.revision-plan.regenerate', {
                exam: exam.id,
                attempt: attempt.id,
            }),
        );
    };

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            AI revision plan
                        </h2>
                        <p className="text-sm text-theme-dim">
                            {attempt.student_name || attempt.student_index} ·{' '}
                            {exam.title}
                        </p>
                    </div>
                    <Link
                        href={route('lecturer.grades.attempt', attempt.id)}
                        className="text-sm font-medium text-sky-400 hover:text-sky-300"
                    >
                        ← Grade attempt
                    </Link>
                </div>
            }
        >
            <Head title="Revision plan" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-2">
                        <PrimaryButton type="button" onClick={generate}>
                            Generate plan
                        </PrimaryButton>
                        <button
                            type="button"
                            onClick={regenerate}
                            className="inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted hover:bg-theme-highlight/80"
                        >
                            Regenerate
                        </button>
                    </div>

                    <div className="rounded-lg border border-theme-line bg-theme-glass p-4 text-sm text-theme-muted">
                        <p>
                            Score:{' '}
                            <span className="font-semibold text-theme-strong">
                                {attempt.score} / {attempt.total_marks}
                            </span>
                        </p>
                        {revisionPlan?.ai_provider ? (
                            <p className="mt-1 text-xs text-theme-dim">
                                Provider tag: {revisionPlan.ai_provider}
                            </p>
                        ) : null}
                    </div>

                    {!revisionPlan ? (
                        <p className="text-sm text-theme-dim">
                            No revision plan stored yet. Generate one when results
                            are released and the attempt is completed.
                        </p>
                    ) : (
                        <>
                            <div className="flex flex-wrap items-center gap-3">
                                {revisionPlan.risk_level ? (
                                    <RiskLevelBadge
                                        level={revisionPlan.risk_level}
                                    />
                                ) : null}
                            </div>

                            <section>
                                <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                    Weak topics
                                </h3>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {(revisionPlan.weak_topics || []).map((t) => (
                                        <WeakTopicBadge key={t} topic={t} />
                                    ))}
                                </div>
                            </section>

                            {revisionPlan.summary ? (
                                <section className="rounded-xl border border-theme-line bg-theme-glass p-5">
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Summary
                                    </h3>
                                    <p className="mt-2 text-sm text-theme-muted">
                                        {revisionPlan.summary}
                                    </p>
                                </section>
                            ) : null}

                            {(revisionPlan.revision_notes || []).length ? (
                                <section>
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Notes
                                    </h3>
                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        {(
                                            revisionPlan.revision_notes || []
                                        ).map((n, i) => (
                                            <RevisionNoteCard
                                                key={i}
                                                topic={n.topic}
                                                note={n.note}
                                            />
                                        ))}
                                    </div>
                                </section>
                            ) : null}

                            {(revisionPlan.flashcards || []).length ? (
                                <section>
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Flashcards
                                    </h3>
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
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Practice
                                    </h3>
                                    <div className="mt-3 space-y-3">
                                        {(
                                            revisionPlan.practice_questions ||
                                            []
                                        ).map((q, i) => (
                                            <PracticeQuestionCard
                                                key={i}
                                                {...q}
                                            />
                                        ))}
                                    </div>
                                </section>
                            ) : null}

                            {(revisionPlan.study_plan || []).length ? (
                                <section>
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Study plan
                                    </h3>
                                    <div className="mt-3">
                                        <StudyPlanTimeline
                                            days={revisionPlan.study_plan}
                                        />
                                    </div>
                                </section>
                            ) : null}

                            {(revisionPlan.recommended_actions || []).length ? (
                                <section>
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Recommended actions
                                    </h3>
                                    <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-theme-muted">
                                        {(
                                            revisionPlan.recommended_actions ||
                                            []
                                        ).map((a, i) => (
                                            <li key={i}>{a}</li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}

                            {(revisionPlan.failed_questions || []).length ? (
                                <section>
                                    <h3 className="text-sm font-semibold uppercase text-theme-dim">
                                        Failed / partial items (payload)
                                    </h3>
                                    <ul className="mt-2 space-y-2 text-xs text-theme-dim">
                                        {(
                                            revisionPlan.failed_questions ||
                                            []
                                        ).map((f, i) => (
                                            <li
                                                key={i}
                                                className="rounded border border-theme-line p-2"
                                            >
                                                <span className="font-medium text-theme-muted">
                                                    {f.topic}
                                                </span>
                                                : {f.question}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}
                        </>
                    )}
                </div>
            </div>
        </LecturerLayout>
    );
}
