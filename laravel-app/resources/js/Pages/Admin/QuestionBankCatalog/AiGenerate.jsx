import AiGenerationLoadingOverlay from '@/Components/Ai/AiGenerationLoadingOverlay';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';

const MAX_LECTURE_CHARS = 500_000;

export default function AiGenerate({
    courses,
    errors: pageErrors = {},
}) {
    const form = useForm({
        course_name: '',
        level: '',
        topic: '',
        lecture_content: '',
        input_file: null,
        question_count: 5,
        question_types: ['mcq'],
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
        form.post(route('admin.question-bank-catalog.ai-generate.store'), {
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
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        AI Question Generation
                    </h2>
                    <Link
                        href={route('admin.question-bank-catalog.index')}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Question bank catalog
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

                        {(form.errors.ai || pageErrors?.ai) && (
                            <div
                                role="alert"
                                className="rounded-md border border-red-800 bg-red-950/40 p-3 text-sm text-red-200"
                            >
                                {form.errors.ai || pageErrors?.ai}
                            </div>
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="course_name" value="Course Name" />
                                <input
                                    id="course_name"
                                    list="course_list"
                                    className="mt-1 block w-full rounded-md border border-theme-line bg-white/[0.04] p-2 text-sm text-theme-strong"
                                    value={form.data.course_name}
                                    onChange={(e) =>
                                        form.setData('course_name', e.target.value)
                                    }
                                    placeholder="Enter or select a course"
                                    required
                                />
                                <datalist id="course_list">
                                    {courses.map((c) => (
                                        <option key={c.id} value={c.name} />
                                    ))}
                                </datalist>
                                <InputError message={form.errors.course_name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="level" value="Level" />
                                <select
                                    id="level"
                                    className="mt-1 block w-full rounded-md border border-theme-line bg-white/[0.04] p-2 text-sm text-theme-strong"
                                    value={form.data.level}
                                    onChange={(e) =>
                                        form.setData('level', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Select a level</option>
                                    <option value="Year 1">Year 1</option>
                                    <option value="Year 2">Year 2</option>
                                    <option value="Year 3">Year 3</option>
                                    <option value="Year 4">Year 4</option>
                                </select>
                                <InputError message={form.errors.level} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="topic" value="Topic (optional)" />
                            <input
                                id="topic"
                                type="text"
                                value={form.data.topic}
                                onChange={(e) =>
                                    form.setData('topic', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-theme-line bg-white/[0.04] text-theme-strong text-sm"
                            />
                            <InputError message={form.errors.topic} className="mt-2" />
                        </div>

                        <div>
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <InputLabel
                                    htmlFor="lecture_content"
                                    value="Course Manual / Scheme of Work (text)"
                                />
                                <span
                                    className={`text-xs tabular-nums ${
                                        lectureNearLimit
                                            ? 'text-amber-400'
                                            : 'text-theme-dim'
                                    }`}
                                >
                                    {lectureLen.toLocaleString()} /{' '}
                                    {MAX_LECTURE_CHARS.toLocaleString()} chars
                                </span>
                            </div>
                            <p className="mt-1 text-xs text-theme-dim">
                                Paste the course manual or scheme of work content here.
                            </p>
                            <textarea
                                id="lecture_content"
                                rows={10}
                                maxLength={MAX_LECTURE_CHARS}
                                value={form.data.lecture_content}
                                onChange={(e) =>
                                    form.setData(
                                        'lecture_content',
                                        e.target.value,
                                    )
                                }
                                className="mt-2 block w-full resize-y rounded-md border border-theme-line bg-theme-glass px-3 py-2 text-sm text-theme-strong focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Paste text here…"
                            />
                            <InputError message={form.errors.lecture_content} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="input_file" value="Or upload source file (txt parses inline)" />
                            <input
                                id="input_file"
                                type="file"
                                accept=".txt,.pdf,.docx"
                                onChange={(e) =>
                                    form.setData(
                                        'input_file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="mt-1 block w-full text-sm text-theme-dim"
                            />
                            <InputError message={form.errors.input_file} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="question_count" value="Number of questions" />
                            <input
                                id="question_count"
                                type="number"
                                min={1}
                                max={100000}
                                value={form.data.question_count}
                                onChange={(e) =>
                                    form.setData(
                                        'question_count',
                                        Number(e.target.value),
                                    )
                                }
                                className="mt-1 block w-full rounded-md border-theme-line bg-white/[0.04] text-theme-strong text-sm"
                                required
                            />
                            <InputError message={form.errors.question_count} className="mt-2" />
                        </div>

                        <div>
                            <span className="block text-sm font-medium text-theme-muted">
                                Question types
                            </span>
                            <div className="mt-2 flex flex-wrap gap-3">
                                {types.map((t) => (
                                    <label
                                        key={t.v}
                                        className="inline-flex items-center gap-2 text-sm text-theme-strong"
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
                            <InputError message={form.errors.question_types} className="mt-2" />
                        </div>

                        <div className="flex justify-end pt-4">
                            <button
                                type="submit"
                                className="btn-ai-primary"
                                disabled={form.processing}
                            >
                                {form.processing ? (
                                    <span className="inline-flex items-center gap-2">
                                        <span
                                            className="inline-block h-4 w-4 shrink-0 rounded-full border-2 border-theme-line border-t-white animate-spin"
                                            aria-hidden
                                        />
                                        Generating…
                                    </span>
                                ) : (
                                    'Generate Questions'
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
