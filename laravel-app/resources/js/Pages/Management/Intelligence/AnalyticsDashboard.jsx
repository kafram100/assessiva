import AnalyticsSummaryCard from '@/Components/Intelligence/AnalyticsSummaryCard';
import CohortTrendChart from '@/Components/Intelligence/CohortTrendChart';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import WeakTopicPanel from '@/Components/Intelligence/WeakTopicPanel';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function AnalyticsDashboard({ courses, report, filters }) {
    const lookup = useForm({
        course_id: filters.course_id ?? '',
    });

    const [submitting, setSubmitting] = useState(false);

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('admin.management.intelligence.analytics'), {
            course_id: lookup.data.course_id || undefined,
        });
    };

    const generate = (e) => {
        e.preventDefault();
        if (!lookup.data.course_id) {
            return;
        }
        setSubmitting(true);
        router.post(
            route('admin.management.intelligence.analytics.generate'),
            { course_id: lookup.data.course_id },
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const trendItems = (report?.cohort_trends || []).map((t, i) => ({
        label: typeof t === 'string' ? `Trend ${i + 1}` : t.label || `Item ${i + 1}`,
        value: typeof t === 'string' ? 50 + i * 5 : Number(t.score) || 40 + i * 5,
    }));

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-theme-strong">
                            Academic Analytics Intelligence
                        </h2>
                        <p className="text-sm text-theme-dim">
                            Cohort diagnostics stored per course for dashboards.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2 text-xs">
                        <Link
                            href={route('admin.management.intelligence.learning-twin')}
                            className="rounded-md border border-theme-line px-3 py-1 text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Learning Twin
                        </Link>
                        <Link
                            href={route('admin.management.intelligence.prediction')}
                            className="rounded-md border border-theme-line px-3 py-1 text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Predictions
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Analytics intelligence" />
            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-8 sm:px-6 lg:px-8">
                    <form
                        onSubmit={applyFilters}
                        className="flex flex-wrap items-end gap-4 rounded-2xl border border-theme-line bg-theme-glass p-6"
                    >
                        <div>
                            <label className="text-xs font-semibold uppercase text-theme-dim">
                                Course
                            </label>
                            <select
                                className="mt-1 block w-full min-w-[220px] rounded-md border-theme-line bg-theme-elevated text-sm text-theme-strong"
                                value={lookup.data.course_id}
                                onChange={(e) =>
                                    lookup.setData('course_id', e.target.value)
                                }
                            >
                                <option value="">Select…</option>
                                {(courses || []).map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <button
                            type="submit"
                            className="rounded-md border border-theme-line px-4 py-2 text-xs font-semibold uppercase tracking-wide text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Load stored report
                        </button>
                        <PrimaryButton
                            type="button"
                            onClick={generate}
                            disabled={submitting || !lookup.data.course_id}
                        >
                            Generate analytics
                        </PrimaryButton>
                    </form>

                    {!report ? (
                        <p className="text-sm text-theme-dim">
                            Pick a course and generate or load a stored analytics
                            report.
                        </p>
                    ) : (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <AnalyticsSummaryCard title="Course health">
                                <p className="text-4xl font-bold text-sky-300">
                                    {report.course_health_score ?? '-'}
                                </p>
                                <p className="mt-2 text-xs text-theme-dim">
                                    Generated {report.generated_at}
                                </p>
                            </AnalyticsSummaryCard>
                            <AnalyticsSummaryCard title="Cohort pulse">
                                <CohortTrendChart items={trendItems} />
                            </AnalyticsSummaryCard>
                            <AnalyticsSummaryCard title="Weak topics">
                                <WeakTopicPanel topics={report.weak_topics || []} />
                            </AnalyticsSummaryCard>
                            <AnalyticsSummaryCard title="Interventions">
                                <ul className="list-disc space-y-2 pl-4">
                                    {(report.interventions || []).map((x, i) => (
                                        <li key={i}>{x}</li>
                                    ))}
                                </ul>
                            </AnalyticsSummaryCard>
                            <div className="lg:col-span-2">
                                <AnalyticsSummaryCard title="Summary bullets">
                                    <ul className="list-disc space-y-2 pl-4">
                                        {(report.summary || []).map((x, i) => (
                                            <li key={i}>{x}</li>
                                        ))}
                                    </ul>
                                </AnalyticsSummaryCard>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
