import CourseSelectFields from '@/Components/Lecturer/CourseSelectFields';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const practiceSelectClass =
    'mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm text-theme-strong focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500';

function FormErrorSummary({ errors }) {
    const entries = Object.entries(errors ?? {}).filter(([, v]) => Boolean(v));
    if (entries.length === 0) {
        return null;
    }
    return (
        <div
            role="alert"
            className="rounded-md border border-red-500/50 bg-red-950/40 px-4 py-3 text-sm text-red-100"
        >
            <p className="font-semibold text-red-50">Please fix the following:</p>
            <ul className="mt-2 list-inside list-disc space-y-1 text-red-200/95">
                {entries.map(([key, msg]) => (
                    <li key={key}>
                        {Array.isArray(msg) ? msg.join(' ') : msg}
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default function Create({ linkableCourses, schools, departments, visibilityOptions }) {
    const form = useForm({
        title: '',
        course_name: '',
        course_code: '',
        course_id: '',
        instructions: '',
        duration: 45,
        practice_duration_minutes: '',
        target_school_id: '',
        target_department_id: '',
        target_program: '',
        target_level: '',
        visibility_status: 'public',
        difficulty_level: '',
        practice_topic: '',
        allow_retry: true,
        show_answers_after_submit: true,
        show_explanations: true,
        ai_recommended: false,
        is_featured_practice: false,
    });

    const submit = () => {
        form.transform((data) => ({
            ...data,
            course_id:
                data.course_id === '' || data.course_id == null
                    ? null
                    : Number(data.course_id),
            duration: Math.max(1, Number(data.duration) || 45),
            practice_duration_minutes:
                data.practice_duration_minutes === '' ||
                data.practice_duration_minutes == null
                    ? null
                    : Math.max(1, Number(data.practice_duration_minutes)),
            target_school_id:
                data.target_school_id === '' || data.target_school_id == null
                    ? null
                    : Number(data.target_school_id),
            target_department_id:
                data.target_department_id === '' ||
                data.target_department_id == null
                    ? null
                    : Number(data.target_department_id),
        }));
        form.post(route('admin.management.practice-quizzes.store'));
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-theme-strong">Create practice quiz</h2>
                    <Link
                        href={route('admin.management.practice-quizzes.index')}
                        className="text-sm text-theme-dim underline hover:text-theme-strong"
                    >
                        Back
                    </Link>
                </div>
            }
        >
            <Head title="Create practice quiz" />
            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        noValidate
                        className="space-y-4 border border-theme-line bg-theme-glass p-6 sm:rounded-lg"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submit();
                        }}
                    >
                        <FormErrorSummary errors={form.errors} />
                        <div>
                            <label className="text-xs text-theme-dim">Title</label>
                            <input
                                className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-4">
                            <CourseSelectFields
                                data={form.data}
                                setData={form.setData}
                                errors={form.errors}
                                linkableCourses={linkableCourses}
                                selectClassName={practiceSelectClass}
                                hintWhenList="Options follow your role (admin: all courses; lecturer: yours). Links the quiz to that course where roster or metadata applies."
                                hintWhenEmpty="No courses are visible for your account. Add one under Courses & rosters, or ask an administrator."
                            />
                        </div>
                        <div>
                            <label className="text-xs text-theme-dim">Instructions</label>
                            <textarea
                                rows={3}
                                className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.instructions}
                                onChange={(e) => form.setData('instructions', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-xs text-theme-dim">Timer cap (minutes)</label>
                                <input
                                    type="number"
                                    min={1}
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.duration}
                                    onChange={(e) => form.setData('duration', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-xs text-theme-dim">Suggested practice length (optional)</label>
                                <input
                                    type="number"
                                    min={1}
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.practice_duration_minutes}
                                    onChange={(e) => form.setData('practice_duration_minutes', e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-xs text-theme-dim">Target school</label>
                                <select
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.target_school_id}
                                    onChange={(e) => form.setData('target_school_id', e.target.value)}
                                >
                                    <option value="">-</option>
                                    {schools.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="text-xs text-theme-dim">Target department</label>
                                <select
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.target_department_id}
                                    onChange={(e) => form.setData('target_department_id', e.target.value)}
                                >
                                    <option value="">-</option>
                                    {departments.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-xs text-theme-dim">Target program</label>
                                <input
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.target_program}
                                    onChange={(e) => form.setData('target_program', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-xs text-theme-dim">Target level</label>
                                <input
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.target_level}
                                    onChange={(e) => form.setData('target_level', e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-xs text-theme-dim">Visibility</label>
                                <select
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.visibility_status}
                                    onChange={(e) => form.setData('visibility_status', e.target.value)}
                                >
                                    {visibilityOptions.map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="text-xs text-theme-dim">Difficulty label</label>
                                <input
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    placeholder="easy / medium / hard"
                                    value={form.data.difficulty_level}
                                    onChange={(e) => form.setData('difficulty_level', e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <label className="text-xs text-theme-dim">Practice topic label</label>
                            <input
                                className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                value={form.data.practice_topic}
                                onChange={(e) => form.setData('practice_topic', e.target.value)}
                            />
                        </div>
                        <div className="flex flex-wrap gap-4 text-sm">
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.allow_retry}
                                    onChange={(e) => form.setData('allow_retry', e.target.checked)}
                                />
                                Allow retries
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.show_answers_after_submit}
                                    onChange={(e) => form.setData('show_answers_after_submit', e.target.checked)}
                                />
                                Show answers after submit
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.show_explanations}
                                    onChange={(e) => form.setData('show_explanations', e.target.checked)}
                                />
                                Show explanations
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.ai_recommended}
                                    onChange={(e) => form.setData('ai_recommended', e.target.checked)}
                                />
                                AI recommended badge
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_featured_practice}
                                    onChange={(e) => form.setData('is_featured_practice', e.target.checked)}
                                />
                                Featured on hub
                            </label>
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Save draft
                        </button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
