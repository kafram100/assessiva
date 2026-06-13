import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link } from '@inertiajs/react';

export default function Monitor({
    sessions,
    recentLogs,
    recentFaceEvents,
    cheatSummary,
}) {
    const summaryEntries = Object.entries(cheatSummary ?? {});

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Proctoring monitor
                    </h2>
                    <Link
                        href={route('lecturer.proctoring.report')}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        Full report →
                    </Link>
                </div>
            }
        >
            <Head title="Proctoring" />

            <div className="space-y-8 py-8">
                <div className="mx-auto max-w-6xl sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <h3 className="text-base font-semibold text-theme-strong">
                            Live / flagged sessions
                        </h3>
                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b text-theme-dim">
                                        <th className="py-2 pr-4">Exam</th>
                                        <th className="py-2 pr-4">Student</th>
                                        <th className="py-2 pr-4">Status</th>
                                        <th className="py-2 pr-4">Started</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sessions.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="py-6 text-theme-dim"
                                            >
                                                No active proctoring sessions.
                                            </td>
                                        </tr>
                                    )}
                                    {sessions.map((s) => (
                                        <tr
                                            key={s.id}
                                            className="border-b border-theme-line"
                                        >
                                            <td className="py-2 pr-4">
                                                {s.attempt?.exam?.title ?? '—'}
                                            </td>
                                            <td className="py-2 pr-4 font-mono text-xs">
                                                {s.attempt?.student_index ?? '—'}
                                            </td>
                                            <td className="py-2 pr-4">
                                                {s.proctoring_status}
                                            </td>
                                            <td className="py-2 pr-4 text-theme-dim">
                                                {s.created_at
                                                    ? new Date(
                                                          s.created_at,
                                                      ).toLocaleString()
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-8 grid gap-6 lg:grid-cols-2">
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                            <h3 className="text-base font-semibold text-theme-strong">
                                Recent security logs
                            </h3>
                            <ul className="mt-3 max-h-80 space-y-2 overflow-y-auto text-xs text-theme-muted">
                                {recentLogs.map((log) => (
                                    <li
                                        key={log.id}
                                        className="rounded border border-theme-line p-2"
                                    >
                                        <span className="font-mono text-theme-dim">
                                            {log.timestamp
                                                ? new Date(
                                                      log.timestamp,
                                                  ).toLocaleString()
                                                : ''}
                                        </span>{' '}
                                        <span className="font-semibold">
                                            {log.activity_type}
                                        </span>
                                        <p className="mt-1 text-theme-dim">
                                            {log.description}
                                        </p>
                                    </li>
                                ))}
                                {recentLogs.length === 0 && (
                                    <li className="text-theme-dim">
                                        No security logs yet.
                                    </li>
                                )}
                            </ul>
                        </div>

                        <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                            <h3 className="text-base font-semibold text-theme-strong">
                                Face-related cheat events
                            </h3>
                            <ul className="mt-3 max-h-80 space-y-2 overflow-y-auto text-xs">
                                {recentFaceEvents.map((e) => (
                                    <li
                                        key={e.id}
                                        className="rounded border border-theme-line p-2"
                                    >
                                        <span className="font-semibold">
                                            {e.event_type}
                                        </span>{' '}
                                        <span className="text-theme-dim">
                                            {e.attempt?.student_index}
                                        </span>
                                        <p className="text-theme-dim">
                                            {e.created_at
                                                ? new Date(
                                                      e.created_at,
                                                  ).toLocaleString()
                                                : ''}
                                        </p>
                                    </li>
                                ))}
                                {recentFaceEvents.length === 0 && (
                                    <li className="text-theme-dim">
                                        No recent face events.
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>

                    <div className="mt-8 rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <h3 className="text-base font-semibold text-theme-strong">
                            Cheat event totals (all your attempts)
                        </h3>
                        {summaryEntries.length === 0 ? (
                            <p className="mt-2 text-sm text-theme-dim">
                                No cheat events recorded.
                            </p>
                        ) : (
                            <ul className="mt-3 flex flex-wrap gap-2 text-sm">
                                {summaryEntries.map(([type, n]) => (
                                    <li
                                        key={type}
                                        className="rounded-full bg-theme-surface-soft px-3 py-1"
                                    >
                                        <span className="font-medium">
                                            {type}
                                        </span>
                                        : {n}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
