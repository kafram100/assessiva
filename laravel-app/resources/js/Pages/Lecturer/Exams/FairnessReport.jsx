import ExportExcelButton from '@/Components/ExportExcelButton';
import PrimaryButton from '@/Components/PrimaryButton';
import LecturerLayout from '@/Layouts/LecturerLayout';
import { useChartTheme } from '@/lib/chartTheme';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const ACCENTS = ['#00a3ff', '#38bdf8', '#a78bfa', '#34d399', '#f472b6', '#fbbf24'];

function severityBadgeClass(sev) {
    const s = String(sev || '').toLowerCase();
    if (s === 'high') {
        return 'border-rose-400/40 bg-rose-500/15 text-rose-800 dark:text-rose-100';
    }
    if (s === 'low') {
        return 'border-sky-400/40 bg-sky-500/15 text-sky-800 dark:text-sky-100';
    }
    return 'border-amber-400/40 bg-amber-500/15 text-amber-900 dark:text-amber-100';
}

function toBarData(obj, labels) {
    if (!obj || typeof obj !== 'object') {
        return [];
    }
    return labels.map(([key, label]) => ({
        key,
        label,
        value: Number(obj[key]) || 0,
    }));
}

export default function FairnessReport({ exam, report }) {
    const { flash } = usePage().props;
    const ct = useChartTheme();

    const difficultyData = useMemo(
        () =>
            toBarData(report.difficulty_distribution, [
                ['easy', 'Easy'],
                ['medium', 'Medium'],
                ['hard', 'Hard'],
                ['very_hard', 'Very hard'],
            ]),
        [report.difficulty_distribution],
    );

    const bloomsData = useMemo(
        () =>
            toBarData(report.blooms_distribution, [
                ['remember', 'Remember'],
                ['understand', 'Understand'],
                ['apply', 'Apply'],
                ['analyze', 'Analyze'],
                ['evaluate', 'Evaluate'],
                ['create', 'Create'],
            ]),
        [report.blooms_distribution],
    );

    const durationData = useMemo(() => {
        const cfg = Number(exam.duration) || 0;
        const est = Number(report.estimated_total_duration_minutes) || 0;
        return [
            { label: 'Configured', minutes: cfg, fill: '#64748b' },
            { label: 'AI estimate', minutes: est, fill: '#00a3ff' },
        ];
    }, [exam.duration, report.estimated_total_duration_minutes]);

    const cognitive = report.cognitive_difficulty_map ?? [];
    const qAnalysis = report.question_analysis ?? [];
    const recs = report.recommendations ?? [];

    const reanalyze = () => {
        if (
            !confirm(
                'Re-run AI fairness analysis? This may take a minute and will replace the stored report.',
            )
        ) {
            return;
        }
        router.post(route('lecturer.exams.fairness.reanalyze', exam.id));
    };

    return (
        <LecturerLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                            Fairness intelligence
                        </h2>
                        <p className="text-sm text-theme-dim">{exam.title}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href={route('lecturer.exams.show', exam.id)}
                            className="inline-flex items-center rounded-md border border-theme-line bg-theme-highlight px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted shadow-sm transition hover:bg-theme-surface-soft"
                        >
                            Back to exam
                        </Link>
                        <ExportExcelButton
                            href={route(
                                'lecturer.exams.fairness.export',
                                exam.id,
                            )}
                        />
                        <PrimaryButton type="button" onClick={reanalyze}>
                            Re-analyze
                        </PrimaryButton>
                    </div>
                </div>
            }
        >
            <Head title={`Fairness — ${exam.title}`} />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {flash?.success ? (
                        <div className="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900 dark:text-emerald-100">
                            {flash.success}
                        </div>
                    ) : null}

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-6 shadow-inner backdrop-blur-xl md:col-span-1">
                            <p className="text-[0.65rem] font-semibold uppercase tracking-widest text-theme-dim">
                                Fairness score
                            </p>
                            <p className="mt-2 font-display text-5xl font-bold tabular-nums text-ai-accent">
                                {report.fairness_score ?? '—'}
                            </p>
                            <p className="mt-2 text-sm font-medium text-theme-strong">
                                {report.overall_status ?? '—'}
                            </p>
                            <p className="mt-3 text-xs text-theme-dim">
                                Last analyzed:{' '}
                                {report.analyzed_at
                                    ? new Date(report.analyzed_at).toLocaleString()
                                    : '—'}
                            </p>
                        </div>
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-6 shadow-inner backdrop-blur-xl md:col-span-2">
                            <p className="text-[0.65rem] font-semibold uppercase tracking-widest text-theme-dim">
                                Duration check
                            </p>
                            <div className="mt-3 flex flex-wrap items-end gap-6">
                                <div>
                                    <p className="text-xs text-theme-dim">Configured</p>
                                    <p className="text-2xl font-bold text-theme-strong">
                                        {exam.duration ?? 0}{' '}
                                        <span className="text-sm font-medium text-theme-muted">
                                            min
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-theme-dim">AI estimate</p>
                                    <p className="text-2xl font-bold text-ai-accent">
                                        {report.estimated_total_duration_minutes ?? '—'}{' '}
                                        <span className="text-sm font-medium text-theme-muted">
                                            min
                                        </span>
                                    </p>
                                </div>
                                <div className="rounded-full border border-theme-line bg-theme-highlight px-3 py-1 text-xs font-semibold text-theme-strong">
                                    {report.duration_status ?? '—'}
                                </div>
                            </div>
                            <div className="mt-6 h-48 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={durationData} layout="vertical" margin={{ left: 8 }}>
                                        <CartesianGrid strokeDasharray="4 8" stroke={ct.grid} horizontal={false} />
                                        <XAxis type="number" tick={{ fill: ct.tick, fontSize: 11 }} />
                                        <YAxis
                                            type="category"
                                            dataKey="label"
                                            width={100}
                                            tick={{ fill: ct.tick, fontSize: 12 }}
                                            axisLine={{ stroke: ct.axisLine }}
                                        />
                                        <Tooltip
                                            cursor={{ fill: ct.grid }}
                                            contentStyle={{
                                                background: ct.tooltipBg,
                                                border: `1px solid ${ct.tooltipBorder}`,
                                                borderRadius: 12,
                                                fontSize: 12,
                                                color: ct.tooltipItem,
                                            }}
                                            labelStyle={{ color: ct.tooltipLabel }}
                                        />
                                        <Bar dataKey="minutes" radius={[0, 6, 6, 0]}>
                                            {durationData.map((_, i) => (
                                                <Cell key={i} fill={ACCENTS[i % ACCENTS.length]} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-200/90">
                                Difficulty distribution
                            </h3>
                            <div className="mt-4 h-56">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={difficultyData}>
                                        <CartesianGrid strokeDasharray="4 8" stroke={ct.grid} vertical={false} />
                                        <XAxis dataKey="label" tick={{ fill: ct.tick, fontSize: 11 }} />
                                        <YAxis tick={{ fill: ct.tick, fontSize: 11 }} />
                                        <Tooltip
                                            contentStyle={{
                                                background: ct.tooltipBg,
                                                border: `1px solid ${ct.tooltipBorder}`,
                                                borderRadius: 12,
                                                fontSize: 12,
                                                color: ct.tooltipItem,
                                            }}
                                        />
                                        <Bar dataKey="value" radius={[6, 6, 0, 0]}>
                                            {difficultyData.map((_, i) => (
                                                <Cell key={i} fill={ACCENTS[i % ACCENTS.length]} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-violet-600 dark:text-violet-200/90">
                                Bloom taxonomy
                            </h3>
                            <div className="mt-4 h-56">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={bloomsData}>
                                        <CartesianGrid strokeDasharray="4 8" stroke={ct.grid} vertical={false} />
                                        <XAxis dataKey="label" tick={{ fill: ct.tick, fontSize: 10 }} interval={0} angle={-18} textAnchor="end" height={70} />
                                        <YAxis tick={{ fill: ct.tick, fontSize: 11 }} />
                                        <Tooltip
                                            contentStyle={{
                                                background: ct.tooltipBg,
                                                border: `1px solid ${ct.tooltipBorder}`,
                                                borderRadius: 12,
                                                fontSize: 12,
                                                color: ct.tooltipItem,
                                            }}
                                        />
                                        <Bar dataKey="value" radius={[6, 6, 0, 0]}>
                                            {bloomsData.map((_, i) => (
                                                <Cell key={i} fill={ACCENTS[(i + 2) % ACCENTS.length]} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                        <h3 className="text-sm font-semibold uppercase tracking-widest text-theme-muted">
                            Cognitive difficulty map
                        </h3>
                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full divide-y divide-theme text-left text-sm">
                                <thead className="bg-theme-highlight">
                                    <tr>
                                        <th className="px-3 py-2 font-semibold text-theme-strong">#</th>
                                        <th className="px-3 py-2 font-semibold text-theme-strong">Difficulty</th>
                                        <th className="px-3 py-2 font-semibold text-theme-strong">Bloom</th>
                                        <th className="px-3 py-2 font-semibold text-theme-strong">Est. min</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-theme text-theme-muted">
                                    {cognitive.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="px-3 py-4 text-theme-dim">
                                                No cognitive map entries.
                                            </td>
                                        </tr>
                                    ) : (
                                        cognitive.map((row, i) => (
                                            <tr key={i} className="hover:bg-theme-highlight/60">
                                                <td className="px-3 py-2 font-mono text-theme-strong">
                                                    {row.question_number}
                                                </td>
                                                <td className="px-3 py-2">{row.difficulty}</td>
                                                <td className="px-3 py-2">{row.blooms_level}</td>
                                                <td className="px-3 py-2 tabular-nums">
                                                    {row.estimated_time_minutes}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <h3 className="text-sm font-semibold uppercase tracking-widest text-theme-muted">
                            Question insights
                        </h3>
                        {qAnalysis.length === 0 ? (
                            <p className="text-sm text-theme-dim">No per-question analysis returned.</p>
                        ) : (
                            qAnalysis.map((q) => (
                                <details
                                    key={q.question_id ?? q.question_number}
                                    className="group rounded-xl border border-theme-line bg-theme-surface-soft/40 open:bg-theme-highlight/30"
                                >
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-theme-strong [&::-webkit-details-marker]:hidden">
                                        <span>
                                            Q{q.question_number}{' '}
                                            <span className="font-normal text-theme-muted">
                                                · {q.difficulty} · {q.blooms_level}
                                            </span>
                                        </span>
                                        <span className="rounded-full border border-theme-line px-2 py-0.5 text-[0.65rem] uppercase tracking-wide text-theme-dim group-open:rotate-0">
                                            {q.syllabus_alignment ?? 'Unknown'}
                                        </span>
                                    </summary>
                                    <div className="space-y-3 border-t border-theme-line px-4 py-4 text-sm text-theme-muted">
                                        <p className="whitespace-pre-wrap text-theme-strong/90">
                                            {q.question}
                                        </p>
                                        <div className="flex flex-wrap gap-2 text-[0.65rem] uppercase tracking-wide">
                                            {q.ambiguity_detected ? (
                                                <span className="rounded-full border border-amber-400/40 bg-amber-500/10 px-2 py-0.5 text-amber-900 dark:text-amber-100">
                                                    Ambiguity
                                                </span>
                                            ) : null}
                                            {q.excessive_difficulty ? (
                                                <span className="rounded-full border border-rose-400/40 bg-rose-500/10 px-2 py-0.5 text-rose-900 dark:text-rose-100">
                                                    Hard
                                                </span>
                                            ) : null}
                                            {q.repeated_pattern ? (
                                                <span className="rounded-full border border-violet-400/40 bg-violet-500/10 px-2 py-0.5 text-violet-900 dark:text-violet-100">
                                                    Repeat
                                                </span>
                                            ) : null}
                                            {q.bias_detected ? (
                                                <span className="rounded-full border border-orange-400/40 bg-orange-500/10 px-2 py-0.5 text-orange-900 dark:text-orange-100">
                                                    Bias risk
                                                </span>
                                            ) : null}
                                            {q.trick_question_detected ? (
                                                <span className="rounded-full border border-fuchsia-400/40 bg-fuchsia-500/10 px-2 py-0.5 text-fuchsia-900 dark:text-fuchsia-100">
                                                    Trick wording
                                                </span>
                                            ) : null}
                                        </div>
                                        {(q.issues ?? []).length > 0 ? (
                                            <ul className="space-y-2">
                                                {(q.issues ?? []).map((iss, j) => (
                                                    <li
                                                        key={j}
                                                        className="rounded-lg border border-theme-line bg-theme-glass p-3"
                                                    >
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span
                                                                className={`rounded-full border px-2 py-0.5 text-[0.65rem] font-semibold uppercase ${severityBadgeClass(iss.severity)}`}
                                                            >
                                                                {iss.severity}
                                                            </span>
                                                            <span className="text-xs font-semibold text-theme-strong">
                                                                {iss.type}
                                                            </span>
                                                        </div>
                                                        <p className="mt-2 text-theme-muted">{iss.message}</p>
                                                        {iss.suggestion ? (
                                                            <p className="mt-1 text-xs text-ai-accent">
                                                                → {iss.suggestion}
                                                            </p>
                                                        ) : null}
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p className="text-xs text-theme-dim">No flagged issues.</p>
                                        )}
                                        {q.improved_question ? (
                                            <div className="rounded-lg border border-ai-accent/30 bg-theme-highlight/80 p-3">
                                                <p className="text-[0.65rem] font-semibold uppercase tracking-wide text-theme-dim">
                                                    Suggested rewrite
                                                </p>
                                                <p className="mt-1 whitespace-pre-wrap text-theme-strong">
                                                    {q.improved_question}
                                                </p>
                                            </div>
                                        ) : null}
                                    </div>
                                </details>
                            ))
                        )}
                    </div>

                    {recs.length > 0 ? (
                        <div className="rounded-2xl border border-cyan-400/25 bg-gradient-to-br from-theme-glass to-theme-surface-soft p-6 dark:border-cyan-500/20">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-200/90">
                                Recommendations
                            </h3>
                            <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-theme-muted">
                                {recs.map((r, i) => (
                                    <li key={i}>{r}</li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </div>
            </div>
        </LecturerLayout>
    );
}
