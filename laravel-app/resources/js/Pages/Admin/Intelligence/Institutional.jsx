import AnalyticsSummaryCard from '@/Components/Intelligence/AnalyticsSummaryCard';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

function Nav() {
    const c = 'rounded-md border border-theme-line px-2 py-1 text-xs text-theme-muted hover:bg-theme-surface-soft';
    return (
        <div className="flex flex-wrap gap-2">
            <Link href={route('admin.intelligence.institutional')} className={c}>
                Institutional
            </Link>
            <Link href={route('admin.intelligence.at-risk')} className={c}>
                At-risk
            </Link>
            <Link href={route('admin.intelligence.department')} className={c}>
                Department
            </Link>
            <Link href={route('admin.intelligence.cohort')} className={c}>
                Cohort
            </Link>
        </div>
    );
}

export default function Institutional({ reports }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Institutional intelligence
                    </h2>
                    <Nav />
                </div>
            }
        >
            <Head title="Institutional AI" />
            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-4 sm:px-6 lg:px-8">
                    {(reports || []).length === 0 ? (
                        <p className="text-sm text-theme-dim">
                            Generate cohort analytics from the Cohort view to populate
                            this stream.
                        </p>
                    ) : (
                        (reports || []).map((r) => (
                            <AnalyticsSummaryCard
                                key={r.id}
                                title={`${r.course_name || 'Course'} · health ${r.course_health_score ?? '-'}`}
                            >
                                <ul className="list-disc space-y-1 pl-4">
                                    {(r.summary || []).slice(0, 4).map((s, i) => (
                                        <li key={i}>{s}</li>
                                    ))}
                                </ul>
                                <p className="mt-2 text-xs text-theme-dim">
                                    {r.generated_at}
                                </p>
                            </AnalyticsSummaryCard>
                        ))
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
