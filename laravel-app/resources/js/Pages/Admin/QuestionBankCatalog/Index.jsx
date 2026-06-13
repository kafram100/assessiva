import AdminLayout from '@/Layouts/AdminLayout';
import HodLayout from '@/Layouts/HodLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useRef } from 'react';

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

function topicLabel(topic) {
    const t = topic != null ? String(topic).trim() : '';
    return t === '' ? 'Uncategorized' : t;
}

function CatalogBody({ questions, topicOptions, filters, scope }) {
    const searchRef = useRef(null);
    const grouped = useMemo(() => {
        const rows = questions?.data ?? [];
        const out = [];
        let block = null;
        for (const q of rows) {
            const label = topicLabel(q.topic);
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
                                    placeholder="Text or topic…"
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
                                    Topic / category
                                </label>
                                <select
                                    className="w-full rounded-lg border border-theme-line bg-ai-navy-900/80 px-3 py-2 text-sm text-theme-strong"
                                    value={filters?.topic ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        apply({ topic: v || undefined });
                                    }}
                                >
                                    <option value="">All topics</option>
                                    <option value="__uncategorized__">
                                        Uncategorized
                                    </option>
                                    {(topicOptions || []).map((t) => (
                                        <option key={t} value={t}>
                                            {t}
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
                                    value={filters?.status ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        apply({ status: v || undefined });
                                    }}
                                >
                                    <option value="">Any status</option>
                                    <option value="draft">Draft</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="archived">Archived</option>
                                    <option value="pending_official_approval">
                                        Pending official
                                    </option>
                                    <option value="official">Official</option>
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
                            No questions match these filters.
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
                                <div className="ai-table-wrap overflow-x-auto">
                                    <table className="ai-table">
                                        <thead>
                                            <tr>
                                                <th className="ai-th">Question</th>
                                                <th className="ai-th whitespace-nowrap">
                                                    Type
                                                </th>
                                                <th className="ai-th whitespace-nowrap">
                                                    Status
                                                </th>
                                                <th className="ai-th">Course</th>
                                                <th className="ai-th">Lecturer</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {block.items.map((q) => (
                                                <tr key={q.id} className="ai-tr">
                                                    <td className="ai-td max-w-md align-top text-theme-strong">
                                                        <span className="text-[11px] text-ai-soft">
                                                            #{q.id}
                                                        </span>{' '}
                                                        {q.question_text?.slice(
                                                            0,
                                                            220,
                                                        )}
                                                        {(q.question_text
                                                            ?.length || 0) > 220
                                                            ? '…'
                                                            : ''}
                                                    </td>
                                                    <td className="ai-td whitespace-nowrap align-top text-xs text-ai-soft">
                                                        {q.question_type} ·{' '}
                                                        {q.difficulty}
                                                    </td>
                                                    <td className="ai-td whitespace-nowrap align-top text-xs uppercase text-ai-muted">
                                                        {q.status}
                                                    </td>
                                                    <td className="ai-td align-top text-xs text-ai-soft">
                                                        {q.course
                                                            ? `${q.course.code} — ${q.course.name}`
                                                            : '—'}
                                                    </td>
                                                    <td className="ai-td align-top text-xs text-ai-soft">
                                                        <div>
                                                            {q.lecturer
                                                                ?.username ??
                                                                '—'}
                                                        </div>
                                                        <div>
                                                            {q.department
                                                                ?.name ?? ''}
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
        </>
    );
}

export default function Index(props) {
    const user = usePage().props.auth.user;
    const { questions, topicOptions, filters, scope } = props;

    const body = (
        <CatalogBody
            questions={questions}
            topicOptions={topicOptions}
            filters={filters}
            scope={scope}
        />
    );

    const hodHeader = (
        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
            Question bank catalog
        </h2>
    );
    const adminHeader = (
        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
            Question bank catalog
        </h2>
    );

    if (user?.role === 'hod') {
        return <HodLayout header={hodHeader}>{body}</HodLayout>;
    }

    return <AdminLayout header={adminHeader}>{body}</AdminLayout>;
}
