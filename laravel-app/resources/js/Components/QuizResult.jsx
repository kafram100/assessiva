import React from 'react';

export default function QuizResult({ practiceSession, questions, onReset }) {
    const { score, responses, ai_feedback } = practiceSession;

    return (
        <div className="mx-auto w-full py-8">
            <div className="mb-10 text-center">
                <h1 className="text-3xl font-bold text-theme-strong">Practice Complete!</h1>
                <div className="mt-4 inline-flex items-center justify-center rounded-full bg-theme-elevated px-4 sm:px-8 py-4 shadow-lg ring-1 ring-theme-line">
                    <div className="text-5xl font-extrabold text-sky-500">
                        {score}%
                    </div>
                    <div className="ml-4 text-left text-sm font-semibold uppercase tracking-wider text-theme-dim">
                        Total<br />Score
                    </div>
                </div>
            </div>

            <div className="mb-12">
                <h2 className="mb-4 text-xl font-bold text-theme-strong">NovaGrade Feedback & Analysis</h2>
                <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 text-theme-dim">
                    Your answers have been graded. Review the detailed NovaGrade feedback for each question below.
                </div>
            </div>

            <div className="space-y-6">
                <h2 className="text-xl font-bold text-theme-strong">Question Review</h2>
                {questions.map((q, i) => {
                    const response = responses[q.id];
                    const isCorrect = response?.is_correct;

                    return (
                        <div key={q.id} className={`rounded-2xl border p-6 ${isCorrect ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-rose-500/30 bg-rose-500/5'}`}>
                            <div className="mb-4 flex items-center justify-between">
                                <div className="font-semibold text-theme-dim">Question {i + 1}</div>
                                <div className={`font-bold ${isCorrect ? 'text-emerald-500' : 'text-rose-500'}`}>
                                    {isCorrect ? 'Correct' : 'Incorrect'}
                                </div>
                            </div>
                            <div
                                className="prose prose-sm prose-invert mb-6 max-w-none text-theme-strong"
                                dangerouslySetInnerHTML={{ __html: q.question_text }}
                            />
                            
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="rounded-xl bg-theme-surface p-4">
                                    <div className="mb-1 text-xs font-semibold uppercase text-theme-dim">Your Answer</div>
                                    <div className="font-medium text-theme-strong">{response?.user_answer || <span className="text-theme-muted italic">No answer provided</span>}</div>
                                </div>
                                <div className="rounded-xl bg-theme-surface p-4">
                                    <div className="mb-1 text-xs font-semibold uppercase text-theme-dim">Correct Answer</div>
                                    <div className="font-medium text-theme-strong">{response?.correct_answer}</div>
                                </div>
                            </div>
                            {response?.ai_feedback && (
                                <div className="mt-4 rounded-xl border border-violet-500/30 bg-violet-500/5 p-4 text-sm text-theme-strong">
                                    <div className="font-semibold text-violet-600 dark:text-violet-400 mb-1">NovaGrade Feedback</div>
                                    {response.ai_feedback}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {onReset && (
                <div className="mt-12 text-center">
                    <button
                        onClick={onReset}
                        className="inline-block rounded-xl bg-theme-surface px-4 sm:px-8 py-3 font-semibold text-theme-strong transition hover:bg-theme-line"
                    >
                        Generate New Practice
                    </button>
                </div>
            )}
        </div>
    );
}
