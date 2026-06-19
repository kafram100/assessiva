import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import QuestionBankPicker from '@/Components/QuestionBankPicker';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const secondaryBtn =
    'inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted shadow-sm transition duration-150 ease-in-out hover:bg-theme-highlight/80 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-[#0d1117]';

const primaryLink =
    'inline-flex items-center rounded-md border border-transparent bg-theme-highlight px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong transition duration-150 ease-in-out hover:bg-slate-600';

function absoluteEntryUrl(entryRoute) {
    if (typeof window === 'undefined') {
        return entryRoute;
    }
    try {
        return new URL(entryRoute, window.location.origin).href;
    } catch {
        return entryRoute;
    }
}

function typeLabel(qType) {
    const v =
        typeof qType === 'string'
            ? qType
            : qType && typeof qType === 'object' && 'value' in qType
              ? qType.value
              : '';
    const map = {
        mcq: 'MCQ',
        theory: 'Theory',
        fill_in: 'Fill in',
        file: 'File',
    };
    return map[v] ?? v ?? '-';
}

function truncate(s, n) {
    if (!s || s.length <= n) {
        return s ?? '';
    }
    return `${s.slice(0, n)}…`;
}

export default function Show({
    exam,
    fairnessReport = null,
    studentEntryUrl,
    allowedIndexesCount = 0,
    rosterPreview = [],
    linkableCourses = [],
}) {
    const { errors, flash } = usePage().props;
    const questions = exam.questions ?? [];
    const deleteForm = useForm({});
    const rosterForm = useForm({
        indexes_text: '',
        indexes_file: null,
        replace: false,
        clear_all: false,
    });
    const [syncCourseId, setSyncCourseId] = useState('');
    const [copyHint, setCopyHint] = useState('');
    const [questionBankOpen, setQuestionBankOpen] = useState(false);
    const entryAbs = absoluteEntryUrl(studentEntryUrl ?? route('student.entry'));
    const code = exam.exam_code ?? '';

    const copy = async (text, label) => {
        try {
            await navigator.clipboard.writeText(text);
            setCopyHint(label);
            setTimeout(() => setCopyHint(''), 2500);
        } catch {
            setCopyHint('Copy blocked - select text manually.');
            setTimeout(() => setCopyHint(''), 4000);
        }
    };

    const publish = (action) => {
        router.post(route('admin.management.exams.publish', exam.id), { action });
    };

    const runFairnessAnalyze = () => {
        if (questions.length === 0) {
            return;
        }
        router.post(route('admin.management.exams.fairness.analyze', exam.id), {}, { preserveScroll: true });
    };

    const submitExamRoster = (e) => {
        e.preventDefault();
        rosterForm.post(route('admin.management.exams.roster.store', exam.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                rosterForm.reset('indexes_text', 'indexes_file');
                rosterForm.setData('replace', false);
                rosterForm.setData('clear_all', false);
            },
        });
    };

    const syncRosterFromCourse = () => {
        if (!exam.course_id && !syncCourseId) {
            alert('Choose a course to copy the roster from.');
            return;
        }
        const body = exam.course_id ? {} : { course_id: Number(syncCourseId) };
        router.post(
            route('admin.management.exams.roster.sync-from-course', exam.id),
            body,
            { preserveScroll: true },
        );
    };

    const destroyExam = () => {
        if (
            !confirm(
                'Delete this exam? This cannot be undone if students have attempts (future constraint).',
            )
        ) {
            return;
        }
        deleteForm.delete(route('admin.management.exams.destroy', exam.id));
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            {exam.title}
                        </h2>
                        <p className="text-sm text-theme-dim">
                            Code{' '}
                            <span className="font-mono font-semibold text-theme-strong">
                                {code || '-'}
                            </span>
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {questions.length > 0 ? (
                            <PrimaryButton
                                type="button"
                                onClick={runFairnessAnalyze}
                                className="text-xs uppercase tracking-widest"
                            >
                                Analyze fairness
                            </PrimaryButton>
                        ) : (
                            <span
                                className="inline-flex cursor-not-allowed items-center rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-dim opacity-70"
                                title="Add questions before running fairness analysis."
                            >
                                Analyze fairness
                            </span>
                        )}
                        {fairnessReport ? (
                            <>
                                <Link
                                    href={route('admin.management.exams.fairness.show', exam.id)}
                                    className="inline-flex items-center rounded-md border border-ai-accent/40 bg-ai-accent/10 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-ai-accent shadow-sm transition hover:bg-ai-accent/20"
                                >
                                    View fairness report
                                </Link>
                                <PrimaryButton
                                    type="button"
                                    onClick={() => {
                                        if (
                                            !confirm(
                                                'Re-run AI fairness analysis? This replaces the stored report.',
                                            )
                                        ) {
                                            return;
                                        }
                                        router.post(
                                            route('admin.management.exams.fairness.reanalyze', exam.id),
                                            {},
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="text-xs uppercase tracking-widest"
                                >
                                    Re-analyze
                                </PrimaryButton>
                            </>
                        ) : null}
                        <Link
                            href={route('admin.management.exams.edit', exam.id)}
                            className={secondaryBtn}
                        >
                            Edit
                        </Link>
                        <Link
                            href={route('admin.management.exams.stats', exam.id)}
                            className={primaryLink}
                        >
                            Results & stats
                        </Link>
                        <Link
                            href={route('admin.management.exams.index')}
                            className={secondaryBtn}
                        >
                            All exams
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={exam.title} />

            {flash?.success ? (
                <div className="mx-auto max-w-4xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div className="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900 dark:text-emerald-100">
                        {flash.success}
                    </div>
                </div>
            ) : null}

            {flash?.error ? (
                <div className="mx-auto max-w-4xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div className="rounded-xl border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-900 dark:text-rose-100">
                        {flash.error}
                    </div>
                </div>
            ) : null}

            {errors?.fairness ? (
                <div className="mx-auto max-w-4xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div className="rounded-xl border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-900 dark:text-rose-100">
                        {errors.fairness}
                    </div>
                </div>
            ) : null}

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="grid gap-6 lg:grid-cols-3">
                        <div className="space-y-4 rounded-lg border border-theme-line bg-theme-glass p-6 shadow lg:col-span-2">
                            <h3 className="text-lg font-medium text-theme-strong">
                                Details
                            </h3>
                            <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-theme-dim">Course</dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.course_name}
                                        {exam.course_code
                                            ? ` (${exam.course_code})`
                                            : ''}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-theme-dim">Type</dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.exam_type ?? '-'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-theme-dim">Proctoring</dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.proctoring_mode === 'enabled'
                                            ? 'Always on'
                                            : exam.proctoring_mode === 'disabled'
                                              ? 'Always off'
                                              : 'Automatic'}
                                        {exam.is_proctoring_required
                                            ? ' · required for students'
                                            : ' · not required'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-theme-dim">Duration</dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.duration} minutes
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-theme-dim">Attempts</dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.attempts_allowed}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-theme-dim">Shuffle</dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.shuffle_questions ? 'Yes' : 'No'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-theme-dim">
                                        Submission password
                                    </dt>
                                    <dd className="font-medium text-theme-strong">
                                        {exam.submission_password_enabled
                                            ? 'Enabled'
                                            : 'Off'}
                                    </dd>
                                </div>
                            </dl>
                            {exam.instructions && (
                                <div>
                                    <h4 className="text-sm font-semibold text-theme-muted">
                                        Instructions
                                    </h4>
                                    <p className="mt-1 whitespace-pre-wrap text-sm text-theme-dim">
                                        {exam.instructions}
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="space-y-4 rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                            <h3 className="text-lg font-medium text-theme-strong">
                                Publishing
                            </h3>
                            <p className="text-sm text-theme-dim">
                                Published:{' '}
                                <strong>
                                    {exam.is_published ? 'Yes' : 'No'}
                                </strong>
                            </p>
                            <p className="text-sm text-theme-dim">
                                Results released:{' '}
                                <strong>
                                    {exam.results_released ? 'Yes' : 'No'}
                                </strong>
                            </p>
                            <div className="flex flex-col gap-2">
                                {exam.is_published ? (
                                    <button
                                        type="button"
                                        className={secondaryBtn}
                                        onClick={() => publish('unpublish')}
                                    >
                                        Unpublish
                                    </button>
                                ) : (
                                    <PrimaryButton
                                        type="button"
                                        onClick={() => publish('publish')}
                                    >
                                        Publish
                                    </PrimaryButton>
                                )}
                                {exam.results_released ? (
                                    <button
                                        type="button"
                                        className={secondaryBtn}
                                        onClick={() =>
                                            publish('hide_results')
                                        }
                                    >
                                        Hide results
                                    </button>
                                ) : (
                                    <PrimaryButton
                                        type="button"
                                        onClick={() =>
                                            publish('release_results')
                                        }
                                    >
                                        Release results
                                    </PrimaryButton>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-sky-800/60 bg-sky-950/30 p-6 shadow">
                        <h3 className="text-lg font-medium text-theme-strong">
                            Student exam link
                        </h3>
                        <p className="mt-1 text-sm text-theme-dim">
                            Share the URL below. Students open it, choose
                            &quot;Take exam&quot;, and enter this exam&apos;s
                            code:{' '}
                            <span className="font-mono font-semibold text-sky-700 dark:text-sky-200">
                                {code || '-'}
                            </span>
                            . Publish the exam when it is ready for students.
                        </p>
                        <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a
                                href={entryAbs}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="break-all text-sm font-medium text-sky-400 underline decoration-sky-500/40 underline-offset-2 hover:text-sky-300"
                            >
                                {entryAbs}
                            </a>
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <button
                                type="button"
                                className={secondaryBtn}
                                onClick={() => copy(entryAbs, 'Link copied')}
                            >
                                Copy student URL
                            </button>
                            <button
                                type="button"
                                className={secondaryBtn}
                                onClick={() =>
                                    copy(code, 'Exam code copied')
                                }
                                disabled={!code}
                            >
                                Copy exam code
                            </button>
                            <button
                                type="button"
                                className={secondaryBtn}
                                onClick={() =>
                                    copy(
                                        `Exam: ${exam.title}\nOpen: ${entryAbs}\nExam code: ${code}`,
                                        'Instructions copied',
                                    )
                                }
                                disabled={!code}
                            >
                                Copy URL + code
                            </button>
                        </div>
                        {copyHint ? (
                            <p className="mt-2 text-xs text-emerald-400">
                                {copyHint}
                            </p>
                        ) : null}
                    </div>

                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <h3 className="text-lg font-medium text-theme-strong">
                            Class roster (allowed index numbers)
                        </h3>
                        <p className="mt-1 text-sm text-theme-dim">
                            If the roster is non-empty, only listed index numbers
                            can start this exam. Leave empty to allow any index
                            (subject to other rules).
                        </p>
                        <p className="mt-2 text-sm text-theme-muted">
                            On roster:{' '}
                            <strong>{allowedIndexesCount}</strong>
                        </p>

                        <form
                            onSubmit={submitExamRoster}
                            className="mt-6 space-y-4"
                            encType="multipart/form-data"
                        >
                            <div>
                                <InputLabel
                                    htmlFor="exam_indexes_text"
                                    value="Index numbers (paste or type)"
                                />
                                <textarea
                                    id="exam_indexes_text"
                                    rows={5}
                                    className="mt-1 block w-full rounded-md border-theme-line bg-theme-elevated font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={rosterForm.data.indexes_text}
                                    onChange={(e) =>
                                        rosterForm.setData(
                                            'indexes_text',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={rosterForm.errors.indexes_text}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="exam_indexes_file"
                                    value="CSV / text file (optional)"
                                />
                                <input
                                    id="exam_indexes_file"
                                    type="file"
                                    accept=".csv,.txt,text/csv,text/plain"
                                    className="mt-1 block w-full text-sm text-theme-dim"
                                    onChange={(e) =>
                                        rosterForm.setData(
                                            'indexes_file',
                                            e.target.files[0] ?? null,
                                        )
                                    }
                                />
                                <InputError
                                    message={rosterForm.errors.indexes_file}
                                    className="mt-2"
                                />
                            </div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <label className="flex items-center gap-2 text-sm text-theme-muted">
                                    <Checkbox
                                        checked={rosterForm.data.replace}
                                        onChange={(e) =>
                                            rosterForm.setData(
                                                'replace',
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Replace entire roster
                                </label>
                                <label className="flex items-center gap-2 text-sm text-theme-muted">
                                    <Checkbox
                                        checked={rosterForm.data.clear_all}
                                        onChange={(e) =>
                                            rosterForm.setData(
                                                'clear_all',
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Clear all index numbers
                                </label>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <PrimaryButton disabled={rosterForm.processing}>
                                    Apply roster
                                </PrimaryButton>
                                <a
                                    href={route(
                                        'lecturer.exams.roster.template',
                                    )}
                                    className={secondaryBtn}
                                >
                                    CSV template
                                </a>
                                <a
                                    href={route(
                                        'lecturer.exams.roster.export',
                                        exam.id,
                                    )}
                                    className={secondaryBtn}
                                >
                                    Export current roster
                                </a>
                            </div>
                        </form>

                        <div className="mt-6 border-t border-theme-line pt-6">
                            <h4 className="text-sm font-semibold text-theme-muted">
                                Copy roster from a course
                            </h4>
                            <p className="mt-1 text-xs text-theme-dim">
                                Replaces the exam roster with the course roster.
                                {exam.course_id
                                    ? ` Linked course ID: ${exam.course_id}.`
                                    : ' Choose a course you manage if this exam is not linked to one.'}
                            </p>
                            {!exam.course_id && (linkableCourses ?? []).length > 0 ? (
                                <select
                                    className="mt-3 block w-full max-w-md rounded-md border-theme-line bg-theme-elevated text-sm shadow-sm"
                                    value={syncCourseId}
                                    onChange={(e) =>
                                        setSyncCourseId(e.target.value)
                                    }
                                >
                                    <option value="">Select course…</option>
                                    {linkableCourses.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.code} - {c.name}
                                        </option>
                                    ))}
                                </select>
                            ) : null}
                            <div className="mt-3">
                                <button
                                    type="button"
                                    className={secondaryBtn}
                                    onClick={syncRosterFromCourse}
                                >
                                    Sync from course roster
                                </button>
                            </div>
                        </div>

                        {(rosterPreview ?? []).length > 0 ? (
                            <div className="mt-6">
                                <h4 className="text-sm font-semibold text-theme-muted">
                                    Preview (first {rosterPreview.length}
                                    {allowedIndexesCount > rosterPreview.length
                                        ? ` of ${allowedIndexesCount}`
                                        : ''}
                                    )
                                </h4>
                                <ul className="mt-2 max-h-36 overflow-y-auto font-mono text-xs text-theme-dim">
                                    {rosterPreview.map((idx) => (
                                        <li key={idx}>{idx}</li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}
                    </div>

                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h3 className="text-lg font-medium text-theme-strong">
                                Questions ({questions.length})
                            </h3>
                            <div className="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onClick={() => setQuestionBankOpen(true)}
                                    className={secondaryBtn}
                                >
                                    Add from Question Bank
                                </button>
                                <Link
                                    href={route(
                                        'lecturer.questions.create',
                                        exam.id,
                                    )}
                                    className={primaryLink}
                                >
                                    Add question
                                </Link>
                                <Link
                                    href={route(
                                        'lecturer.questions.import',
                                        exam.id,
                                    )}
                                    className={secondaryBtn}
                                >
                                    Import CSV
                                </Link>
                                <a
                                    href={route(
                                        'lecturer.questions.template',
                                    )}
                                    className={secondaryBtn}
                                >
                                    CSV template
                                </a>
                            </div>
                        </div>
                        {questions.length === 0 ? (
                            <p className="text-sm text-theme-dim">
                                No questions yet. Add a question or import a
                                CSV file.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-theme text-sm">
                                    <thead>
                                        <tr className="text-left text-xs font-medium uppercase tracking-wide text-theme-dim">
                                            <th className="py-2 pe-4">#</th>
                                            <th className="py-2 pe-4">Type</th>
                                            <th className="py-2 pe-4">
                                                Question
                                            </th>
                                            <th className="py-2 pe-4">
                                                Marks
                                            </th>
                                            <th className="py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-theme">
                                        {questions.map((q, idx) => (
                                            <tr key={q.id}>
                                                <td className="py-2 pe-4 text-theme-dim">
                                                    {idx + 1}
                                                </td>
                                                <td className="py-2 pe-4">
                                                    {typeLabel(q.q_type)}
                                                </td>
                                                <td className="max-w-md py-2 pe-4 text-theme-strong">
                                                    {truncate(
                                                        q.question_text,
                                                        140,
                                                    )}
                                                </td>
                                                <td className="py-2 pe-4">
                                                    {q.marks}
                                                </td>
                                                <td className="py-2">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Link
                                                            href={route(
                                                                'lecturer.questions.edit',
                                                                q.id,
                                                            )}
                                                            className="text-indigo-600 underline"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            type="button"
                                                            className="text-red-600 underline"
                                                            onClick={() => {
                                                                if (
                                                                    !confirm(
                                                                        'Delete this question?',
                                                                    )
                                                                ) {
                                                                    return;
                                                                }
                                                                router.delete(
                                                                    route(
                                                                        'lecturer.questions.destroy',
                                                                        q.id,
                                                                    ),
                                                                );
                                                            }}
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    <div className="rounded-lg border border-red-100 bg-red-50 p-4">
                        <DangerButton type="button" onClick={destroyExam}>
                            Delete exam
                        </DangerButton>
                    </div>
                </div>
            </div>
            <QuestionBankPicker
                examId={exam.id}
                open={questionBankOpen}
                onClose={() => setQuestionBankOpen(false)}
                courses={linkableCourses}
            />
        </AdminLayout>
    );
}
