import PrimaryButton from '@/Components/PrimaryButton';
import { router } from '@inertiajs/react';

function statusBadge(status) {
    const s = status || 'pending';
    const map = {
        pending: 'bg-slate-600/40 text-slate-200 border-slate-500/40',
        applied: 'bg-rose-900/40 text-rose-100 border-rose-600/40',
        ignored: 'bg-zinc-700/50 text-zinc-100 border-zinc-500/40',
        dismissed: 'bg-emerald-900/35 text-emerald-100 border-emerald-600/35',
    };
    const cls = map[s] || map.pending;
    return (
        <span
            className={`inline-flex rounded border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${cls}`}
        >
            {s}
        </span>
    );
}

export default function CheatEventReviewPanel({ grouped, examId }) {
    const entries = Object.entries(grouped ?? {});

    const postAction = (name, eventId) => {
        if (!examId) {
            return;
        }
        router.post(
            route(name, { exam: examId, event: eventId }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow lg:col-span-2">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h3 className="text-base font-semibold text-theme-strong">
                    Suspicious activity (integrity events)
                </h3>
                {!examId && (
                    <p className="text-xs text-amber-700 dark:text-amber-400">
                        Select a specific exam to use apply / ignore actions.
                    </p>
                )}
            </div>
            {entries.length === 0 ? (
                <p className="mt-2 text-sm text-theme-dim">No rows.</p>
            ) : (
                <div className="mt-4 max-h-[520px] space-y-6 overflow-y-auto">
                    {entries.map(([index, rows]) => (
                        <div key={index}>
                            <p className="font-mono text-xs font-bold text-theme-dim">
                                {index}
                            </p>
                            <div className="mt-2 overflow-x-auto">
                                <table className="min-w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b text-theme-dim">
                                            <th className="py-2 pr-2">When</th>
                                            <th className="py-2 pr-2">Type</th>
                                            <th className="py-2 pr-2">Severity</th>
                                            <th className="py-2 pr-2">Status</th>
                                            <th className="py-2 pr-2">Penalty</th>
                                            <th className="py-2 pr-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(rows ?? []).map((row) => (
                                            <tr
                                                key={row.id}
                                                className="border-b border-theme-line text-theme-muted"
                                            >
                                                <td className="py-2 pr-2 text-theme-dim">
                                                    {row.created_at
                                                        ? new Date(
                                                              row.created_at,
                                                          ).toLocaleString()
                                                        : '—'}
                                                </td>
                                                <td className="py-2 pr-2 font-mono">
                                                    {row.event_type}
                                                </td>
                                                <td className="py-2 pr-2">
                                                    {row.severity}
                                                </td>
                                                <td className="py-2 pr-2">
                                                    {statusBadge(
                                                        row.review_status,
                                                    )}
                                                </td>
                                                <td className="py-2 pr-2 font-mono">
                                                    {row.penalty_applied &&
                                                    row.penalty_marks != null
                                                        ? `−${Number(row.penalty_marks).toFixed(2)}`
                                                        : '—'}
                                                </td>
                                                <td className="py-2 pr-2">
                                                    <div className="flex flex-wrap gap-1">
                                                        <button
                                                            type="button"
                                                            disabled={!examId}
                                                            onClick={() =>
                                                                postAction(
                                                                    'lecturer.exams.proctoring.events.apply-penalty',
                                                                    row.id,
                                                                )
                                                            }
                                                            className="rounded border border-rose-500/40 bg-rose-950/30 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-100 hover:bg-rose-900/40 disabled:opacity-40"
                                                        >
                                                            Apply
                                                        </button>
                                                        <button
                                                            type="button"
                                                            disabled={!examId}
                                                            onClick={() =>
                                                                postAction(
                                                                    'lecturer.exams.proctoring.events.ignore',
                                                                    row.id,
                                                                )
                                                            }
                                                            className="rounded border border-theme-line bg-theme-surface-soft/60 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-theme-muted hover:bg-theme-highlight/80 disabled:opacity-40"
                                                        >
                                                            Ignore
                                                        </button>
                                                        <button
                                                            type="button"
                                                            disabled={!examId}
                                                            onClick={() =>
                                                                postAction(
                                                                    'lecturer.exams.proctoring.events.dismiss',
                                                                    row.id,
                                                                )
                                                            }
                                                            className="rounded border border-emerald-600/40 bg-emerald-950/30 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-100 hover:bg-emerald-900/40 disabled:opacity-40"
                                                        >
                                                            Reviewed
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
