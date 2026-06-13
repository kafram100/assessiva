import HodLayout from '@/Layouts/HodLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

export default function AiQuestionReviewsIndex({ categories, pendingTotal }) {
    const errors = usePage().props.errors || {};
    const allIds = useMemo(
        () =>
            (categories || []).flatMap((cat) =>
                (cat.lecturer_groups || []).flatMap((g) =>
                    (g.questions || []).map((q) => q.id),
                ),
            ),
        [categories],
    );

    const [selected, setSelected] = useState(() => new Set());

    const toggleOne = useCallback((id) => {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const selectGroup = useCallback((ids) => {
        setSelected((prev) => {
            const next = new Set(prev);
            const allIn = ids.every((id) => next.has(id));
            if (allIn) {
                ids.forEach((id) => next.delete(id));
            } else {
                ids.forEach((id) => next.add(id));
            }
            return next;
        });
    }, []);

    const selectAll = useCallback(() => {
        setSelected((prev) => {
            if (prev.size === allIds.length) {
                return new Set();
            }
            return new Set(allIds);
        });
    }, [allIds]);

    const selectedList = useMemo(() => [...selected], [selected]);

    const bulkApprove = () => {
        if (selectedList.length === 0) {
            return;
        }
        if (
            !confirm(
                `Approve ${selectedList.length} selected question(s) into lecturers' banks?`,
            )
        ) {
            return;
        }
        router.post(
            route('hod.ai-question-reviews.bulk-approve'),
            { ids: selectedList },
            {
                preserveScroll: true,
                onSuccess: () => setSelected(new Set()),
            },
        );
    };

    const bulkReject = () => {
        if (selectedList.length === 0) {
            return;
        }
        const reason = window.prompt(
            'Optional rejection note (shown to lecturers):',
            '',
        );
        if (reason === null) {
            return;
        }
        router.post(
            route('hod.ai-question-reviews.bulk-reject'),
            { ids: selectedList, reason: reason || '' },
            {
                preserveScroll: true,
                onSuccess: () => setSelected(new Set()),
            },
        );
    };

    return (
        <HodLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        AI question review queue
                    </h2>
                    <span className="text-sm font-normal text-ai-soft">
                        {pendingTotal} pending
                    </span>
                </div>
            }
        >
            <Head title="AI question reviews" />

            <div className="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8">
                <Link
                    href={route('hod.dashboard')}
                    className="mb-4 inline-block text-sm text-ai-soft transition hover:text-theme-strong"
                >
                    ← Back to HoD overview
                </Link>

                {errors.ids && (
                    <div
                        role="alert"
                        className="mb-4 rounded-xl border border-amber-500/30 bg-amber-950/40 px-3 py-2 text-sm text-amber-100"
                    >
                        {errors.ids}
                    </div>
                )}

                {allIds.length > 0 && (
                    <div className="ai-glass-card mb-4 flex flex-wrap items-center gap-2 px-4 py-3">
                        <button
                            type="button"
                            onClick={selectAll}
                            className="btn-ai-secondary !py-1.5 !text-xs"
                        >
                            {selected.size === allIds.length
                                ? 'Clear selection'
                                : `Select all (${allIds.length})`}
                        </button>
                        <PrimaryButton
                            type="button"
                            disabled={selectedList.length === 0}
                            onClick={bulkApprove}
                        >
                            Approve selected ({selectedList.length})
                        </PrimaryButton>
                        <button
                            type="button"
                            disabled={selectedList.length === 0}
                            onClick={bulkReject}
                            className="btn-ai-danger !py-1.5 !text-xs"
                        >
                            Reject selected ({selectedList.length})
                        </button>
                    </div>
                )}

                <p className="mb-6 text-sm text-ai-muted">
                    Only lecturers in <span className="font-medium text-theme-strong">your department</span>{' '}
                    are listed. Questions are grouped by{' '}
                    <span className="font-medium text-theme-strong">topic / category</span>
                    , then by lecturer. Use row checkboxes or group toggles, then
                    approve or reject in bulk.
                </p>

                {(!categories || categories.length === 0) && (
                    <div className="ai-panel py-12 text-center text-ai-soft">
                        No AI questions are waiting for review.
                    </div>
                )}

                <div className="space-y-10">
                    {(categories || []).map((cat) => {
                        const catIds = (cat.lecturer_groups || []).flatMap((g) =>
                            (g.questions || []).map((q) => q.id),
                        );
                        const allCat =
                            catIds.length > 0 &&
                            catIds.every((id) => selected.has(id));
                        return (
                            <section key={cat.topic_key} className="space-y-4">
                                <div className="flex flex-col gap-2 border-b border-theme-line pb-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 className="font-display text-lg font-semibold tracking-tight text-theme-strong">
                                            {cat.topic_label}
                                        </h3>
                                        <p className="text-xs text-ai-soft">
                                            {cat.total} question
                                            {cat.total === 1 ? '' : 's'} in this
                                            category
                                        </p>
                                    </div>
                                    {catIds.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => selectGroup(catIds)}
                                            className="btn-ai-secondary self-start !py-1.5 !text-xs sm:self-center"
                                        >
                                            {allCat
                                                ? 'Deselect category'
                                                : 'Select all in category'}
                                        </button>
                                    )}
                                </div>

                                <div className="space-y-6">
                                    {(cat.lecturer_groups || []).map((g) => {
                                        const gIds = (g.questions || []).map(
                                            (q) => q.id,
                                        );
                                        const allG =
                                            gIds.length > 0 &&
                                            gIds.every((id) =>
                                                selected.has(id),
                                            );
                                        return (
                                            <div
                                                key={
                                                    g.lecturer_id ??
                                                    g.lecturer_username
                                                }
                                                className="ai-glass-card overflow-hidden"
                                            >
                                                <div className="flex flex-col gap-2 border-b border-theme-line bg-white/[0.02] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <div className="font-semibold text-theme-strong">
                                                            {g.lecturer_name}
                                                            {g.lecturer_username
                                                                ? ` (@${g.lecturer_username})`
                                                                : ''}
                                                        </div>
                                                        <div className="text-xs text-ai-soft">
                                                            {[g.school, g.department]
                                                                .filter(Boolean)
                                                                .join(' · ') ||
                                                                '—'}
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            selectGroup(gIds)
                                                        }
                                                        className="btn-ai-secondary shrink-0 self-start !py-1 !text-xs sm:self-center"
                                                    >
                                                        {allG
                                                            ? 'Deselect lecturer'
                                                            : 'Select all from lecturer'}
                                                    </button>
                                                </div>
                                                <div className="overflow-x-auto">
                                                    <table className="ai-table">
                                                        <thead>
                                                            <tr>
                                                                <th className="ai-th w-10">
                                                                    <span className="sr-only">
                                                                        Select
                                                                    </span>
                                                                </th>
                                                                <th className="ai-th">
                                                                    Question
                                                                </th>
                                                                <th className="ai-th whitespace-nowrap">
                                                                    Type
                                                                </th>
                                                                <th className="ai-th whitespace-nowrap">
                                                                    Marks
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {(
                                                                g.questions ||
                                                                []
                                                            ).map((q) => (
                                                                <tr
                                                                    key={q.id}
                                                                    className="ai-tr align-top"
                                                                >
                                                                    <td className="ai-td">
                                                                        <input
                                                                            type="checkbox"
                                                                            className="size-4 rounded border-theme-line bg-ai-navy-900 accent-ai-accent"
                                                                            checked={selected.has(
                                                                                q.id,
                                                                            )}
                                                                            onChange={() =>
                                                                                toggleOne(
                                                                                    q.id,
                                                                                )
                                                                            }
                                                                            aria-label={`Select question ${q.id}`}
                                                                        />
                                                                    </td>
                                                                    <td className="ai-td max-w-xl text-theme-strong">
                                                                        <span className="text-[11px] text-ai-soft">
                                                                            #{q.id}
                                                                        </span>{' '}
                                                                        {q.question_text?.slice(
                                                                            0,
                                                                            280,
                                                                        )}
                                                                        {(q
                                                                            .question_text
                                                                            ?.length ||
                                                                            0) >
                                                                        280
                                                                            ? '…'
                                                                            : ''}
                                                                    </td>
                                                                    <td className="ai-td whitespace-nowrap text-xs text-ai-soft">
                                                                        {
                                                                            q.question_type
                                                                        }{' '}
                                                                        ·{' '}
                                                                        {
                                                                            q.difficulty
                                                                        }
                                                                    </td>
                                                                    <td className="ai-td whitespace-nowrap text-xs text-ai-soft">
                                                                        {
                                                                            q.suggested_marks
                                                                        }
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </section>
                        );
                    })}
                </div>
            </div>
        </HodLayout>
    );
}
