import AcademicHealthCard from '@/Components/Intelligence/AcademicHealthCard';
import ExportExcelButton from '@/Components/ExportExcelButton';
import InterventionTimeline from '@/Components/Intelligence/InterventionTimeline';
import RiskMeter from '@/Components/Intelligence/RiskMeter';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function LearningTwinDashboard({
    courses,
    exams = [],
    twin,
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
        router.get(route('admin.management.intelligence.learning-twin'), {
            student_index: lookup.data.student_index,
            course_id: lookup.data.course_id || undefined,
        });
    };

    const generate = (e) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            route('admin.management.intelligence.learning-twin.generate'),
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
                            AI Learning Twin
                        </h2>
                        <p className="text-sm text-theme-dim">
                            Stored profile - regenerate only when cohort data
                            changes.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2 text-xs">
                        <Link
                            href={route('admin.management.intelligence.prediction')}
                            className="rounded-md border border-theme-line px-3 py-1 text-theme-muted hover:bg-theme-surface-soft"
                        >
                            Predictions
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
            <Head title="Learning Twin" />
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
                                Course scope (optional)
                            </label>
                            <select
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-elevated text-sm text-theme-strong"
                                value={lookup.data.course_id}
                                onChange={(e) =>
                                    lookup.setData('course_id', e.target.value)
                                }
                            >
                                <option value="">All my exams</option>
                                {(courses || []).map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex items-end gap-2">
                            <button
                                type="submit"
                                className="w-full rounded-md border border-theme-line px-4 py-2 text-xs font-semibold uppercase tracking-wide text-theme-muted hover:bg-theme-surface-soft"
                            >
                                Load stored twin
                            </button>
                        </div>
                    </form>

                    <div className="flex flex-wrap items-center gap-3">
                        <PrimaryButton
                            type="button"
                            onClick={generate}
                            disabled={submitting}
                        >
                            Generate / refresh twin
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
                                        'lecturer.exams.learning-twins.export',
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
                                    Pick an exam and index to export stored twin
                                    data.
                                </span>
                            )}
                        </div>
                        <span className="text-xs text-theme-dim">
                            Calls the AI service once and saves the result.
                        </span>
                    </div>

                    {!twin ? (
                        <p className="text-sm text-theme-dim">
                            No stored learning twin for this selection yet.
                        </p>
                    ) : (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <div className="space-y-4">
                                <AcademicHealthCard
                                    title="Academic health"
                                    score={twin.academic_health_score}
                                    subtitle={`Risk: ${twin.risk_level || '-'} · Next score outlook: ${twin.predicted_next_score ?? '-'}`}
                                    accent="sky"
                                />
                                <RiskMeter
                                    value={twin.academic_health_score}
                                    label="Health index"
                                />
                                <p className="text-sm leading-relaxed text-theme-muted">
                                    {twin.learning_pattern}
                                </p>
                            </div>
                            <div className="space-y-4">
                                <div className="rounded-2xl border border-theme-line bg-theme-glass p-4">
                                    <h3 className="text-xs font-semibold uppercase text-theme-dim">
                                        Strengths
                                    </h3>
                                    <ul className="mt-2 list-disc space-y-1 pl-4 text-sm text-emerald-800 dark:text-emerald-200">
                                        {(twin.strengths || []).map((s, i) => (
                                            <li key={i}>{s}</li>
                                        ))}
                                    </ul>
                                    <h3 className="mt-4 text-xs font-semibold uppercase text-theme-dim">
                                        Growth areas
                                    </h3>
                                    <ul className="mt-2 list-disc space-y-1 pl-4 text-sm text-rose-200">
                                        {(twin.weaknesses || []).map((s, i) => (
                                            <li key={i}>{s}</li>
                                        ))}
                                    </ul>
                                </div>
                                <div>
                                    <h3 className="text-xs font-semibold uppercase text-theme-dim">
                                        Revision focus
                                    </h3>
                                    <InterventionTimeline items={twin.revision_focus || []} />
                                </div>
                                <div>
                                    <h3 className="text-xs font-semibold uppercase text-theme-dim">
                                        Recommended actions
                                    </h3>
                                    <InterventionTimeline
                                        items={twin.recommended_actions || []}
                                    />
                                </div>
                                <p className="text-xs text-theme-dim">
                                    Lecturer note: {twin.lecturer_intervention}
                                </p>
                                <p className="text-xs text-slate-600">
                                    Generated {twin.generated_at}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
