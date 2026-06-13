import AcademicHealthCard from '@/Components/Intelligence/AcademicHealthCard';
import GuestLayout from '@/Layouts/GuestLayout';
import InterventionTimeline from '@/Components/Intelligence/InterventionTimeline';
import RiskMeter from '@/Components/Intelligence/RiskMeter';
import { Head, Link } from '@inertiajs/react';

export default function LearningTwin({ attempt, exam, twin }) {
    return (
        <GuestLayout>
            <Head title="My Learning Twin" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-lg font-semibold text-theme-strong">Learning twin</h1>
                    <p className="text-sm text-theme-dim">{exam.title}</p>
                </div>
                {!twin ? (
                    <p className="text-sm text-theme-dim">
                        Your lecturer has not generated a learning twin for this
                        exam context yet.
                    </p>
                ) : (
                    <div className="space-y-4">
                        <AcademicHealthCard
                            title="Academic health"
                            score={twin.academic_health_score}
                            subtitle={`Risk: ${twin.risk_level || '—'}`}
                        />
                        <RiskMeter value={twin.academic_health_score} label="Health" />
                        <p className="text-sm text-theme-muted">{twin.learning_pattern}</p>
                        <InterventionTimeline items={twin.recommended_actions || []} />
                    </div>
                )}
                <div className="flex flex-wrap gap-3 text-xs">
                    <Link
                        href={route('student.intelligence.academic-health', attempt.id)}
                        className="text-sky-400 hover:text-sky-300"
                    >
                        Academic health
                    </Link>
                    <Link
                        href={route('student.intelligence.recommendations', attempt.id)}
                        className="text-sky-400 hover:text-sky-300"
                    >
                        Recommendations
                    </Link>
                    <Link
                        href={route('student.results.show', attempt.id)}
                        className="text-theme-dim hover:text-theme-muted"
                    >
                        Back to result
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}
