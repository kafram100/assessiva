import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';

function PaginationLinks({ links }) {
    if (!links?.length) {
        return null;
    }
    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((link, i) => (
                <span key={i}>
                    {link.url ? (
                        <Link
                            href={link.url}
                            preserveState
                            className={`inline-block rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-theme-elevated font-semibold text-theme-strong'
                                    : 'bg-theme-surface-soft text-theme-muted hover:bg-theme-highlight'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            className="inline-block rounded bg-theme-surface-soft/60 px-3 py-1 text-sm text-theme-dim"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </span>
            ))}
        </div>
    );
}

export default function Index({
    questions,
    courses,
    topics,
    deletableBankCount,
    filters: initialFilters,
}) {
    const filterForm = useForm({
        search: initialFilters?.search ?? '',
        course_id: initialFilters?.course_id ?? '',
        topic: initialFilters?.topic ?? '',
        question_type: initialFilters?.question_type ?? '',
        difficulty: initialFilters?.difficulty ?? '',
        source: initialFilters?.source ?? '',
    });

    const deleteForm = useForm({ confirm_delete_all: false });

    const [editing, setEditing] = useState(null);
    const editForm = useForm({
        course_id: '',
        topic: '',
        question_text: '',
        question_type: 'mcq',
        difficulty: 'medium',
        correct_answer: '',
        model_answer: '',
        marking_scheme: '',
        suggested_marks: 1,
        options: [],
    });

    const applyFilters = (e) => {
        e.preventDefault();
        filterForm.get(route('admin.management.question-bank.index'), {
            preserveState: true,
        });
    };

    const openEdit = (q) => {
        setEditing(q);
        editForm.setData({
            course_id: q.course_id ?? '',
            topic: q.topic ?? '',
            question_text: q.question_text,
            question_type: q.question_type,
            difficulty: q.difficulty,
            correct_answer: q.correct_answer ?? '',
            model_answer: q.model_answer ?? '',
            marking_scheme: q.marking_scheme ?? '',
            suggested_marks: q.suggested_marks,
            options: (q.options || []).map((o) => ({
                key: o.option_key,
                text: o.option_text,
                is_correct: !!o.is_correct,
            })),
        });
    };

    const saveEdit = (e) => {
        e.preventDefault();
        editForm.put(route('admin.management.question-bank.update', editing.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(null),
        });
    };

    const addOption = () => {
        editForm.setData('options', [
            ...editForm.data.options,
            { key: 'A', text: '', is_correct: false },
        ]);
    };

    const destroyAll = (e) => {
        e.preventDefault();
        if (
            !confirm(
                `Permanently delete ${deletableBankCount} non-official bank question(s)?`,
            )
        ) {
            return;
        }
        if (!deleteForm.data.confirm_delete_all) {
            return;
        }
        deleteForm.post(route('admin.management.question-bank.delete-all'));
    };

    const rows = questions?.data ?? [];
    const me = usePage().props.auth.user?.id;
    const pageErrors = usePage().props.errors || {};

    const [selectedIds, setSelectedIds] = useState(() => new Set());
    const [bulkDeleteConfirm, setBulkDeleteConfirm] = useState(false);

    const canBulkDeleteRow = useCallback(
        (q) => q.lecturer_id === me && q.status !== 'official',
        [me],
    );

    const deletableOnPage = rows.filter(canBulkDeleteRow);
    const selectedCount = selectedIds.size;
    const allDeletableOnPageSelected =
        deletableOnPage.length > 0 &&
        deletableOnPage.every((q) => selectedIds.has(q.id));

    const toggleRowSelection = (id) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const toggleSelectAllOnPage = () => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (allDeletableOnPageSelected) {
                deletableOnPage.forEach((q) => next.delete(q.id));
            } else {
                deletableOnPage.forEach((q) => next.add(q.id));
            }
            return next;
        });
    };

    const submitBulkDelete = (e) => {
        e.preventDefault();
        const ids = [...selectedIds];
        if (ids.length === 0 || !bulkDeleteConfirm) {
            return;
        }
        router.post(
            route('admin.management.question-bank.bulk-destroy'),
            {
                ids,
                confirm_bulk_delete: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedIds(new Set());
                    setBulkDeleteConfirm(false);
                },
            },
        );
    };

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Question bank
                </h2>
            }
        >
            <Head title="Question bank" />

            <div className="space-y-6 py-8">
                {pageErrors.approve && (
                    <div className="mx-auto max-w-7xl rounded-md border border-amber-800 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 sm:px-6 lg:px-8">
                        {pageErrors.approve}
                    </div>
                )}
                <div className="mx-auto flex max-w-7xl flex-wrap gap-3 sm:px-6 lg:px-8">
                    <Link
                        href={route('admin.management.ai-generate')}
                        className="rounded-md bg-theme-surface-soft px-3 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong hover:bg-gray-700"
                    >
                        AI generate
                    </Link>
                    <Link
                        href={route('admin.management.question-bank.generate-quiz')}
                        className="rounded-md border border-theme-line bg-theme-surface-soft/60 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted hover:bg-theme-highlight/80"
                    >
                        Build quiz
                    </Link>
                    <Link
                        href={route('admin.management.question-bank.analytics')}
                        className="rounded-md border border-theme-line bg-theme-surface-soft/60 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted hover:bg-theme-highlight/80"
                    >
                        Analytics
                    </Link>
                </div>

                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={applyFilters}
                        className="mb-6 grid gap-3 rounded-lg border border-theme-line bg-theme-glass p-4 shadow sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <input
                            type="search"
                            placeholder="Search text…"
                            value={filterForm.data.search}
                            onChange={(e) =>
                                filterForm.setData('search', e.target.value)
                            }
                            className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong placeholder:text-theme-dim"
                        />
                        <select
                            value={filterForm.data.course_id}
                            onChange={(e) =>
                                filterForm.setData('course_id', e.target.value)
                            }
                            className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                        >
                            <option value="">All courses</option>
                            {courses.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.course_name}
                                </option>
                            ))}
                        </select>
                        <select
                            value={filterForm.data.topic}
                            onChange={(e) =>
                                filterForm.setData('topic', e.target.value)
                            }
                            className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                        >
                            <option value="">All topics</option>
                            {(topics || []).map((t) => (
                                <option key={t} value={t}>
                                    {t}
                                </option>
                            ))}
                        </select>
                        <select
                            value={filterForm.data.question_type}
                            onChange={(e) =>
                                filterForm.setData(
                                    'question_type',
                                    e.target.value,
                                )
                            }
                            className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                        >
                            <option value="">Any type</option>
                            <option value="mcq">MCQ</option>
                            <option value="fill_in">Fill in</option>
                            <option value="short_answer">Short answer</option>
                            <option value="theory">Theory</option>
                        </select>
                        <select
                            value={filterForm.data.difficulty}
                            onChange={(e) =>
                                filterForm.setData(
                                    'difficulty',
                                    e.target.value,
                                )
                            }
                            className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                        >
                            <option value="">Any difficulty</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                        <select
                            value={filterForm.data.source}
                            onChange={(e) =>
                                filterForm.setData('source', e.target.value)
                            }
                            className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                        >
                            <option value="">Any source</option>
                            <option value="ai">AI</option>
                            <option value="manual">Manual</option>
                        </select>
                        <div className="sm:col-span-2 lg:col-span-3">
                            <PrimaryButton type="submit" disabled={filterForm.processing}>
                                Apply filters
                            </PrimaryButton>
                        </div>
                    </form>

                    {deletableBankCount > 0 && (
                        <form
                            onSubmit={submitBulkDelete}
                            className="mb-4 flex flex-col gap-3 rounded-lg border border-amber-900/50 bg-amber-950/20 p-4 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
                        >
                            <div className="text-theme-muted">
                                <span className="font-semibold text-amber-100">
                                    {selectedCount}
                                </span>{' '}
                                question
                                {selectedCount === 1 ? '' : 's'} selected
                                {deletableOnPage.length === 0 &&
                                    selectedCount === 0 && (
                                        <span className="text-theme-dim">
                                            {' '}
                                            - tick the{' '}
                                            <strong>Pick</strong> box beside{' '}
                                            <strong>Actions</strong> on each row
                                            (official items have no box).
                                        </span>
                                    )}
                            </div>
                            <label className="flex max-w-xl items-start gap-2 text-theme-muted">
                                <input
                                    type="checkbox"
                                    className="mt-1"
                                    checked={bulkDeleteConfirm}
                                    onChange={(e) =>
                                        setBulkDeleteConfirm(e.target.checked)
                                    }
                                />
                                <span>
                                    I understand this permanently deletes the
                                    selected question(s) from my bank (never
                                    official department items).
                                </span>
                            </label>
                            <button
                                type="submit"
                                disabled={
                                    selectedCount === 0 ||
                                    !bulkDeleteConfirm
                                }
                                className="rounded bg-red-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Delete selected
                            </button>
                            {pageErrors.ids && (
                                <p className="w-full text-sm text-red-400">
                                    {Array.isArray(pageErrors.ids)
                                        ? pageErrors.ids.join(' ')
                                        : pageErrors.ids}
                                </p>
                            )}
                        </form>
                    )}

                    {deletableBankCount > 0 && (
                        <form onSubmit={destroyAll} className="mb-4 rounded border border-red-200 bg-red-50 p-4 text-sm">
                            <label className="flex items-center gap-2 text-red-900">
                                <input
                                    type="checkbox"
                                    checked={!!deleteForm.data.confirm_delete_all}
                                    onChange={(e) =>
                                        deleteForm.setData(
                                            'confirm_delete_all',
                                            e.target.checked,
                                        )
                                    }
                                />
                                I understand this permanently deletes{' '}
                                {deletableBankCount} personal bank question(s)
                                (never official items).
                            </label>
                            <button
                                type="submit"
                                disabled={
                                    !deleteForm.data.confirm_delete_all ||
                                    deleteForm.processing
                                }
                                className="mt-3 rounded bg-red-700 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white disabled:opacity-40"
                            >
                                Delete all personal
                            </button>
                        </form>
                    )}

                    <p className="mb-3 flex flex-wrap items-start gap-2 rounded-md border border-sky-800/50 bg-sky-950/25 px-3 py-2.5 text-sm leading-snug text-sky-100">
                        <span
                            className="select-none text-lg leading-none text-sky-400"
                            aria-hidden
                        >
                            ☑
                        </span>
                        <span>
                            <strong className="text-theme-strong">Bulk delete:</strong>{' '}
                            tick the{' '}
                            <strong className="text-theme-strong">
                                checkbox beside Actions
                            </strong>{' '}
                            on each row (only your non-official questions). Then
                            use the amber bar above to confirm and click{' '}
                            <em>Delete selected</em>.
                        </span>
                    </p>

                    <div className="overflow-x-auto rounded-lg border border-theme-line bg-theme-glass shadow">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-surface-soft/60">
                                <tr>
                                    <th className="px-3 py-2 text-left font-semibold text-theme-muted">
                                        Question
                                    </th>
                                    <th className="px-3 py-2 text-left font-semibold text-theme-muted">
                                        Meta
                                    </th>
                                    <th className="min-w-[14rem] px-3 py-2 text-right font-semibold text-theme-muted">
                                        <div className="flex flex-col items-end gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                                            <label className="flex cursor-pointer items-center gap-2 text-[11px] font-normal text-theme-dim">
                                                <input
                                                    type="checkbox"
                                                    title="Select all removable questions on this page"
                                                    checked={
                                                        allDeletableOnPageSelected
                                                    }
                                                    disabled={
                                                        deletableOnPage.length ===
                                                        0
                                                    }
                                                    onChange={
                                                        toggleSelectAllOnPage
                                                    }
                                                    className="size-5 cursor-pointer rounded border-2 border-slate-400 bg-theme-mesh accent-sky-500 focus:ring-2 focus:ring-sky-500 disabled:cursor-not-allowed disabled:opacity-40"
                                                />
                                                <span className="whitespace-nowrap">
                                                    All on page
                                                </span>
                                            </label>
                                            <span className="hidden text-slate-600 sm:inline">
                                                |
                                            </span>
                                            <span>Actions</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme">
                                {rows.map((q) => (
                                    <tr key={q.id}>
                                        <td className="max-w-md px-3 py-2 text-theme-strong">
                                            {q.question_text?.slice(0, 140)}
                                            {(q.question_text?.length || 0) > 140
                                                ? '…'
                                                : ''}
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-2 text-xs text-theme-dim">
                                            {q.question_type} · {q.difficulty} ·{' '}
                                            <span className="font-semibold">
                                                {q.status}
                                            </span>
                                            {q.generated_by_ai ? ' · AI' : ''}
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <div className="flex flex-wrap items-center justify-end gap-x-2 gap-y-1">
                                                {canBulkDeleteRow(q) ? (
                                                    <input
                                                        type="checkbox"
                                                        className="size-5 shrink-0 cursor-pointer rounded border-2 border-slate-400 bg-theme-mesh accent-sky-500 focus:ring-2 focus:ring-sky-500"
                                                        checked={selectedIds.has(
                                                            q.id,
                                                        )}
                                                        onChange={() =>
                                                            toggleRowSelection(
                                                                q.id,
                                                            )
                                                        }
                                                        title="Select for bulk delete"
                                                        aria-label={`Select question ${q.id} for bulk delete`}
                                                    />
                                                ) : (
                                                    <span
                                                        className="inline-flex size-5 shrink-0 items-center justify-center rounded border border-theme-line bg-theme-glass text-[10px] text-theme-dim"
                                                        title="Official or not yours - cannot bulk-delete"
                                                    >
                                                        -
                                                    </span>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => openEdit(q)}
                                                    className="text-sky-700 hover:underline"
                                                >
                                                    Edit
                                                </button>
                                            {q.lecturer_id === me &&
                                                ['draft', 'rejected'].includes(
                                                    q.status,
                                                ) && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        router.post(
                                                            route(
                                                                'lecturer.questions.approve',
                                                                q.id,
                                                            ),
                                                        )
                                                    }
                                                    className="text-emerald-700 hover:underline"
                                                >
                                                    Approve
                                                </button>
                                            )}
                                            {q.lecturer_id === me &&
                                                q.status === 'draft' && (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        const r =
                                                            window.prompt(
                                                                'Reject reason?',
                                                            );
                                                        router.post(
                                                            route(
                                                                'lecturer.questions.reject',
                                                                q.id,
                                                            ),
                                                            {
                                                                reason:
                                                                    r ?? '',
                                                            },
                                                        );
                                                    }}
                                                    className="text-amber-800 hover:underline"
                                                >
                                                    Reject
                                                </button>
                                            )}
                                            {q.lecturer_id === me &&
                                                q.status !== 'archived' && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'lecturer.question-bank.archive',
                                                                    q.id,
                                                                ),
                                                            )
                                                        }
                                                        className="text-theme-dim hover:underline"
                                                    >
                                                        Archive
                                                    </button>
                                                )}
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    router.post(
                                                        route(
                                                            'lecturer.question-bank.duplicate',
                                                            q.id,
                                                        ),
                                                    )
                                                }
                                                className="text-theme-dim hover:underline"
                                            >
                                                Duplicate
                                            </button>
                                            {q.lecturer_id === me &&
                                                q.status === 'approved' &&
                                                q.visibility === 'personal' && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'lecturer.question-bank.submit-official',
                                                                    q.id,
                                                                ),
                                                            )
                                                        }
                                                        className="text-violet-700 hover:underline"
                                                    >
                                                        Submit official
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks links={questions?.links} />
                </div>

                {editing && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-theme-highlight p-4">
                        <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-theme-line bg-theme-glass p-6 shadow-xl">
                            <h3 className="text-lg font-semibold text-theme-strong">
                                Edit question #{editing.id}
                            </h3>
                            <form onSubmit={saveEdit} className="mt-4 space-y-3">
                                <textarea
                                    required
                                    rows={4}
                                    value={editForm.data.question_text}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'question_text',
                                            e.target.value,
                                        )
                                    }
                                    className="block w-full rounded-md border-theme-line text-sm"
                                />
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <select
                                        value={editForm.data.question_type}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'question_type',
                                                e.target.value,
                                            )
                                        }
                                        className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                                    >
                                        <option value="mcq">MCQ</option>
                                        <option value="fill_in">Fill in</option>
                                        <option value="short_answer">
                                            Short answer
                                        </option>
                                        <option value="theory">Theory</option>
                                    </select>
                                    <select
                                        value={editForm.data.difficulty}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'difficulty',
                                                e.target.value,
                                            )
                                        }
                                        className="rounded-md border border-theme-line bg-theme-elevated text-sm text-theme-strong"
                                    >
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                                <input
                                    type="number"
                                    min={1}
                                    max={100}
                                    value={editForm.data.suggested_marks}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'suggested_marks',
                                            Number(e.target.value),
                                        )
                                    }
                                    className="block w-full rounded-md border-theme-line text-sm"
                                />
                                <textarea
                                    rows={2}
                                    placeholder="Correct answer / key"
                                    value={editForm.data.correct_answer}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'correct_answer',
                                            e.target.value,
                                        )
                                    }
                                    className="block w-full rounded-md border-theme-line text-sm"
                                />
                                <textarea
                                    rows={2}
                                    placeholder="Model answer"
                                    value={editForm.data.model_answer}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'model_answer',
                                            e.target.value,
                                        )
                                    }
                                    className="block w-full rounded-md border-theme-line text-sm"
                                />
                                <textarea
                                    rows={2}
                                    placeholder="Marking scheme"
                                    value={editForm.data.marking_scheme}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'marking_scheme',
                                            e.target.value,
                                        )
                                    }
                                    className="block w-full rounded-md border-theme-line text-sm"
                                />
                                {editForm.data.question_type === 'mcq' && (
                                    <div>
                                        <div className="mb-2 flex items-center justify-between">
                                            <span className="text-sm font-medium text-theme-muted">
                                                Options
                                            </span>
                                            <button
                                                type="button"
                                                onClick={addOption}
                                                className="text-xs text-sky-700 hover:underline"
                                            >
                                                + Add option
                                            </button>
                                        </div>
                                        {(editForm.data.options || []).map(
                                            (opt, idx) => (
                                                <div
                                                    key={idx}
                                                    className="mb-2 flex flex-wrap gap-2"
                                                >
                                                    <input
                                                        className="w-12 rounded border-theme-line text-sm"
                                                        maxLength={1}
                                                        value={opt.key}
                                                        onChange={(e) => {
                                                            const next = [
                                                                ...editForm.data
                                                                    .options,
                                                            ];
                                                            next[idx] = {
                                                                ...next[idx],
                                                                key: e.target
                                                                    .value,
                                                            };
                                                            editForm.setData(
                                                                'options',
                                                                next,
                                                            );
                                                        }}
                                                    />
                                                    <input
                                                        className="min-w-[200px] flex-1 rounded border-theme-line text-sm"
                                                        value={opt.text}
                                                        onChange={(e) => {
                                                            const next = [
                                                                ...editForm.data
                                                                    .options,
                                                            ];
                                                            next[idx] = {
                                                                ...next[idx],
                                                                text: e.target
                                                                    .value,
                                                            };
                                                            editForm.setData(
                                                                'options',
                                                                next,
                                                            );
                                                        }}
                                                    />
                                                    <label className="flex items-center gap-1 text-xs">
                                                        <input
                                                            type="checkbox"
                                                            checked={
                                                                !!opt.is_correct
                                                            }
                                                            onChange={(e) => {
                                                                const next = [
                                                                    ...editForm
                                                                        .data
                                                                        .options,
                                                                ];
                                                                next[idx] = {
                                                                    ...next[idx],
                                                                    is_correct:
                                                                        e.target
                                                                            .checked,
                                                                };
                                                                editForm.setData(
                                                                    'options',
                                                                    next,
                                                                );
                                                            }}
                                                        />
                                                        Correct
                                                    </label>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                                <div className="flex gap-2 pt-2">
                                    <PrimaryButton
                                        type="submit"
                                        disabled={editForm.processing}
                                    >
                                        Save
                                    </PrimaryButton>
                                    <button
                                        type="button"
                                        onClick={() => setEditing(null)}
                                        className="rounded border border-theme-line px-4 py-2 text-xs font-semibold text-theme-muted"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
