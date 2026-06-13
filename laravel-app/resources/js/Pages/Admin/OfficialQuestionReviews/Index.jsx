import AdminLayout from '@/Layouts/AdminLayout';
import HodLayout from '@/Layouts/HodLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

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

function ReviewsTable({ reviews }) {
    const rows = reviews?.data ?? [];

    const tableRows = useMemo(() => {
        const out = [];
        let prevTopic = null;
        for (const rev of rows) {
            const q = rev.question;
            const lab = topicLabel(q?.topic);
            if (lab !== prevTopic) {
                out.push({ kind: 'topic', key: `topic-${rev.id}-${lab}`, label: lab });
                prevTopic = lab;
            }
            out.push({ kind: 'review', key: rev.id, rev });
        }
        return out;
    }, [rows]);

    const approve = (id) => {
        if (!confirm('Approve this question for the official bank?')) {
            return;
        }
        router.post(route('admin.official-question-reviews.approve', id));
    };

    const reject = (id) => {
        const reason = window.prompt('Rejection reason (required)');
        if (!reason) {
            return;
        }
        router.post(route('admin.official-question-reviews.reject', id), {
            reason,
        });
    };

    return (
        <>
            <Head title="Official reviews" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-ai-muted">
                        Rows are sorted by review status (pending first), then by{' '}
                        <span className="font-medium text-theme-strong">
                            topic / category
                        </span>
                        , so questions stay grouped by how they are stored in the
                        bank.
                    </p>
                    <div className="ai-table-wrap overflow-x-auto">
                        <table className="ai-table">
                            <thead>
                                <tr>
                                    <th className="ai-th">Question</th>
                                    <th className="ai-th whitespace-nowrap">
                                        Topic
                                    </th>
                                    <th className="ai-th">Lecturer / dept</th>
                                    <th className="ai-th whitespace-nowrap">
                                        Status
                                    </th>
                                    <th className="ai-th text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="ai-td py-8 text-center text-ai-soft"
                                        >
                                            No review queue items.
                                        </td>
                                    </tr>
                                )}
                                {tableRows.map((item) => {
                                    if (item.kind === 'topic') {
                                        return (
                                            <tr
                                                key={item.key}
                                                className="border-b border-theme-line bg-white/[0.04]"
                                            >
                                                <td
                                                    colSpan={5}
                                                    className="px-4 py-2.5"
                                                >
                                                    <div className="font-display text-sm font-semibold tracking-tight text-theme-strong">
                                                        {item.label}
                                                    </div>
                                                    <div className="text-[0.65rem] font-medium uppercase tracking-widest text-ai-soft">
                                                        Category
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    }
                                    const rev = item.rev;
                                    const q = rev.question;
                                    const dept =
                                        q?.lecturer?.department?.name ?? '—';
                                    return (
                                        <tr key={item.key} className="ai-tr">
                                            <td className="ai-td max-w-md align-top text-theme-strong">
                                                {q?.question_text?.slice(0, 200)}
                                                {(q?.question_text?.length ||
                                                    0) > 200
                                                    ? '…'
                                                    : ''}
                                            </td>
                                            <td className="ai-td whitespace-nowrap align-top text-xs text-ai-muted">
                                                {topicLabel(q?.topic)}
                                            </td>
                                            <td className="ai-td align-top text-xs text-ai-soft">
                                                <div>
                                                    {q?.lecturer?.username ??
                                                        '—'}
                                                </div>
                                                <div>{dept}</div>
                                            </td>
                                            <td className="ai-td whitespace-nowrap align-top text-xs uppercase text-ai-muted">
                                                {rev.status}
                                            </td>
                                            <td className="ai-td space-x-2 whitespace-nowrap text-right align-top">
                                                {rev.status === 'pending' && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                approve(rev.id)
                                                            }
                                                            className="rounded-lg bg-emerald-600/90 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-500"
                                                        >
                                                            Approve
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                reject(rev.id)
                                                            }
                                                            className="rounded-lg bg-rose-700/90 px-2 py-1 text-xs font-semibold text-theme-strong hover:bg-rose-600"
                                                        >
                                                            Reject
                                                        </button>
                                                    </>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks links={reviews?.links} />
                </div>
            </div>
        </>
    );
}

export default function Index({ reviews }) {
    const user = usePage().props.auth.user;
    const hodHeader = (
        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
            Official question reviews
        </h2>
    );
    const adminHeader = (
        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
            Official question reviews
        </h2>
    );

    if (user?.role === 'hod') {
        return (
            <HodLayout header={hodHeader}>
                <ReviewsTable reviews={reviews} />
            </HodLayout>
        );
    }

    return (
        <AdminLayout header={adminHeader}>
            <ReviewsTable reviews={reviews} />
        </AdminLayout>
    );
}
