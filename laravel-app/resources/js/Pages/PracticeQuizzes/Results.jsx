import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link } from '@inertiajs/react';

export default function Results({ quiz, attempt, questions }) {
    const ai = attempt.ai_feedback || {};
    const totalMarks = Number(attempt.total_marks ?? 0);
    const score = Number(attempt.score ?? 0);
    const pct =
        typeof attempt.percent === 'number' && !Number.isNaN(attempt.percent)
            ? attempt.percent
            : totalMarks > 0
              ? Math.round((score / totalMarks) * 100)
              : 0;

    return (
        <PracticeHubLayout>
            <Head title={`Results · ${quiz.title}`} />
            <div className="mx-auto max-w-3xl">
                <div className="rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-950/40 to-[#0d1117] p-4 sm:p-8 text-center shadow-2xl shadow-emerald-900/20">
                    <p className="text-sm font-medium uppercase tracking-widest text-emerald-300/90">
                        Practice complete
                    </p>
                    <h1 className="mt-2 text-3xl font-bold text-theme-strong">{quiz.title}</h1>
                    <p className="mt-4 text-5xl font-black text-theme-strong">{pct}%</p>
                    <p className="text-theme-dim">
                        {score} / {totalMarks} marks
                    </p>
                </div>

                {attempt.weak_topics?.length ? (
                    <div className="mt-8 rounded-2xl border border-amber-500/20 bg-amber-950/20 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-amber-200/90">
                            Topics to revisit
                        </h2>
                        <ul className="mt-2 list-inside list-disc text-sm text-amber-50/90">
                            {attempt.weak_topics.map((t) => (
                                <li key={t}>{t}</li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                {ai.summary ? (
                    <div className="mt-8 rounded-2xl border border-violet-500/25 bg-violet-950/20 p-6">
                        <h2 className="text-lg font-semibold text-violet-100">AI coach</h2>
                        <p className="mt-2 text-sm leading-relaxed text-theme-muted">{ai.summary}</p>
                        {ai.motivation_message ? (
                            <p className="mt-4 text-sm font-medium text-sky-700 dark:text-sky-200">{ai.motivation_message}</p>
                        ) : null}
                        {Array.isArray(ai.revision_tips) && ai.revision_tips.length ? (
                            <ul className="mt-4 list-inside list-disc text-sm text-theme-muted">
                                {ai.revision_tips.map((t) => (
                                    <li key={t}>{t}</li>
                                ))}
                            </ul>
                        ) : null}
                        {Array.isArray(ai.recommended_quizzes) && ai.recommended_quizzes.length ? (
                            <div className="mt-4">
                                <p className="text-xs font-semibold uppercase text-theme-dim">Try next</p>
                                <ul className="mt-1 text-sm text-theme-muted">
                                    {ai.recommended_quizzes.map((t) => (
                                        <li key={t}>{t}</li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}
                    </div>
                ) : null}

                {quiz.show_answers_after_submit ? (
                    <div className="mt-10 space-y-6">
                        <h2 className="text-lg font-semibold text-theme-strong">Review</h2>
                        {questions.map((q, i) => (
                            <div
                                key={q.id}
                                className="rounded-xl border border-theme-line bg-theme-highlight p-4 text-sm"
                            >
                                <div className="text-xs text-theme-dim">
                                    Q{i + 1} · {q.q_type}{' '}
                                    {q.marks_awarded != null ? `· ${q.marks_awarded}/${q.marks}` : ''}
                                </div>
                                <p className="mt-2 text-theme-strong">{q.question_text}</p>
                                {q.q_type === 'mcq' && q.correct_option ? (
                                    <p className="mt-2 text-theme-dim">
                                        Correct: <span className="text-emerald-300">{q.correct_option}</span> ·
                                        Yours: {q.your_answer || '-'}
                                    </p>
                                ) : null}
                                {(q.q_type === 'fill_in' || q.q_type === 'theory') && q.model_answer ? (
                                    <p className="mt-2 text-theme-dim">
                                        Model answer: <span className="text-theme-muted">{q.model_answer}</span>
                                    </p>
                                ) : null}
                            </div>
                        ))}
                    </div>
                ) : null}

                <div className="mt-10 flex flex-wrap justify-center gap-3">
                    {quiz.allow_retry ? (
                        <Link
                            href={route('practice-quizzes.show', quiz.id)}
                            className="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-500"
                        >
                            Practice again
                        </Link>
                    ) : null}
                    <Link
                        href={route('practice-quizzes.index')}
                        className="rounded-xl border border-theme-line px-5 py-2.5 text-sm text-theme-muted hover:bg-white/5"
                    >
                        Back to hub
                    </Link>
                </div>
            </div>
        </PracticeHubLayout>
    );
}
