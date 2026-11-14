import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link } from '@inertiajs/react';

export default function Result({ practiceSession, questions }) {
    const { score, responses, ai_feedback } = practiceSession;

    const formatAnswer = (q, ans, ansText) => {
        if (ansText && String(ansText).trim() !== '') return ansText;
        if (ans && String(ans).trim() !== '') {
            if (q.question_type === 'mcq') {
                const letter = String(ans).trim().toUpperCase();
                const displayedText = q[`option_${letter.toLowerCase()}`];
                if (displayedText) return `Option ${letter}: ${displayedText}`;
                const opt = q.options?.find(o => o.option_key?.toUpperCase() === letter);
                if (opt) return `Option ${letter}: ${opt.option_text}`;
            }
            return ans;
        }
        if (q.question_type !== 'mcq') {
            return q.correct_option || q.model_answer || q.marking_scheme || q.correct_answer || null;
        }
        return null;
    };

    return (
        <PracticeHubLayout>
            <Head title="Practice Results" />
            
            <div className="mx-auto max-w-5xl py-8">
                <div className="mb-10 text-center">
                    <h1 className="text-3xl font-bold text-theme-strong">Practice Complete!</h1>
                    <div className="mt-4 inline-flex items-center justify-center rounded-full bg-theme-elevated px-4 sm:px-8 py-4 shadow-lg ring-1 ring-theme-line">
                        <div className="text-5xl font-extrabold text-indigo-600 dark:text-indigo-400">
                            {score}%
                        </div>
                        <div className="ml-4 text-left text-sm font-semibold uppercase tracking-wider text-theme-dim">
                            Total<br />Score
                        </div>
                    </div>
                </div>

                <div className="mb-12">
                    <h2 className="mb-4 text-xl font-bold text-theme-strong">Assessiva Feedback & Analysis</h2>
                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 text-theme-dim">
                        Your answers have been graded. Review the detailed Assessiva feedback for each question below.
                    </div>
                </div>

                <div className="space-y-6">
                    <h2 className="text-xl font-bold text-theme-strong">Question Review</h2>
                    {questions.map((q, i) => {
                        const response = responses?.[q.id] || {};
                        const isCorrect = Boolean(response?.is_correct);
                        const marks = typeof response?.marks === 'number' ? response.marks : (isCorrect ? 1 : 0);
                        const isPartial = !isCorrect && marks > 0;

                        const badgeText = isPartial
                            ? `Partially Correct (${marks} / 1 mark)`
                            : (isCorrect ? (marks > 0 && marks < 1 ? `Correct (${marks} / 1 mark)` : 'Correct') : 'Incorrect');

                        const badgeColor = isPartial
                            ? 'text-amber-500'
                            : (isCorrect ? 'text-emerald-500' : 'text-rose-500');

                        const cardBorder = isPartial
                            ? 'border-amber-500/30 bg-amber-500/5'
                            : (isCorrect ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-rose-500/30 bg-rose-500/5');

                        const correctAnswerDisplay = formatAnswer(q, response?.correct_answer, response?.correct_answer_text)
                            || q.correct_option
                            || q.model_answer
                            || q.marking_scheme
                            || q.correct_answer
                            || <span className="text-theme-muted italic">See model answer above</span>;

                        return (
                            <div key={q.id} className={`rounded-2xl border p-6 ${cardBorder}`}>
                                <div className="mb-4 flex items-center justify-between">
                                    <div className="font-semibold text-theme-dim">Question {i + 1}</div>
                                    <div className={`font-bold ${badgeColor}`}>
                                        {badgeText}
                                    </div>
                                </div>
                                <div
                                    className="prose prose-sm prose-invert mb-6 max-w-none text-theme-strong"
                                    dangerouslySetInnerHTML={{ __html: q.question_text }}
                                />
                                
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="rounded-xl bg-theme-surface p-4">
                                        <div className="mb-1 text-xs font-semibold uppercase text-theme-dim">Your Answer</div>
                                        <div className="font-medium text-theme-strong whitespace-pre-wrap">{formatAnswer(q, response?.user_answer, response?.user_answer_text) || <span className="text-theme-muted italic">No answer provided</span>}</div>
                                    </div>
                                    <div className="rounded-xl bg-theme-surface p-4">
                                        <div className="mb-1 text-xs font-semibold uppercase text-theme-dim">Correct Answer</div>
                                        <div className="font-medium text-theme-strong whitespace-pre-wrap">{correctAnswerDisplay}</div>
                                    </div>
                                </div>
                                {response?.ai_feedback && (
                                    <div className="mt-4 rounded-xl border border-indigo-500/30 bg-indigo-500/5 p-4 text-sm text-theme-strong">
                                        <div className="font-semibold text-indigo-600 dark:text-indigo-400 mb-1">Assessiva Feedback</div>
                                        {response.ai_feedback}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                <div className="mt-12 text-center">
                    <Link
                        href={route('practice-quizzes.index')}
                        className="inline-block rounded-xl bg-theme-surface px-4 sm:px-8 py-3 font-semibold text-theme-strong transition hover:bg-theme-line"
                    >
                        Back to Practice Hub
                    </Link>
                </div>
            </div>
        </PracticeHubLayout>
    );
}
