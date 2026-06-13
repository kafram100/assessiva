import LecturerLayout from '@/Layouts/LecturerLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';

export default function GenerateQuiz({ courses, topics }) {
    const form = useForm({
        title: `Bank quiz ${new Date().toISOString().slice(0, 10)}`,
        course_id: '',
        course_label: '',
        topics: [],
        number_of_questions: 10,
        difficulty_mix: {},
        question_type_mix: {},
        total_marks: 20,
        time_limit: 30,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('lecturer.question-bank.generate-quiz.store'));
    };

    const toggleTopic = (t) => {
        const cur = form.data.topics || [];
        if (cur.includes(t)) {
            form.setData(
                'topics',
                cur.filter((x) => x !== t),
            );
        } else {
            form.setData('topics', [...cur, t]);
        }
    };

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Generate quiz from bank
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
            <Head title="Generate quiz" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-5 rounded-lg border border-theme-line bg-theme-glass p-6 shadow"
                    >
                        <div>
                            <label className="block text-sm font-medium text-theme-muted">
                                Quiz title
                            </label>
                            <input
                                required
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-theme-line text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-theme-muted">
                                Course label (stored on exam)
                            </label>
                            <input
                                required
                                value={form.data.course_label}
                                onChange={(e) =>
                                    form.setData('course_label', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-theme-line text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-theme-muted">
                                Filter by course (optional)
                            </label>
                            <select
                                value={form.data.course_id}
                                onChange={(e) =>
                                    form.setData('course_id', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-theme-line text-sm"
                            >
                                <option value="">Any</option>
                                {courses.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.course_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <span className="block text-sm font-medium text-theme-muted">
                                Topics (optional)
                            </span>
                            <div className="mt-2 flex max-h-32 flex-wrap gap-2 overflow-y-auto text-sm">
                                {(topics || []).map((t) => (
                                    <label
                                        key={t}
                                        className="inline-flex items-center gap-1"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={form.data.topics.includes(
                                                t,
                                            )}
                                            onChange={() => toggleTopic(t)}
                                        />
                                        {t}
                                    </label>
                                ))}
                                {(!topics || topics.length === 0) && (
                                    <span className="text-theme-dim">
                                        No topics in your bank yet.
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Questions
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
                                    <p className="mt-1 text-xs text-red-600">
                                        {form.errors.number_of_questions}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Total marks
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
                            <div>
                                <label className="block text-sm font-medium text-theme-muted">
                                    Duration (min)
                                </label>
                                <input
                                    type="number"
                                    min={5}
                                    max={360}
                                    value={form.data.time_limit}
                                    onChange={(e) =>
                                        form.setData(
                                            'time_limit',
                                            Number(e.target.value),
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                            </div>
                        </div>
                        <PrimaryButton disabled={form.processing}>
                            Create exam draft
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </LecturerLayout>
    );
}
