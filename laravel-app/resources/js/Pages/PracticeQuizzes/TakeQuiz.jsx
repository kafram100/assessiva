import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function OptionLetter({ label, name, value, checked, onChange, disabled }) {
    return (
        <label
            className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm ${
                checked ? 'border-sky-500 bg-sky-500/10' : 'border-theme-line bg-theme-highlight'
            } ${disabled ? 'opacity-50' : ''}`}
        >
            <input
                type="radio"
                name={name}
                value={value}
                checked={checked}
                disabled={disabled}
                onChange={() => onChange(value)}
                className="text-sky-500"
            />
            <span className="font-medium text-theme-muted">{label}</span>
        </label>
    );
}

export default function TakeQuiz({ quiz, questions, startedAtIso }) {
    const form = useForm({
        answers: {},
        fill_in_answers: {},
        theory_answers: {},
    });

    const [tick, setTick] = useState(0);
    const minutes = quiz.practice_duration_minutes;

    useEffect(() => {
        if (!minutes) return undefined;
        const id = setInterval(() => setTick((t) => t + 1), 1000);
        return () => clearInterval(id);
    }, [minutes]);

    const elapsedLabel = useMemo(() => {
        if (!startedAtIso) return null;
        const start = new Date(startedAtIso).getTime();
        const sec = Math.max(0, Math.floor((Date.now() - start) / 1000));
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m}:${s.toString().padStart(2, '0')}`;
    }, [startedAtIso, tick]);

    const submit = () => {
        form.post(route('practice-quizzes.submit', quiz.id));
    };

    return (
        <PracticeHubLayout>
            <Head title={`Taking: ${quiz.title}`} />
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-theme-strong">{quiz.title}</h1>
                    <p className="text-sm text-theme-dim">{quiz.course_name}</p>
                </div>
                <div className="flex items-center gap-4 text-sm">
                    {elapsedLabel ? (
                        <span className="rounded-full bg-white/10 px-3 py-1 text-theme-muted">
                            Elapsed {elapsedLabel}
                            {minutes ? (
                                <span className="text-theme-dim"> · suggested {minutes} min</span>
                            ) : null}
                        </span>
                    ) : null}
                </div>
            </div>

            <div className="space-y-8">
                {questions.map((q, i) => (
                    <div
                        key={q.id}
                        className="rounded-2xl border border-theme-line bg-theme-highlight p-5 shadow-inner"
                    >
                        <div className="text-xs font-semibold uppercase tracking-wider text-theme-dim">
                            Question {i + 1} · {q.marks} marks · {q.q_type}
                        </div>
                        <div className="mt-2 text-base text-theme-strong">{q.question_text}</div>

                        {q.q_type === 'mcq' ? (
                            <div className="mt-4 grid gap-2 sm:grid-cols-2">
                                {['A', 'B', 'C', 'D', 'E'].map((L) => {
                                    const opt = q[`option_${L.toLowerCase()}`];
                                    if (!opt) return null;
                                    const name = `mcq-${q.id}`;
                                    const val = form.data.answers[q.id] ?? '';
                                    return (
                                        <OptionLetter
                                            key={L}
                                            label={`${L}. ${opt}`}
                                            name={name}
                                            value={L}
                                            checked={val === L}
                                            disabled={form.processing}
                                            onChange={(v) =>
                                                form.setData('answers', { ...form.data.answers, [q.id]: v })
                                            }
                                        />
                                    );
                                })}
                            </div>
                        ) : null}

                        {q.q_type === 'fill_in' ? (
                            <input
                                className="mt-4 w-full rounded-lg border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                placeholder="Your answer"
                                value={form.data.fill_in_answers[q.id] ?? ''}
                                disabled={form.processing}
                                onChange={(e) =>
                                    form.setData('fill_in_answers', {
                                        ...form.data.fill_in_answers,
                                        [q.id]: e.target.value,
                                    })
                                }
                            />
                        ) : null}

                        {q.q_type === 'theory' ? (
                            <textarea
                                rows={5}
                                className="mt-4 w-full rounded-lg border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                placeholder="Your answer"
                                value={form.data.theory_answers[q.id] ?? ''}
                                disabled={form.processing}
                                onChange={(e) =>
                                    form.setData('theory_answers', {
                                        ...form.data.theory_answers,
                                        [q.id]: e.target.value,
                                    })
                                }
                            />
                        ) : null}

                        {q.q_type === 'file' ? (
                            <p className="mt-4 text-sm text-amber-200/90">
                                File uploads are not supported in the public practice hub for this build. Skip or
                                answer other items.
                            </p>
                        ) : null}
                    </div>
                ))}
            </div>

            {form.errors.quiz ? (
                <p className="mt-4 text-sm text-red-400">{form.errors.quiz}</p>
            ) : null}

            <div className="mt-10 flex flex-wrap gap-3">
                <button
                    type="button"
                    onClick={submit}
                    disabled={form.processing}
                    className="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    Submit for instant results
                </button>
                <Link
                    href={route('practice-quizzes.show', quiz.id)}
                    className="rounded-xl border border-theme-line px-6 py-3 text-sm text-theme-muted hover:bg-white/5"
                >
                    Leave (progress may be lost)
                </Link>
            </div>
        </PracticeHubLayout>
    );
}
