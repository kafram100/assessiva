import CourseSelectFields from '@/Components/Lecturer/CourseSelectFields';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

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

export default function Edit({
    quiz,
    linkableCourses,
    schools,
    departments,
    visibilityOptions,
    questionRoutes,
    bankQuestions = [],
}) {
    const form = useForm({
        title: quiz.title ?? '',
        course_name: quiz.course_name ?? '',
        course_code: quiz.course_code ?? '',
        course_id: quiz.course_id ?? '',
        instructions: quiz.instructions ?? '',
        duration: quiz.duration ?? 45,
        practice_duration_minutes: quiz.practice_duration_minutes ?? '',
        target_school_id: quiz.target_school_id ?? '',
        target_department_id: quiz.target_department_id ?? '',
        target_program: quiz.target_program ?? '',
        target_level: quiz.target_level ?? '',
        visibility_status: quiz.visibility_status ?? 'public',
        difficulty_level: quiz.difficulty_level ?? '',
        practice_topic: quiz.practice_topic ?? '',
        allow_retry: !!quiz.allow_retry,
        show_answers_after_submit: !!quiz.show_answers_after_submit,
        show_explanations: !!quiz.show_explanations,
        ai_recommended: !!quiz.ai_recommended,
        is_featured_practice: !!quiz.is_featured_practice,
    });

    const attachForm = useForm({ question_bank_ids: [] });

    const save = () => {
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
        form.put(route('admin.management.practice-quizzes.update', quiz.id));
    };

    const toggleBankId = (id) => {
        const idNum = Number(id);
        const cur = (attachForm.data.question_bank_ids ?? []).map(Number);
        if (cur.includes(idNum)) {
            attachForm.setData(
                'question_bank_ids',
                cur.filter((x) => x !== idNum),
            );
        } else {
            attachForm.setData('question_bank_ids', [...cur, idNum]);
        }
    };

    const attachFromBank = () => {
        attachForm.post(route('admin.management.practice-quizzes.attach-bank', quiz.id), {
            preserveScroll: true,
            onSuccess: () => attachForm.reset('question_bank_ids'),
        });
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-xl font-semibold text-theme-strong">Edit practice quiz</h2>
                    <div className="flex flex-wrap gap-2 text-sm">
                        <Link
                            href={route('admin.management.practice-quizzes.analytics', quiz.id)}
                            className="text-sky-400 hover:text-sky-300"
                        >
                            Analytics
                        </Link>
                        <Link
                            href={route('admin.management.practice-quizzes.index')}
                            className="text-theme-dim underline hover:text-theme-strong"
                        >
                            Back
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Practice · ${quiz.title}`} />
            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-2 border border-theme-line bg-theme-glass p-4 sm:rounded-lg">
                        <Link
                            href={questionRoutes.create}
                            className="rounded-lg bg-theme-highlight px-3 py-2 text-sm text-theme-strong hover:bg-slate-600"
                        >
                            Add questions
                        </Link>
                        <Link
                            href={questionRoutes.import}
                            className="rounded-lg border border-theme-line px-3 py-2 text-sm text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Import questions
                        </Link>
                        {questionRoutes.examShow ? (
                            <Link
                                href={questionRoutes.examShow}
                                className="rounded-lg border border-theme-line px-3 py-2 text-sm text-theme-muted hover:bg-theme-surface-soft"
                            >
                                Full question list
                            </Link>
                        ) : null}
                        <span className="self-center text-xs text-theme-dim">
                            {quiz.questions_count ?? 0} question(s)
                        </span>
                    </div>

                    <div className="space-y-3 border border-sky-800/40 bg-sky-950/20 p-4 sm:rounded-lg">
                        <h3 className="text-sm font-semibold text-sky-100">
                            Add from your question bank
                        </h3>
                        <p className="text-xs text-theme-dim">
                            Select approved or official items you own (or official bank items for your
                            department). Duplicates already on this quiz are skipped.
                        </p>
                        {bankQuestions.length === 0 ? (
                            <p className="text-sm text-theme-dim">
                                No eligible bank questions yet. Approve items in your bank or add
                                questions manually above.
                            </p>
                        ) : (
                            <>
                                <div className="max-h-56 space-y-2 overflow-y-auto rounded border border-theme-line bg-theme-elevated/80 p-2 text-sm">
                                    {bankQuestions.map((q) => (
                                        <label
                                            key={q.id}
                                            className={`flex cursor-pointer gap-2 rounded px-2 py-1.5 hover:bg-theme-surface-soft ${
                                                q.already_on_quiz ? 'opacity-50' : ''
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                className="mt-0.5"
                                                disabled={q.already_on_quiz}
                                                checked={(
                                                    attachForm.data.question_bank_ids ?? []
                                                ).map(Number).includes(Number(q.id))}
                                                onChange={() => toggleBankId(q.id)}
                                            />
                                            <span className="text-theme-muted">
                                                <span className="font-mono text-xs text-theme-dim">
                                                    #{q.id}
                                                </span>{' '}
                                                {q.preview}
                                                {q.topic ? (
                                                    <span className="text-xs text-theme-dim">
                                                        {' '}
                                                        · {q.topic}
                                                    </span>
                                                ) : null}
                                                <span className="text-xs text-theme-dim">
                                                    {' '}
                                                    · {q.question_type}
                                                    {q.suggested_marks
                                                        ? ` · ${q.suggested_marks} marks`
                                                        : ''}
                                                </span>
                                                {q.already_on_quiz ? (
                                                    <span className="ms-1 text-xs text-emerald-400">
                                                        (already on quiz)
                                                    </span>
                                                ) : null}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        disabled={
                                            attachForm.processing ||
                                            (attachForm.data.question_bank_ids ?? []).length === 0
                                        }
                                        onClick={attachFromBank}
                                        className="rounded-lg bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-50"
                                    >
                                        Add selected to quiz
                                    </button>
                                    {attachForm.errors.question_bank_ids ? (
                                        <span className="text-xs text-red-400">
                                            {attachForm.errors.question_bank_ids}
                                        </span>
                                    ) : null}
                                </div>
                            </>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() =>
                                router.post(route('admin.management.practice-quizzes.publish', quiz.id), {}, { preserveScroll: true })
                            }
                            className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                        >
                            Publish to hub
                        </button>
                        <button
                            type="button"
                            onClick={() =>
                                router.post(route('admin.management.practice-quizzes.unpublish', quiz.id), {}, { preserveScroll: true })
                            }
                            className="rounded-lg border border-theme-line px-3 py-2 text-sm text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Unpublish
                        </button>
                    </div>

                    <form
                        noValidate
                        className="space-y-4 border border-theme-line bg-theme-glass p-6 sm:rounded-lg"
                        onSubmit={(e) => {
                            e.preventDefault();
                            save();
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
                                hintWhenList="Options follow your role (admin: all courses; lecturer: yours)."
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
                                <label className="text-xs text-theme-dim">Suggested practice length</label>
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
                                <label className="text-xs text-theme-dim">Difficulty</label>
                                <input
                                    className="mt-1 w-full rounded border border-theme-line bg-theme-elevated px-3 py-2 text-sm"
                                    value={form.data.difficulty_level}
                                    onChange={(e) => form.setData('difficulty_level', e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <label className="text-xs text-theme-dim">Practice topic</label>
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
                                Show answers
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.show_explanations}
                                    onChange={(e) => form.setData('show_explanations', e.target.checked)}
                                />
                                Explanations
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.ai_recommended}
                                    onChange={(e) => form.setData('ai_recommended', e.target.checked)}
                                />
                                AI recommended
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_featured_practice}
                                    onChange={(e) => form.setData('is_featured_practice', e.target.checked)}
                                />
                                Featured
                            </label>
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Save changes
                        </button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
