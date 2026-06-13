import { useForm } from '@inertiajs/react';
import { useCallback, useEffect, useLayoutEffect, useMemo, useState } from 'react';

function buildQuery(params) {
    const sp = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
        if (v === '' || v === null || v === undefined) {
            return;
        }
        sp.set(k, String(v));
    });
    const s = sp.toString();
    return s ? `?${s}` : '';
}

/** Keep in sync with lecturer group in routes/web.php: /exams/{exam}/question-bank */
function examQuestionBankBasePath(examId) {
    return `/lecturer/exams/${examId}/question-bank`;
}

export default function QuestionBankPicker({
    examId,
    open,
    onClose,
    courses = [],
}) {
    const [q, setQ] = useState('');
    const [courseId, setCourseId] = useState('');
    const [topic, setTopic] = useState('');
    const [questionType, setQuestionType] = useState('');
    const [difficulty, setDifficulty] = useState('');
    const [source, setSource] = useState('');
    const [status, setStatus] = useState('');
    const [debouncedQ, setDebouncedQ] = useState('');
    const [page, setPage] = useState(1);
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
    });
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState(null);
    const [selected, setSelected] = useState(() => new Set());
    const attachForm = useForm({ question_bank_ids: [] });

    useEffect(() => {
        const t = setTimeout(() => setDebouncedQ(q), 300);
        return () => clearTimeout(t);
    }, [q]);

    const filterPayload = useMemo(
        () => ({
            q: debouncedQ,
            course_id: courseId,
            topic,
            question_type: questionType,
            difficulty,
            source,
            status,
            page,
            per_page: 20,
        }),
        [
            debouncedQ,
            courseId,
            topic,
            questionType,
            difficulty,
            source,
            status,
            page,
        ],
    );

    const fetchRows = useCallback(async () => {
        setLoading(true);
        setLoadError(null);
        try {
            const url =
                examQuestionBankBasePath(examId) + buildQuery(filterPayload);
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                throw new Error(
                    body.message ||
                        `Could not load question bank (${res.status})`,
                );
            }
            const data = await res.json();
            setRows(data.data ?? []);
            if (data.meta) {
                setMeta(data.meta);
            }
        } catch (e) {
            setLoadError(e.message || 'Failed to load questions.');
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [examId, filterPayload]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }
        fetchRows();
        return undefined;
    }, [open, fetchRows]);

    useLayoutEffect(() => {
        if (!open) {
            return;
        }
        setPage(1);
        setSelected(new Set());
    }, [open]);

    const toggleRow = (id, disabled) => {
        if (disabled) {
            return;
        }
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const selectAllOnPage = () => {
        const next = new Set(selected);
        rows.forEach((r) => {
            if (!r.already_on_exam) {
                next.add(r.id);
            }
        });
        setSelected(next);
    };

    const clearSelection = () => setSelected(new Set());

    const attach = () => {
        const ids = [...selected];
        if (ids.length === 0) {
            return;
        }
        attachForm.setData('question_bank_ids', ids);
        attachForm.post(`${examQuestionBankBasePath(examId)}/attach`, {
            preserveScroll: true,
            onSuccess: () => {
                setSelected(new Set());
                onClose();
            },
        });
    };

    if (!open) {
        return null;
    }

    return (
        <div
            className="modal-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="qb-picker-title"
            onClick={(e) => {
                if (e.target === e.currentTarget) {
                    onClose();
                }
            }}
        >
            <div
                className="modal-panel modal-panel--wide"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="modal-header flex items-start justify-between gap-3">
                    <div>
                        <h2
                            id="qb-picker-title"
                            className="text-lg font-semibold text-theme-strong"
                        >
                            Add from Question Bank
                        </h2>
                        <p className="mt-1 text-xs text-theme-muted">
                            Only approved or official items you are allowed to
                            use. Duplicates already on this exam cannot be
                            selected.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md border border-theme-line bg-theme-elevated px-3 py-1.5 text-sm font-medium text-theme-strong hover:bg-theme-surface"
                    >
                        Close
                    </button>
                </div>

                <div className="modal-body space-y-3">
                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <input
                            type="search"
                            placeholder="Search text…"
                            value={q}
                            onChange={(e) => {
                                setQ(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 lg:col-span-2"
                        />
                        <select
                            value={courseId}
                            onChange={(e) => {
                                setCourseId(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">All courses</option>
                            {courses.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {(c.code || '—') + ' — ' + c.name}
                                </option>
                            ))}
                        </select>
                        <input
                            type="text"
                            placeholder="Topic contains…"
                            value={topic}
                            onChange={(e) => {
                                setTopic(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <select
                            value={questionType}
                            onChange={(e) => {
                                setQuestionType(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">Any type</option>
                            <option value="mcq">MCQ</option>
                            <option value="fill_in">Fill in</option>
                            <option value="short_answer">Short answer</option>
                            <option value="theory">Theory</option>
                        </select>
                        <select
                            value={difficulty}
                            onChange={(e) => {
                                setDifficulty(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">Any difficulty</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                        <select
                            value={source}
                            onChange={(e) => {
                                setSource(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">Any source</option>
                            <option value="mine">My bank</option>
                            <option value="official">Official only</option>
                        </select>
                        <select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                setPage(1);
                            }}
                            className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">Approved + official</option>
                            <option value="approved">Approved only</option>
                            <option value="official">Official only</option>
                        </select>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 text-xs text-theme-muted">
                        <button
                            type="button"
                            onClick={selectAllOnPage}
                            className="rounded border border-slate-300 bg-slate-100 px-2 py-1 text-slate-800 hover:bg-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            Select all on page
                        </button>
                        <button
                            type="button"
                            onClick={clearSelection}
                            className="rounded border border-slate-300 bg-slate-100 px-2 py-1 text-slate-800 hover:bg-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            Clear selection
                        </button>
                        <span>
                            Selected:{' '}
                            <strong className="text-cyan-600 dark:text-cyan-400">
                                {selected.size}
                            </strong>
                            {meta.total ? (
                                <>
                                    {' '}
                                    · {meta.total} match
                                    {meta.total === 1 ? '' : 'es'}
                                </>
                            ) : null}
                        </span>
                    </div>

                    {loadError ? (
                        <p className="text-sm text-rose-400">{loadError}</p>
                    ) : null}

                    {attachForm.errors.question_bank_ids ? (
                        <p className="text-sm text-rose-400">
                            {Array.isArray(
                                attachForm.errors.question_bank_ids,
                            )
                                ? attachForm.errors.question_bank_ids.join(' ')
                                : attachForm.errors.question_bank_ids}
                        </p>
                    ) : null}

                    <div className="modal-surface overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme-line text-sm">
                            <thead className="modal-table-head text-left text-xs font-semibold uppercase tracking-wide">
                                <tr>
                                    <th className="w-10 px-3 py-2" />
                                    <th className="px-3 py-2">Question</th>
                                    <th className="px-3 py-2">Type</th>
                                    <th className="px-3 py-2">Topic</th>
                                    <th className="px-3 py-2">Difficulty</th>
                                    <th className="px-3 py-2">Marks</th>
                                    <th className="px-3 py-2">Source</th>
                                    <th className="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody className="modal-table-body divide-y divide-theme-line">
                                {loading && rows.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-3 py-8 text-center text-theme-muted"
                                        >
                                            Loading…
                                        </td>
                                    </tr>
                                ) : null}
                                {!loading && rows.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-3 py-8 text-center text-theme-muted"
                                        >
                                            No questions match these filters.
                                        </td>
                                    </tr>
                                ) : null}
                                {rows.map((r) => {
                                    const dis = r.already_on_exam;
                                    const checked = selected.has(r.id);
                                    return (
                                        <tr
                                            key={r.id}
                                            className={
                                                dis
                                                    ? 'opacity-60'
                                                    : 'hover:bg-theme-surface'
                                            }
                                        >
                                            <td className="px-3 py-2 align-top">
                                                <input
                                                    type="checkbox"
                                                    className="rounded border-slate-400 text-slate-900 dark:border-slate-500 dark:text-slate-100"
                                                    disabled={dis}
                                                    checked={checked && !dis}
                                                    onChange={() =>
                                                        toggleRow(r.id, dis)
                                                    }
                                                    aria-label={`Select question ${r.id}`}
                                                />
                                            </td>
                                            <td className="max-w-md px-3 py-2 align-top text-theme-strong">
                                                <div className="line-clamp-3">
                                                    {r.preview}
                                                </div>
                                                {dis ? (
                                                    <span className="mt-1 inline-block rounded bg-amber-500/20 px-2 py-0.5 text-[0.65rem] font-semibold uppercase text-amber-200">
                                                        Already added
                                                    </span>
                                                ) : null}
                                            </td>
                                            <td className="px-3 py-2 align-top text-theme-main">
                                                {r.question_type}
                                            </td>
                                            <td className="px-3 py-2 align-top text-theme-main">
                                                {r.topic || '—'}
                                            </td>
                                            <td className="px-3 py-2 align-top text-theme-main">
                                                {r.difficulty || '—'}
                                            </td>
                                            <td className="px-3 py-2 align-top tabular-nums text-theme-main">
                                                {r.suggested_marks ?? '—'}
                                            </td>
                                            <td className="px-3 py-2 align-top text-xs text-theme-muted">
                                                {r.source_label}
                                            </td>
                                            <td className="px-3 py-2 align-top text-xs text-theme-muted">
                                                {r.status}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {meta.last_page > 1 ? (
                        <div className="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span className="text-theme-muted">
                                Page {meta.current_page} of {meta.last_page}
                            </span>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    disabled={meta.current_page <= 1 || loading}
                                    onClick={() =>
                                        setPage((p) => Math.max(1, p - 1))
                                    }
                                    className="rounded border border-slate-300 bg-slate-100 px-3 py-1 text-slate-800 hover:bg-slate-200 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    Previous
                                </button>
                                <button
                                    type="button"
                                    disabled={
                                        meta.current_page >= meta.last_page ||
                                        loading
                                    }
                                    onClick={() =>
                                        setPage((p) =>
                                            Math.min(meta.last_page, p + 1),
                                        )
                                    }
                                    className="rounded border border-slate-300 bg-slate-100 px-3 py-1 text-slate-800 hover:bg-slate-200 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    ) : null}
                </div>

                <div className="modal-footer">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md border border-theme-line bg-theme-elevated px-4 py-2 text-sm font-medium text-theme-strong hover:bg-theme-surface"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        disabled={attachForm.processing || selected.size === 0}
                        onClick={attach}
                        className="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-cyan-500 disabled:opacity-40"
                    >
                        {attachForm.processing
                            ? 'Adding…'
                            : `Add selected (${selected.size})`}
                    </button>
                </div>
            </div>
        </div>
    );
}
