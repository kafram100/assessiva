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

export default function Department({ reports }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Department analytics
                    </h2>
                    <Nav />
                </div>
            }
        >
            <Head title="Department AI" />
            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-4 sm:px-6 lg:px-8">
                    {(reports || []).map((r) => (
                        <AnalyticsSummaryCard
                            key={r.id}
                            title={`${r.department_name || 'Dept'} · ${r.course_name || ''}`}
                        >
                            <ul className="list-disc space-y-1 pl-4">
                                {(r.department_insights || []).map((s, i) => (
                                    <li key={i}>{s}</li>
                                ))}
                            </ul>
                        </AnalyticsSummaryCard>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
