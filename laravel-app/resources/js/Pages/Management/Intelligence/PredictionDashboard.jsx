import InterventionTimeline from '@/Components/Intelligence/InterventionTimeline';
import ExportExcelButton from '@/Components/ExportExcelButton';
import PredictionBadge from '@/Components/Intelligence/PredictionBadge';
import RiskMeter from '@/Components/Intelligence/RiskMeter';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function PredictionDashboard({
    courses,
    exams = [],
    prediction,
    filters,
}) {
    const lookup = useForm({
        student_index: filters.student_index || '',
        course_id: filters.course_id ?? '',
    });

    const [submitting, setSubmitting] = useState(false);
    const [exportExamId, setExportExamId] = useState('');

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('admin.management.intelligence.prediction'), {
            student_index: lookup.data.student_index,
            course_id: lookup.data.course_id || undefined,
        });
    };

    const generate = (e) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            route('admin.management.intelligence.prediction.generate'),
            {
                student_index: lookup.data.student_index,
                course_id: lookup.data.course_id || '',
            },
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-theme-strong">
                            AI Prediction Engine
                        </h2>
                        <p className="text-sm text-theme-dim">
                            Advisory risk model - each run appends a stored report.
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
                            href={route('admin.management.intelligence.analytics')}
                            className="rounded-md border border-theme-line px-3 py-1 text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Analytics
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Predictions" />
            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                    <form
                        onSubmit={applyFilters}
                        className="grid gap-4 rounded-2xl border border-theme-line bg-theme-glass p-6 md:grid-cols-3"
                    >
                        <div>
                            <label className="text-xs font-semibold uppercase text-theme-dim">
                                Index number
                            </label>
                            <TextInput
                                className="mt-1 block w-full"
                                value={lookup.data.student_index}
                                onChange={(e) =>
                                    lookup.setData('student_index', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-theme-dim">
                                Course (optional)
                            </label>
                            <select
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-elevated text-sm text-theme-strong"
                                value={lookup.data.course_id}
                                onChange={(e) =>
                                    lookup.setData('course_id', e.target.value)
                                }
                            >
                                <option value="">All</option>
                                {(courses || []).map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex items-end">
                            <button
                                type="submit"
                                className="w-full rounded-md border border-theme-line px-4 py-2 text-xs font-semibold uppercase tracking-wide text-theme-muted hover:bg-theme-surface-soft"
                            >
                                Load latest report
                            </button>
                        </div>
                    </form>

                    <div className="flex flex-wrap items-center gap-3">
                        <PrimaryButton
                            type="button"
                            onClick={generate}
                            disabled={submitting}
                        >
                            Run new prediction
                        </PrimaryButton>
                        <div className="flex flex-wrap items-end gap-2 rounded-xl border border-theme-line bg-theme-elevated/40 px-3 py-2">
                            <div>
                                <label className="block text-[0.65rem] font-semibold uppercase text-theme-dim">
                                    Exam for Excel export
                                </label>
                                <select
                                    className="mt-1 min-w-[12rem] rounded-md border-theme-line bg-theme-elevated text-sm text-theme-strong"
                                    value={exportExamId}
                                    onChange={(e) =>
                                        setExportExamId(e.target.value)
                                    }
                                >
                                    <option value="">Select exam…</option>
                                    {(exams || []).map((ex) => (
                                        <option key={ex.id} value={ex.id}>
                                            {ex.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {exportExamId &&
                            (lookup.data.student_index || '').trim() ? (
                                <ExportExcelButton
                                    href={route(
                                        'lecturer.exams.predictions.export',
                                        exportExamId,
                                    )}
                                    query={() => ({
                                        student_index:
                                            lookup.data.student_index,
                                        ...(lookup.data.course_id
                                            ? {
                                                  course_id:
                                                      lookup.data.course_id,
                                              }
                                            : {}),
                                    })}
                                />
                            ) : (
                                <span className="max-w-[10rem] text-[0.65rem] text-theme-dim">
                                    Pick an exam and index to export stored
                                    predictions.
                                </span>
                            )}
                        </div>
                    </div>

                    {!prediction ? (
                        <p className="text-sm text-theme-dim">
                            No prediction stored for this selection yet.
                        </p>
                    ) : (
                        <div className="space-y-6 rounded-2xl border border-theme-line bg-gradient-to-br from-theme-glass to-theme-surface-soft p-6 dark:from-slate-900/80 dark:to-[#0d1117]">
                            <div className="flex flex-wrap gap-2">
                                <PredictionBadge
                                    label="Dropout"
                                    value={prediction.dropout_risk}
                                />
                                <PredictionBadge
                                    label="Burnout"
                                    value={prediction.burnout_risk}
                                />
                                <PredictionBadge
                                    label="Urgency"
                                    value={prediction.intervention_urgency}
                                />
                                <span className="rounded-lg border border-theme-line px-2 py-1 text-xs font-semibold uppercase text-theme-muted">
                                    Grade outlook: {prediction.predicted_grade}
                                </span>
                            </div>
                            <RiskMeter
                                value={prediction.failure_probability}
                                label="Failure pressure index"
                            />
                            <p className="text-sm text-theme-muted">
                                {prediction.advisory_rationale}
                            </p>
                            <InterventionTimeline
                                items={prediction.recommended_interventions || []}
                            />
                            <p className="text-xs text-slate-600">
                                Generated {prediction.generated_at}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
