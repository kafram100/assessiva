import AcademicHealthScoreCard from '@/Components/Intelligence/AcademicHealthScoreCard';
import AIRecommendationPanel from '@/Components/Intelligence/AIRecommendationPanel';
import CohortAnalyticsCard from '@/Components/Intelligence/CohortAnalyticsCard';
import DepartmentComparisonChart from '@/Components/Intelligence/DepartmentComparisonChart';
import EngagementTrendChart from '@/Components/Intelligence/EngagementTrendChart';
import RiskHeatmap from '@/Components/Intelligence/RiskHeatmap';
import WeakTopicPanel from '@/Components/Intelligence/WeakTopicPanel';
import AdminLayout from '@/Layouts/AdminLayout';
import ExportExcelButton from '@/Components/ExportExcelButton';
import { Head, Link } from '@inertiajs/react';

export default function AdminInstitutionalIntelligenceDashboard({
    departments,
    institution,
}) {
    const uh = institution?.university_academic_health ?? {};
    const heatRows = institution?.department_heatmap ?? [];
    const retention = institution?.retention_risk ?? {};
    const fairness = institution?.fairness ?? {};

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-1 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 className="text-lg font-semibold text-theme-strong">
                            Institutional intelligence
                        </h1>
                        <p className="text-sm text-theme-dim">
                            University-wide mirror of stored academic AI outputs —
                            no live model calls.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                        <Link
                            href={route('admin.intelligence.institutional')}
                            className="text-sm text-ai-accent hover:text-ai-accent-deep"
                        >
                            Open legacy institutional analytics →
                        </Link>
                        <ExportExcelButton
                            href={route(
                                'admin.institutional-intelligence.export',
                            )}
                            className="normal-case tracking-normal"
                        >
                            Export institutional (Excel)
                        </ExportExcelButton>
                    </div>
                </div>
            }
        >
            <Head title="Admin — Institutional intelligence" />

            <div className="mx-auto max-w-7xl space-y-8">
                <div className="rounded-2xl border border-cyan-500/20 bg-gradient-to-r from-theme-glass via-theme-surface-soft to-indigo-100/50 p-6 text-sm text-theme-muted shadow-[0_0_60px_-24px_rgba(34,211,238,0.2)] backdrop-blur-xl dark:from-slate-950/90 dark:via-slate-900/80 dark:to-indigo-950/80 dark:shadow-[0_0_80px_-24px_rgba(34,211,238,0.4)]">
                    <p className="max-w-3xl leading-relaxed">
                        This command deck aggregates{' '}
                        <span className="font-medium text-cyan-800 dark:text-cyan-200/95">
                            Learning Twin, Prediction Engine, Analytics
                            Intelligence, Revision, and Practice Hub
                        </span>{' '}
                        artefacts already persisted in the database. Use it for
                        supportive planning — never for punitive comparisons
                        between people.
                    </p>
                </div>

                <div className="grid gap-6 xl:grid-cols-5">
                    <div className="xl:col-span-2">
                        <AcademicHealthScoreCard
                            title="University academic health"
                            score={uh.score}
                            label={uh.label}
                            hint="Mean of department health indices where data exists — a compass, not a verdict."
                        />
                    </div>
                    <div className="xl:col-span-3">
                        <CohortAnalyticsCard
                            title="Institutional signal volume"
                            items={[
                                {
                                    label: 'Departments in view',
                                    value: institution?.counts?.departments ?? 0,
                                },
                                {
                                    label: 'High-tier risk signals',
                                    value:
                                        institution?.counts
                                            ?.high_tier_risk_signals ?? 0,
                                    hint: 'Rows aggregated from stored analytics + prediction outputs.',
                                },
                                {
                                    label: 'Medium-tier risk signals',
                                    value:
                                        institution?.counts
                                            ?.medium_tier_risk_signals ?? 0,
                                },
                                {
                                    label: 'Practice attempts (all depts.)',
                                    value:
                                        institution?.counts
                                            ?.practice_attempts_total ?? 0,
                                },
                                {
                                    label: 'Avg pass rate (dept. scopes)',
                                    value:
                                        institution?.cross_department_performance
                                            ?.avg_pass_rate_percent != null
                                            ? `${institution.cross_department_performance.avg_pass_rate_percent}%`
                                            : '—',
                                },
                                {
                                    label: 'Fairness proxy (mean)',
                                    value:
                                        fairness.avg_proxy_score != null
                                            ? fairness.avg_proxy_score
                                            : '—',
                                },
                            ]}
                        />
                    </div>
                </div>

                <RiskHeatmap rows={heatRows} />

                <div className="grid gap-6 lg:grid-cols-2">
                    <DepartmentComparisonChart
                        departments={institution?.weakest_departments}
                        subtitle="Lowest health indices — prioritise support, not blame."
                    />
                    <WeakTopicPanel
                        topics={institution?.university_weak_topics}
                        title="University weak topics"
                        subtitle="Cross-department aggregation of stored weak-topic signals."
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <EngagementTrendChart
                        data={institution?.practice_institution?.weekly_trend}
                        title="Institutional practice engagement"
                        subtitle="Summed weekly completions across all departments."
                    />
                    <div className="space-y-4">
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-rose-700 dark:text-rose-200/85">
                                Retention &amp; wellbeing risk (aggregated)
                            </h3>
                            {retention.note ? (
                                <p className="mt-2 text-xs leading-relaxed text-theme-dim">
                                    {retention.note}
                                </p>
                            ) : null}
                            <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                                <div className="rounded-xl border border-rose-400/25 bg-rose-50/90 px-4 py-3 dark:border-rose-500/20 dark:bg-rose-950/20">
                                    <dt className="text-[0.65rem] font-semibold uppercase tracking-wider text-rose-800 dark:text-rose-300/80">
                                        High tier
                                    </dt>
                                    <dd className="mt-1 text-3xl font-bold tabular-nums text-theme-strong">
                                        {retention.high_tier_signals ?? 0}
                                    </dd>
                                </div>
                                <div className="rounded-xl border border-amber-400/25 bg-amber-50/90 px-4 py-3 dark:border-amber-500/20 dark:bg-amber-950/20">
                                    <dt className="text-[0.65rem] font-semibold uppercase tracking-wider text-amber-900 dark:text-amber-200/80">
                                        Medium tier
                                    </dt>
                                    <dd className="mt-1 text-3xl font-bold tabular-nums text-theme-strong">
                                        {retention.medium_tier_signals ?? 0}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-violet-700 dark:text-violet-200/85">
                                Fairness intelligence (institution)
                            </h3>
                            {fairness.note ? (
                                <p className="mt-2 text-xs text-theme-dim">
                                    {fairness.note}
                                </p>
                            ) : null}
                            <p className="mt-3 text-sm text-theme-muted">
                                Departments with low fairness proxy:{' '}
                                <span className="text-theme-strong">
                                    {(fairness.departments_with_low_proxy ?? [])
                                        .length === 0
                                        ? '—'
                                        : fairness.departments_with_low_proxy.join(
                                              ', ',
                                          )}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <AIRecommendationPanel
                    lines={institution?.ai_recommendations}
                    title="Institutional AI recommendations"
                    subtitle="De-duplicated lines from stored analytics, twin, and prediction narratives across departments."
                />

                <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                    <h3 className="text-sm font-semibold uppercase tracking-widest text-theme-muted/90">
                        Per-department rollups (raw payload)
                    </h3>
                    <p className="mt-1 text-xs text-theme-dim">
                        For drill-down, pair with lecturer tools or export flows —
                        this table lists each department&apos;s blended health score.
                    </p>
                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead>
                                <tr className="text-[0.65rem] font-semibold uppercase tracking-wider text-theme-dim">
                                    <th className="pb-2 pr-3">Department</th>
                                    <th className="pb-2 pr-3">School</th>
                                    <th className="pb-2 pr-3">Health</th>
                                    <th className="pb-2 pr-3">Practice attempts</th>
                                    <th className="pb-2">Twins</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(departments ?? []).map((d) => (
                                    <tr
                                        key={d.department_id}
                                        className="border-t border-theme-line text-theme-muted"
                                    >
                                        <td className="py-2.5 pr-3 font-medium text-theme-strong">
                                            {d.department_name}
                                        </td>
                                        <td className="py-2.5 pr-3 text-theme-dim">
                                            {d.school_name ?? '—'}
                                        </td>
                                        <td className="py-2.5 pr-3 font-mono text-xs">
                                            {d.academic_health?.score ?? '—'}
                                        </td>
                                        <td className="py-2.5 pr-3 font-mono text-xs">
                                            {d.practice_hub?.total_attempts ?? 0}
                                        </td>
                                        <td className="py-2.5 font-mono text-xs">
                                            {d.counts?.learning_twins ?? 0}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
