import AiGenerationLoadingOverlay from '@/Components/Ai/AiGenerationLoadingOverlay';
import LecturerLayout from '@/Layouts/LecturerLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';

/** Must match `lecture_content` max in `LecturerQuestionBankController::generateStore`. */
const MAX_LECTURE_CHARS = 500_000;

export default function AiGenerate({
    courses,
    errors: pageErrors = {},
    requireHodApprovalForAiQuestions = false,
}) {
    const form = useForm({
        course_id: '',
        topic: '',
        lecture_content: '',
        input_file: null,
        number_of_questions: 5,
        question_types: ['mcq'],
        difficulty: 'medium',
        total_marks: 25,
        learning_outcome: '',
    });

    const toggleType = (t) => {
        const cur = form.data.question_types || [];
        if (cur.includes(t)) {
            form.setData(
                'question_types',
                cur.filter((x) => x !== t),
            );
        } else {
            form.setData('question_types', [...cur, t]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('lecturer.ai-generate.store'), {
            forceFormData: true,
        });
    };

    const lectureLen = (form.data.lecture_content || '').length;
    const lectureNearLimit = lectureLen > MAX_LECTURE_CHARS * 0.9;

    const types = [
        { v: 'mcq', label: 'MCQ' },
        { v: 'fill_in', label: 'Fill in' },
        { v: 'short_answer', label: 'Short answer' },
        { v: 'theory', label: 'Theory' },
    ];

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        AI question generation
                    </h2>
                    <Link
                        href={route('lecturer.question-bank.index')}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Question bank
                    </Link>
                </div>
            }
        >
            <Head title="AI generate" />

            <AiGenerationLoadingOverlay show={form.processing} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        aria-busy={form.processing}
                        className="space-y-6 rounded-lg border border-theme-line bg-theme-glass p-6 shadow"
                    >
                        {requireHodApprovalForAiQuestions && (
                            <div
                                role="status"
                                className="rounded-md border border-sky-800/60 bg-sky-950/30 p-3 text-sm text-sky-100"
                            >
                                Head-of-department approval is required for
                                AI-generated questions. After each batch is
                                created, submit it for HoD review from the batch
                                page before those questions appear in your bank.
                            </div>
                        )}
                        {(form.errors.ai || pageErrors?.ai) && (
                            <div
                                role="alert"
                                className="rounded-md border border-red-800 bg-red-950/40 p-3 text-sm text-red-200"
                            >
                                {form.errors.ai || pageErrors?.ai}
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-theme-muted">
                                Course (optional)
                            </label>
                            <select
                                value={form.data.course_id}
                                onChange={(e) =>
                                    form.setData('course_id', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-theme-line text-sm"
                            >
                                <option value="">—</option>
                                {courses.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.course_name}{' '}
                                        {c.course_code
                                            ? `(${c.course_code})`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-theme-muted">
                                Topic (optional)
                            </label>
                            <input
                                type="text"
                                value={form.data.topic}
                                onChange={(e) =>
                                    form.setData('topic', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-theme-line text-sm"
                            />
                        </div>

                        <div>
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <label
                                    htmlFor="lecture_content"
                                    className="block text-sm font-medium text-theme-muted"
                                >
                                    Lecture notes (text)
                                </label>
                                <span
                                    className={`text-xs tabular-nums ${
                                        lectureNearLimit
                                            ? 'text-amber-400'
                                            : 'text-theme-dim'
                                    }`}
                                    aria-live="polite"
                                >
                                    {lectureLen.toLocaleString()} /{' '}
                                    {MAX_LECTURE_CHARS.toLocaleString()}{' '}
                                    characters
                                </span>
                            </div>
                            <p className="mt-1 text-xs text-theme-dim">
                                Plenty of room for full notes or transcripts
                                (same limit as the server). Very long text may be
                                trimmed by the AI service before generation.
                            </p>
                            <textarea
                                id="lecture_content"
                                rows={18}
                                maxLength={MAX_LECTURE_CHARS}
                                value={form.data.lecture_content}
                                onChange={(e) =>
                                    form.setData(
                                        'lecture_content',
                                        e.target.value,
                                    )
                                }
                                className="mt-2 block min-h-[22rem] w-full resize-y rounded-md border border-theme-line bg-theme-glass px-3 py-2 text-sm leading-relaxed text-theme-strong placeholder:text-theme-dim focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Paste your lecture notes here…"
                            />
                            {form.errors.lecture_content && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.lecture_content}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-theme-muted">
                                Or upload source file (txt parses inline; others
                                stored)
                            </label>
                            <input
                                type="file"
                                onChange={(e) =>
                                    form.setData(
                                        'input_file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="mt-1 block w-full text-sm"
                            />
                            {form.errors.input_file && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.input_file}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Number of questions
                                </label>
                                <input
                                    type="number"
                                    min={1}
                                    max={100}
                                    value={form.data.number_of_questions}
                                    onChange={(e) =>
                                        form.setData(
                                            'number_of_questions',
                                            Number(e.target.value),
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                                {form.errors.number_of_questions && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {form.errors.number_of_questions}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Total marks target
                                </label>
                                <input
                                    type="number"
                                    min={1}
                                    max={500}
                                    value={form.data.total_marks}
                                    onChange={(e) =>
                                        form.setData(
                                            'total_marks',
                                            Number(e.target.value),
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                            </div>
                        </div>

                        <div>
                            <span className="block text-sm font-medium text-theme-muted">
                                Question types
                            </span>
                            <div className="mt-2 flex flex-wrap gap-3">
                                {types.map((t) => (
                                    <label
                                        key={t.v}
                                        className="inline-flex items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={form.data.question_types.includes(
                                                t.v,
                                            )}
                                            onChange={() => toggleType(t.v)}
                                        />
                                        {t.label}
                                    </label>
                                ))}
                            </div>
                            {form.errors.question_types && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.question_types}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Difficulty
                                </label>
                                <select
                                    value={form.data.difficulty}
                                    onChange={(e) =>
                                        form.setData(
                                            'difficulty',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                >
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Learning outcome (optional)
                                </label>
                                <input
                                    type="text"
                                    value={form.data.learning_outcome}
                                    onChange={(e) =>
                                        form.setData(
                                            'learning_outcome',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                            </div>
                        </div>

                        <PrimaryButton disabled={form.processing}>
                            {form.processing ? (
                                <span className="inline-flex items-center gap-2">
                                    <span
                                        className="inline-block h-4 w-4 shrink-0 rounded-full border-2 border-theme-line border-t-white animate-spin"
                                        aria-hidden
                                    />
                                    Generating…
                                </span>
                            ) : (
                                'Generate drafts'
                            )}
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </LecturerLayout>
    );
}
