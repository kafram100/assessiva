import AcademicHealthCard from '@/Components/Intelligence/AcademicHealthCard';
import GuestLayout from '@/Layouts/GuestLayout';
import RiskMeter from '@/Components/Intelligence/RiskMeter';
import { Head, Link } from '@inertiajs/react';

export default function AcademicHealth({ attempt, exam, twin }) {
    return (
        <GuestLayout>
            <Head title="My Academic Health" />
            <div className="space-y-6">
                <h1 className="text-lg font-semibold text-theme-strong">Academic health</h1>
                <p className="text-sm text-theme-dim">{exam.title}</p>
                {!twin ? (
                    <p className="text-sm text-theme-dim">No twin data available.</p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <AcademicHealthCard
                            title="Health score"
                            score={twin.academic_health_score}
                            accent="emerald"
                        />
                        <AcademicHealthCard
                            title="Next outlook"
                            score={twin.predicted_next_score}
                            subtitle="Predicted next performance (advisory)"
                            accent="amber"
                        />
                        <div className="sm:col-span-2">
                            <RiskMeter
                                value={twin.academic_health_score}
                                label="Composite"
                            />
                        </div>
                    </div>
                )}
                <Link
                    href={route('student.intelligence.learning-twin', attempt.id)}
                    className="text-xs text-sky-400 hover:text-sky-300"
                >
                    ← Full twin profile
                </Link>
            </div>
        </GuestLayout>
    );
}
