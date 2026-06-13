import AnalyticsSummaryCard from '@/Components/Intelligence/AnalyticsSummaryCard';
import CohortTrendChart from '@/Components/Intelligence/CohortTrendChart';
import HodLayout from '@/Layouts/HodLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import WeakTopicPanel from '@/Components/Intelligence/WeakTopicPanel';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function Nav() {
    const c = 'rounded-md border border-theme-line px-2 py-1 text-xs text-theme-muted hover:bg-theme-surface-soft';
    return (
        <div className="flex flex-wrap gap-2">
            <Link href={route('hod.intelligence.institutional')} className={c}>
                Institutional
            </Link>
            <Link href={route('hod.intelligence.at-risk')} className={c}>
                At-risk
            </Link>
            <Link href={route('hod.intelligence.department')} className={c}>
                Department
            </Link>
            <Link href={route('hod.intelligence.cohort')} className={c}>
                Cohort
            </Link>
        </div>
    );
}

export default function Cohort({ courses, report, filters }) {
    const form = useForm({ course_id: filters.course_id ?? '' });
    const [submitting, setSubmitting] = useState(false);

    const load = (e) => {
        e.preventDefault();
        router.get(route('hod.intelligence.cohort'), {
            course_id: form.data.course_id || undefined,
        });
    };

    const generate = (e) => {
        e.preventDefault();
        if (!form.data.course_id) {
            return;
        }
        setSubmitting(true);
        router.post(
            route('hod.intelligence.analytics.generate'),
            { course_id: form.data.course_id },
            { onFinish: () => setSubmitting(false) },
        );
    };

    const trendItems = (report?.cohort_students_snapshot || []).map((s, i) => ({
        label: s.student_index || `Student ${i + 1}`,
        value: s.average_score ?? 0,
    }));

    return (
        <HodLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Cohort intelligence
                    </h2>
                    <Nav />
                </div>
            }
        >
            <Head title="HOD · Cohort" />
            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    <form
                        onSubmit={load}
                        className="flex flex-wrap items-end gap-3 rounded-2xl border border-theme-line bg-theme-glass p-6"
                    >
                        <select
                            className="rounded-md border-theme-line bg-theme-elevated px-3 py-2 text-sm text-theme-strong"
                            value={form.data.course_id}
                            onChange={(e) =>
                                form.setData('course_id', e.target.value)
                            }
                        >
                            <option value="">Select course…</option>
                            {(courses || []).map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            className="rounded-md border border-theme-line px-4 py-2 text-xs font-semibold uppercase text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Load
                        </button>
                        <PrimaryButton
                            type="button"
                            onClick={generate}
                            disabled={submitting || !form.data.course_id}
                        >
                            Generate
                        </PrimaryButton>
                    </form>

                    {report ? (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <AnalyticsSummaryCard title="Health">
                                <p className="text-4xl font-bold text-sky-300">
                                    {report.course_health_score}
                                </p>
                            </AnalyticsSummaryCard>
                            <AnalyticsSummaryCard title="Distribution">
                                <CohortTrendChart items={trendItems} />
                            </AnalyticsSummaryCard>
                            <AnalyticsSummaryCard title="Weak topics">
                                <WeakTopicPanel topics={report.weak_topics || []} />
                            </AnalyticsSummaryCard>
                            <AnalyticsSummaryCard title="Interventions">
                                <ul className="list-disc pl-4">
                                    {(report.interventions || []).map((x, i) => (
                                        <li key={i}>{x}</li>
                                    ))}
                                </ul>
                            </AnalyticsSummaryCard>
                        </div>
                    ) : (
                        <p className="text-sm text-theme-dim">
                            Select a course to view stored analytics.
                        </p>
                    )}
                </div>
            </div>
        </HodLayout>
    );
}
