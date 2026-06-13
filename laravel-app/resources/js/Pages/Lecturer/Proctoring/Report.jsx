import ExportExcelButton from '@/Components/ExportExcelButton';
import CheatEventReviewPanel from '@/Components/Proctoring/CheatEventReviewPanel';
import LecturerLayout from '@/Layouts/LecturerLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router } from '@inertiajs/react';

function LogList({ title, grouped }) {
    const entries = Object.entries(grouped ?? {});
    return (
        <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
            <h3 className="text-base font-semibold text-theme-strong">{title}</h3>
            {entries.length === 0 ? (
                <p className="mt-2 text-sm text-theme-dim">No rows.</p>
            ) : (
                <div className="mt-4 max-h-[480px] space-y-6 overflow-y-auto text-sm">
                    {entries.map(([index, rows]) => (
                        <div key={index}>
                            <p className="font-mono text-xs font-bold text-theme-dim">
                                {index}
                            </p>
                            <ul className="mt-2 space-y-2">
                                {(rows ?? []).map((row) => (
                                    <li
                                        key={row.id}
                                        className="rounded border border-theme-line p-2 text-xs text-theme-muted"
                                    >
                                        <span className="text-theme-dim">
                                            {row.timestamp || row.created_at
                                                ? new Date(
                                                      row.timestamp ||
                                                          row.created_at,
                                                  ).toLocaleString()
                                                : ''}
                                        </span>{' '}
                                        <span className="font-semibold">
                                            {row.activity_type ||
                                                row.event_type}
                                        </span>{' '}
                                        <span className="text-theme-dim">
                                            {row.severity}
                                        </span>
                                        {row.description && (
                                            <p className="mt-1 text-theme-dim">
                                                {row.description}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Report({
    exams,
    logs,
    cheatEvents,
    incidents,
    filters,
    eventTypeOptions,
    resultsCapped,
    maxRows,
}) {
    const submit = (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const params = {};
        fd.forEach((v, k) => {
            if (v !== '') {
                params[k] = v;
            }
        });
        router.get(route('lecturer.proctoring.report'), params, {
            preserveState: true,
        });
    };

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Proctoring report
                    </h2>
                    <Link
                        href={route('lecturer.proctoring.monitor')}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Live monitor
                    </Link>
                </div>
            }
        >
            <Head title="Proctoring report" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow"
                    >
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    Exam
                                </label>
                                <select
                                    name="exam_id"
                                    defaultValue={filters.exam_id ?? ''}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                >
                                    <option value="">All exams</option>
                                    {exams.map((ex) => (
                                        <option key={ex.id} value={ex.id}>
                                            {ex.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    Severity
                                </label>
                                <select
                                    name="severity"
                                    defaultValue={filters.severity ?? ''}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                >
                                    <option value="">Any</option>
                                    <option value="low">low</option>
                                    <option value="medium">medium</option>
                                    <option value="high">high</option>
                                    <option value="critical">critical</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    Event type
                                </label>
                                <select
                                    name="event_type"
                                    defaultValue={filters.event_type ?? ''}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                >
                                    <option value="">Any</option>
                                    {eventTypeOptions.map((t) => (
                                        <option key={t} value={t}>
                                            {t}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    Student index
                                </label>
                                <input
                                    name="student_index"
                                    type="text"
                                    defaultValue={filters.student_index ?? ''}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    Date range
                                </label>
                                <select
                                    name="date_range"
                                    defaultValue={filters.date_range ?? '7d'}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                >
                                    <option value="today">Today</option>
                                    <option value="7d">Last 7 days</option>
                                    <option value="30d">Last 30 days</option>
                                    <option value="all">All time</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    Start (custom)
                                </label>
                                <input
                                    name="start_date"
                                    type="date"
                                    defaultValue={filters.start_date ?? ''}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-theme-dim">
                                    End (custom)
                                </label>
                                <input
                                    name="end_date"
                                    type="date"
                                    defaultValue={filters.end_date ?? ''}
                                    className="mt-1 block w-full rounded-md border-theme-line text-sm"
                                />
                            </div>
                        </div>
                        <div className="mt-4 flex flex-wrap items-center gap-3">
                            <PrimaryButton type="submit">
                                Apply filters
                            </PrimaryButton>
                            {filters.exam_id ? (
                                <Link
                                    href={route(
                                        'lecturer.exams.proctoring.penalties.index',
                                        filters.exam_id,
                                    )}
                                    className="text-sm font-semibold text-sky-700 hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-200"
                                >
                                    Configure penalty rules →
                                </Link>
                            ) : null}
                            <ExportExcelButton
                                href={route('lecturer.proctoring.export')}
                                query={() => ({
                                    exam_id: filters.exam_id || '',
                                    severity: filters.severity || '',
                                    event_type: filters.event_type || '',
                                    student_index:
                                        filters.student_index || '',
                                    date_range: filters.date_range || '7d',
                                    start_date: filters.start_date || '',
                                    end_date: filters.end_date || '',
                                })}
                                className="normal-case tracking-normal"
                            />
                        </div>
                    </form>

                    {resultsCapped && (
                        <p className="text-center text-xs text-amber-800">
                            Showing up to {maxRows} rows per section (broad
                            search). Narrow by exam or student index for more
                            rows.
                        </p>
                    )}

                    <div className="grid gap-6 lg:grid-cols-2">
                        <LogList title="Activity logs" grouped={logs} />
                        <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                            <h3 className="text-base font-semibold text-theme-strong">
                                Integrity review
                            </h3>
                            <p className="mt-2 text-sm text-theme-dim">
                                Suspicious activities are not treated as cheating
                                until you review them and choose to apply configured
                                penalties.
                            </p>
                            {filters.exam_id ? (
                                <Link
                                    href={route(
                                        'lecturer.exams.proctoring.penalties.index',
                                        filters.exam_id,
                                    )}
                                    className="mt-4 inline-flex text-sm font-semibold text-sky-700 hover:text-sky-900 dark:text-sky-400"
                                >
                                    Open penalty rules for this exam →
                                </Link>
                            ) : (
                                <p className="mt-4 text-xs text-amber-800 dark:text-amber-300">
                                    Select a specific exam above to jump to its
                                    penalty settings and enable row actions in the
                                    table below.
                                </p>
                            )}
                        </div>
                    </div>

                    <CheatEventReviewPanel
                        grouped={cheatEvents}
                        examId={filters.exam_id}
                    />

                    <div className="rounded-lg border border-theme-line bg-theme-glass p-6 shadow">
                        <h3 className="text-base font-semibold text-theme-strong">
                            Cheating incidents
                        </h3>
                        <div className="mt-4 max-h-96 overflow-y-auto">
                            <table className="min-w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b text-theme-dim">
                                        <th className="py-2 pr-2">When</th>
                                        <th className="py-2 pr-2">Index</th>
                                        <th className="py-2 pr-2">Type</th>
                                        <th className="py-2 pr-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(incidents ?? []).map((i) => (
                                        <tr
                                            key={i.id}
                                            className="border-b border-theme-line"
                                        >
                                            <td className="py-2 pr-2 text-theme-dim">
                                                {i.incident_timestamp
                                                    ? new Date(
                                                          i.incident_timestamp,
                                                      ).toLocaleString()
                                                    : '—'}
                                            </td>
                                            <td className="py-2 pr-2 font-mono">
                                                {i.attempt?.student_index ??
                                                    '—'}
                                            </td>
                                            <td className="py-2 pr-2">
                                                {i.incident_type}
                                            </td>
                                            <td className="py-2 pr-2">
                                                {i.status}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {incidents?.length === 0 && (
                                <p className="text-sm text-theme-dim">
                                    No incidents in this window.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
