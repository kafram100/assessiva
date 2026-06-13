import GuestLayout from '@/Layouts/GuestLayout';
import InterventionTimeline from '@/Components/Intelligence/InterventionTimeline';
import PredictionBadge from '@/Components/Intelligence/PredictionBadge';
import RiskMeter from '@/Components/Intelligence/RiskMeter';
import { Head, Link } from '@inertiajs/react';

export default function Recommendations({ attempt, exam, twin, prediction }) {
    return (
        <GuestLayout>
            <Head title="Recommendations" />
            <div className="space-y-6">
                <h1 className="text-lg font-semibold text-theme-strong">Guidance</h1>
                <p className="text-sm text-theme-dim">{exam.title}</p>
                {twin ? (
                    <div>
                        <h2 className="text-xs font-semibold uppercase text-theme-dim">
                            From your learning twin
                        </h2>
                        <InterventionTimeline items={twin.recommended_actions || []} />
                    </div>
                ) : null}
                {prediction ? (
                    <div className="space-y-3 rounded-xl border border-theme-line bg-theme-glass p-4">
                        <h2 className="text-xs font-semibold uppercase text-theme-dim">
                            Advisory prediction
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            <PredictionBadge
                                label="Urgency"
                                value={prediction.intervention_urgency}
                            />
                            <PredictionBadge
                                label="Dropout"
                                value={prediction.dropout_risk}
                            />
                        </div>
                        <RiskMeter
                            value={prediction.failure_probability}
                            label="Pressure index"
                        />
                        <InterventionTimeline
                            items={prediction.recommended_interventions || []}
                        />
                        <p className="text-xs text-theme-dim">{prediction.advisory_rationale}</p>
                    </div>
                ) : (
                    <p className="text-sm text-theme-dim">
                        No prediction report on file yet.
                    </p>
                )}
                <Link
                    href={route('student.results.show', attempt.id)}
                    className="text-xs text-theme-dim hover:text-theme-muted"
                >
                    Back to result
                </Link>
            </div>
        </GuestLayout>
    );
}
