import AcademicHealthScoreCard from '@/Components/Intelligence/AcademicHealthScoreCard';
import AIRecommendationPanel from '@/Components/Intelligence/AIRecommendationPanel';
import AtRiskStudentsTable from '@/Components/Intelligence/AtRiskStudentsTable';
import CohortAnalyticsCard from '@/Components/Intelligence/CohortAnalyticsCard';
import EngagementTrendChart from '@/Components/Intelligence/EngagementTrendChart';
import ExportExcelButton from '@/Components/ExportExcelButton';
import FairnessTrendChart from '@/Components/Intelligence/FairnessTrendChart';
import WeakTopicPanel from '@/Components/Intelligence/WeakTopicPanel';
import HodLayout from '@/Layouts/HodLayout';
import { Head, Link } from '@inertiajs/react';

export default function HodAcademicIntelligenceDashboard({ department, intelligence }) {
    const h = intelligence?.academic_health ?? {};
    const fairnessTrend = intelligence?.fairness?.trend ?? [];

    return (
        <HodLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-theme-strong">
                            Academic intelligence
                        </h2>
                        <p className="text-sm text-theme-muted">
                            Department operating picture — aggregated from stored
                            twins, predictions, analytics, revision, and practice
                            signals.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2 text-xs">
                        <ExportExcelButton
                            href={route('hod.academic-intelligence.export')}
                            className="normal-case tracking-normal"
                        >
                            Department analytics (Excel)
                        </ExportExcelButton>
                        <Link
                            href={route('hod.intelligence.department')}
                            className="rounded-lg border border-theme-line bg-theme-highlight px-3 py-1.5 text-theme-strong hover:bg-theme-surface-soft"
                        >
                            Legacy analytics views
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="HOD — Academic intelligence" />

            <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div className="rounded-2xl border border-cyan-500/15 bg-theme-glass p-5 text-sm text-theme-muted shadow-[0_0_60px_-20px_rgba(34,211,238,0.35)] backdrop-blur-xl">
                    <p className="font-medium text-cyan-100/95">
                        {department?.school?.name ? (
                            <>
                                <span className="text-theme-dim">
                                    {department.school.name}
                                </span>
                                <span className="mx-2 text-slate-600">/</span>
                            </>
                        ) : null}
                        <span className="text-theme-strong">
                            {department?.name ?? 'Department not assigned'}
                        </span>
                    </p>
                    <p className="mt-2 max-w-3xl text-theme-dim">
                        This surface is read-only aggregation: it never re-runs AI
                        jobs. Encourage your team to refresh course intelligence in
                        the lecturer workspace so this mirror stays current.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <AcademicHealthScoreCard
                            title="Department academic health"
                            score={h.score}
                            label={h.label}
                            hint="Blended from latest cohort analytics, twin health scores where present, and summative pass rates in this department."
                        />
                    </div>
                    <div className="lg:col-span-3">
                        <CohortAnalyticsCard
                            title="Signal coverage"
                            items={[
                                {
                                    label: 'Learning twins (rows)',
                                    value: intelligence?.counts?.learning_twins ?? 0,
                                    hint: 'Stored twin profiles linked to your scope.',
                                },
                                {
                                    label: 'Prediction reports',
                                    value:
                                        intelligence?.counts?.prediction_reports ??
                                        0,
                                },
                                {
                                    label: 'Analytics reports (latest/course)',
                                    value:
                                        intelligence?.counts
                                            ?.analytics_reports_used ?? 0,
                                },
                                {
                                    label: 'Revision plans',
                                    value:
                                        intelligence?.counts?.revision_plans ?? 0,
                                },
                                {
                                    label: 'Practice attempts',
                                    value:
                                        intelligence?.counts?.practice_attempts ??
                                        0,
                                },
                                {
                                    label: 'Summative attempts (sample)',
                                    value:
                                        intelligence?.performance?.attempts ?? 0,
                                    hint: 'Completed exam attempts in scope (capped sample for performance).',
                                },
                            ]}
                        />
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <WeakTopicPanel
                        topics={intelligence?.weak_topics}
                        title="Weakest topics"
                        subtitle="Merged from analytics weak topics, twin weaknesses, revision plans, and practice hub misses."
                    />
                    <FairnessTrendChart
                        trend={fairnessTrend}
                        note={intelligence?.fairness?.note}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <EngagementTrendChart
                        data={intelligence?.practice_trend}
                        subtitle="Weekly practice completions from lecturers in your department."
                    />
                    <div className="space-y-4">
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-200/85">
                                Weakest courses (cohort health)
                            </h3>
                            <ul className="mt-3 space-y-2">
                                {(intelligence?.weakest_courses ?? []).length ===
                                0 ? (
                                    <li className="text-sm text-theme-dim">
                                        No per-course analytics rows yet.
                                    </li>
                                ) : (
                                    (intelligence?.weakest_courses ?? []).map(
                                        (c, i) => (
                                            <li
                                                key={i}
                                                className="flex items-center justify-between rounded-lg border border-theme-line bg-theme-highlight px-3 py-2 text-sm"
                                            >
                                                <span className="text-theme-muted">
                                                    {c.course ?? 'Course'}
                                                </span>
                                                <span className="font-mono text-xs text-cyan-300/90">
                                                    {c.health ?? '—'}
                                                </span>
                                            </li>
                                        ),
                                    )
                                )}
                            </ul>
                        </div>
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-200/85">
                                Practice hub — top attempts
                            </h3>
                            <ul className="mt-3 space-y-2">
                                {(
                                    intelligence?.practice_hub?.top_quizzes ??
                                    []
                                ).length === 0 ? (
                                    <li className="text-sm text-theme-dim">
                                        No practice completions recorded.
                                    </li>
                                ) : (
                                    (
                                        intelligence?.practice_hub
                                            ?.top_quizzes ?? []
                                    ).map((q, i) => (
                                        <li
                                            key={i}
                                            className="flex items-center justify-between rounded-lg border border-theme-line bg-theme-highlight px-3 py-2 text-sm text-theme-muted"
                                        >
                                            <span className="min-w-0 truncate">
                                                {q.title}
                                                <span className="block text-[0.7rem] text-theme-dim">
                                                    {q.course}
                                                </span>
                                            </span>
                                            <span className="shrink-0 font-mono text-xs text-emerald-300/90">
                                                {q.attempts}
                                            </span>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <AtRiskStudentsTable atRisk={intelligence?.at_risk} />
                    <div className="space-y-4">
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-200/85">
                                Lecturer support snapshot
                            </h3>
                            <p className="mt-1 text-xs text-theme-dim">
                                Averages are contextual only — not rankings. Use to
                                spot where extra departmental scaffolding may help.
                            </p>
                            <ul className="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                                {(intelligence?.lecturer_snapshots ?? [])
                                    .length === 0 ? (
                                    <li className="text-sm text-theme-dim">
                                        No lecturers in scope.
                                    </li>
                                ) : (
                                    (intelligence?.lecturer_snapshots ?? []).map(
                                        (L) => (
                                            <li
                                                key={L.lecturer_id}
                                                className="rounded-lg border border-theme-line bg-theme-highlight px-3 py-2 text-sm"
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="font-medium text-theme-strong">
                                                        {L.name}
                                                    </span>
                                                    <span
                                                        className={`text-[0.65rem] font-semibold uppercase ${
                                                            L.tone === 'positive'
                                                                ? 'text-emerald-300/90'
                                                                : L.tone ===
                                                                    'support'
                                                                  ? 'text-amber-200/90'
                                                                  : 'text-theme-dim'
                                                        }`}
                                                    >
                                                        {L.tone}
                                                    </span>
                                                </div>
                                                <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-theme-dim">
                                                    <span>
                                                        Avg course health:{' '}
                                                        <span className="font-mono text-theme-muted">
                                                            {L.avg_course_health ??
                                                                '—'}
                                                        </span>
                                                    </span>
                                                    <span>
                                                        Twin rows:{' '}
                                                        <span className="font-mono text-theme-muted">
                                                            {L.twin_generations}
                                                        </span>
                                                    </span>
                                                </div>
                                            </li>
                                        ),
                                    )
                                )}
                            </ul>
                        </div>
                        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 text-sm text-theme-muted backdrop-blur-xl">
                            <h3 className="text-sm font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-200/85">
                                Fairness intelligence
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-theme-dim">
                                Proxy score:{' '}
                                <span className="font-mono text-theme-muted">
                                    {intelligence?.fairness?.proxy_score ?? '—'}
                                </span>
                            </p>
                            <p className="mt-2 text-xs text-theme-dim">
                                Courses flagged for collegial review:{' '}
                                {(
                                    intelligence?.fairness
                                        ?.courses_needing_review ?? []
                                ).join(', ') || '—'}
                            </p>
                        </div>
                    </div>
                </div>

                <AIRecommendationPanel
                    lines={intelligence?.ai_recommendations}
                    title="Department AI recommendations"
                />

                <div className="rounded-xl border border-theme-line bg-theme-highlight p-4 text-center text-xs text-theme-muted">
                    Pass-rate sample:{' '}
                    <span className="font-mono text-theme-strong">
                        {intelligence?.performance?.pass_rate_percent ?? '—'}%
                    </span>{' '}
                    across completed attempts in this department&apos;s scope.
                </div>
            </div>
        </HodLayout>
    );
}
