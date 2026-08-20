import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import EditModal from './EditModal';

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
                            className={`inline-block rounded-lg px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-ai-accent font-semibold text-theme-strong'
                                    : 'border border-theme-line bg-white/[0.04] text-ai-muted hover:border-ai-accent/30 hover:text-theme-strong'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            className="inline-block rounded-lg bg-white/[0.02] px-3 py-1 text-sm text-ai-soft"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </span>
            ))}
        </div>
    );
}

function getCourseLabel(question) {
    return question.course ? question.course.name : 'Unknown Course';
}

function CatalogBody({ questions, topicOptions, filters, scope, courses }) {
    const searchRef = useRef(null);
    const [editingQuestion, setEditingQuestion] = useState(null);
    const grouped = useMemo(() => {
        const rows = questions?.data ?? [];
        const out = [];
        let block = null;
        for (const q of rows) {
            const label = getCourseLabel(q);
            if (!block || block.label !== label) {
                block = { label, items: [] };
                out.push(block);
            }
            block.items.push(q);
        }
        return out;
    }, [questions]);

    const apply = (patch) => {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((k) => {
            if (next[k] === '' || next[k] == null) {
                delete next[k];
            }
        });
        router.get(route('admin.question-bank-catalog.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Question bank catalog" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

                    {/* Action buttons — belong in the page, not the navbar */}
                    <div className="mb-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <Link
                            href={route('admin.question-bank-catalog.import')}
                            className="btn-ai-secondary flex items-center justify-center gap-2"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Import Questions
                        </Link>
                        <Link
                            href={route('admin.question-bank-catalog.ai-generate')}
                            className="btn-ai-primary flex items-center justify-center gap-2"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            AI Generate Questions
                        </Link>
                    </div>

                    <p className="mb-6 text-sm text-ai-muted">
                        {scope === 'admin'
                            ? 'Institution-wide view of every question bank item, organized by topic on each page.'
                            : 'Department and institution-wide official items in your scope, organized by topic on each page.'}
                    </p>

                    <div className="ai-glass-card mb-6 space-y-4 p-4">
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-ai-soft">
                                    Search
                                </label>
                                <input
                                    ref={searchRef}
                                    type="search"
                                    defaultValue={filters?.search ?? ''}
                                    className="w-full rounded-lg border border-theme-line bg-ai-navy-900/80 px-3 py-2 text-sm text-theme-strong placeholder:text-ai-soft"
                                    placeholder="Text, topic, or course…"
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            apply({
                                                search: e.currentTarget.value,
                                            });
                                        }
                                    }}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-ai-soft">
                                    Course
                                </label>
                                <select
                                    className="w-full rounded-lg border border-theme-line bg-ai-navy-900/80 px-3 py-2 text-sm text-theme-strong"
                                    value={filters?.course_id ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        apply({ course_id: v || undefined });
                                    }}
                                >
                                    <option value="">All courses</option>
                                    {(courses || []).map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-ai-soft">
                                    Status
                                </label>
                                <select
                                    className="w-full rounded-lg border border-theme-line bg-ai-navy-900/80 px-3 py-2 text-sm text-theme-strong"
                                    value={filters?.is_active ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        apply({ is_active: v || undefined });
                                    }}
                                >
                                    <option value="">Any</option>
                                    <option value="1">Active</option>
                                    <option value="0">Archived</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-ai-soft">
                                    Question type
                                </label>
                                <select
                                    className="w-full rounded-lg border border-theme-line bg-ai-navy-900/80 px-3 py-2 text-sm text-theme-strong"
                                    value={filters?.question_type ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        apply({
                                            question_type: v || undefined,
                                        });
                                    }}
                                >
                                    <option value="">Any type</option>
                                    <option value="mcq">MCQ</option>
                                    <option value="fill_in">Fill in</option>
                                    <option value="short_answer">
                                        Short answer
                                    </option>
                                    <option value="theory">Theory</option>
                                </select>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                className="btn-ai-primary !py-2 !text-xs"
                                onClick={() =>
                                    apply({
                                        search: searchRef.current?.value ?? '',
                                    })
                                }
                            >
                                Apply search
                            </button>
                            <button
                                type="button"
                                className="btn-ai-secondary !py-2 !text-xs"
                                onClick={() =>
                                    router.get(
                                        route(
                                            'admin.question-bank-catalog.index',
                                        ),
                                        {},
                                        { replace: true },
                                    )
                                }
                            >
                                Clear filters
                            </button>
                        </div>
                    </div>

                    {grouped.length === 0 && (
                        <div className="ai-panel py-12 text-center text-ai-soft">
                            {(!filters?.search && !filters?.course_id) 
                                ? "Please search for a course or select one to view its questions." 
                                : "No questions match these filters."}
                        </div>
                    )}

                    <div className="space-y-8">
                        {grouped.map((block) => (
                            <section key={block.label} className="space-y-3">
                                <div className="flex items-baseline justify-between gap-3 border-b border-theme-line pb-2">
                                    <h3 className="font-display text-lg font-semibold text-theme-strong">
                                        {block.label}
                                    </h3>
                                    <span className="text-xs text-ai-soft">
                                        {block.items.length} on this page
                                    </span>
                                </div>
                                {/* ── Mobile card view (hidden on md+) ── */}
                                <div className="space-y-3 md:hidden">
                                    {block.items.map((q) => (
                                        <div key={q.id} className="rounded-xl border border-theme-line bg-theme-glass p-4 space-y-3 shadow-sm">
                                            {/* Question */}
                                            <div>
                                                <span className="text-[10px] font-semibold uppercase tracking-wide text-ai-muted">Question {q.question_number}</span>
                                                <p className="mt-1 text-sm text-theme-strong leading-relaxed">{q.question_text}</p>
                                            </div>

                                            {/* Answer */}
                                            <div className="rounded-lg bg-ai-accent/10 border border-ai-accent/20 p-3">
                                                <span className="text-[10px] font-semibold uppercase tracking-wide text-ai-accent block mb-1">
                                                    {q.question_type === 'mcq' ? 'Correct Answer' : 'Suggested Answer'}
                                                </span>
                                                {q.question_type === 'mcq' ? (
                                                    <div>
                                                        <span className="font-bold text-ai-accent text-sm">{q.correct_answer || '-'}</span>
                                                        {q.options && q.correct_answer && (
                                                            <span className="text-theme-strong text-sm ml-2">
                                                                — {q.options.find(o => o.option_key === q.correct_answer)?.option_text || ''}
                                                            </span>
                                                        )}
                                                        {/* Show all MCQ options */}
                                                        {q.options && q.options.length > 0 && (
                                                            <ul className="mt-2 space-y-1">
                                                                {q.options.map(o => (
                                                                    <li key={o.option_key} className={`text-xs flex gap-2 ${o.option_key === q.correct_answer ? 'text-ai-accent font-semibold' : 'text-ai-soft'}`}>
                                                                        <span className="shrink-0 font-bold w-4">{o.option_key}.</span>
                                                                        <span>{o.option_text}</span>
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <p className="text-sm text-theme-strong leading-relaxed">{q.model_answer || q.correct_answer || '-'}</p>
                                                )}
                                            </div>

                                            {/* Meta row */}
                                            <div className="flex flex-wrap gap-2 text-xs">
                                                <span className="rounded-full bg-white/[0.06] px-2 py-1 text-ai-soft capitalize">{q.question_type?.replace('_', ' ')}</span>
                                                <span className="rounded-full bg-white/[0.06] px-2 py-1 text-ai-soft capitalize">{q.difficulty}</span>
                                                <span className={`rounded-full px-2 py-1 font-medium ${q.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}`}>
                                                    {q.is_active ? 'Active' : 'Archived'}
                                                </span>
                                                {q.course && (
                                                    <span className="rounded-full bg-white/[0.06] px-2 py-1 text-ai-soft">{q.course.code}</span>
                                                )}
                                            </div>
                                            
                                            {/* Mobile Actions */}
                                            <div className="mt-2 flex items-center justify-end gap-2 border-t border-theme-line pt-3">
                                                <button type="button" onClick={() => setEditingQuestion(q)} className="text-ai-accent hover:text-ai-accent/80 p-2 rounded-lg bg-white/[0.02] hover:bg-white/[0.05]">
                                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button type="button" onClick={() => { if(confirm('Are you sure you want to delete this question?')) { router.delete(route('admin.question-bank-catalog.destroy', q.id), { preserveScroll: true }) } }} className="text-red-400 hover:text-red-300 p-2 rounded-lg bg-white/[0.02] hover:bg-white/[0.05]">
                                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* ── Desktop table view (hidden on mobile) ── */}
                                <div className="ai-table-wrap overflow-x-auto hidden md:block">
                                    <table className="ai-table">
                                        <thead>
                                            <tr>
                                                <th className="ai-th">Question</th>
                                                <th className="ai-th">Answer</th>
                                                <th className="ai-th whitespace-nowrap">Type</th>
                                                <th className="ai-th whitespace-nowrap">Status</th>
                                                <th className="ai-th">Course</th>
                                                <th className="ai-th text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {block.items.map((q) => (
                                                <tr key={q.id} className="ai-tr">
                                                    <td className="ai-td max-w-md align-top text-theme-strong">
                                                        <span className="text-[11px] text-ai-soft font-semibold">Question {q.question_number}</span><br />
                                                        {q.question_text?.slice(0, 220)}
                                                        {(q.question_text?.length || 0) > 220 ? '…' : ''}
                                                    </td>
                                                    <td className="ai-td max-w-sm align-top text-theme-strong text-sm">
                                                        {q.question_type === 'mcq' ? (
                                                            <div className="space-y-1 mt-1">
                                                                {q.options && q.options.length > 0 ? (
                                                                    q.options.map(o => (
                                                                        <div key={o.option_key} className={`text-xs flex gap-2 ${o.option_key === q.correct_answer ? 'text-ai-accent font-semibold' : 'text-ai-soft'}`}>
                                                                            <span className="shrink-0 font-bold w-4">{o.option_key}.</span>
                                                                            <span>{o.option_text}</span>
                                                                        </div>
                                                                    ))
                                                                ) : (
                                                                    <span className="font-bold text-ai-accent">{q.correct_answer || '-'}</span>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div>
                                                                <span className="text-[10px] font-semibold uppercase tracking-wide text-ai-muted mb-1 block">Suggested Answer</span>
                                                                <div className="text-ai-soft" title={q.model_answer || q.correct_answer}>
                                                                    {q.model_answer || q.correct_answer || '-'}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="ai-td whitespace-nowrap align-top text-xs text-ai-soft">
                                                        {q.question_type} · {q.difficulty}
                                                    </td>
                                                    <td className="ai-td whitespace-nowrap align-top text-xs uppercase text-ai-muted">
                                                        {q.is_active ? 'Active' : 'Archived'}
                                                    </td>
                                                    <td className="ai-td align-top text-xs text-ai-soft">
                                                        {q.course ? `${q.course.code} - ${q.course.name}` : '-'}
                                                    </td>
                                                    <td className="ai-td align-top text-right whitespace-nowrap">
                                                        <div className="flex items-center justify-end gap-3">
                                                            <button type="button" onClick={() => setEditingQuestion(q)} className="text-ai-accent hover:text-ai-accent/80 transition-colors" title="Edit">
                                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button type="button" onClick={() => { if(confirm('Are you sure you want to delete this question?')) { router.delete(route('admin.question-bank-catalog.destroy', q.id), { preserveScroll: true }) } }} className="text-red-400 hover:text-red-300 transition-colors" title="Delete">
                                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                        ))}
                    </div>

                    <PaginationLinks links={questions?.links} />
                </div>
            </div>

            <EditModal 
                show={!!editingQuestion}
                onClose={() => setEditingQuestion(null)}
                question={editingQuestion}
                courses={courses}
            />
        </>
    );
}

export default function Index(props) {
    const { questions, topicOptions, filters, scope, courses } = props;

    const adminHeader = (
        <h2 className="truncate text-base font-semibold leading-tight text-theme-strong sm:text-xl">
            Question bank catalog
        </h2>
    );

    const body = (
        <CatalogBody
            questions={questions}
            topicOptions={topicOptions}
            filters={filters}
            scope={scope}
            courses={courses}
        />
    );

    return <AdminLayout header={adminHeader}>{body}</AdminLayout>;
}
