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

export default function AtRisk({ atRiskRows }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        At-risk signals
                    </h2>
                    <Nav />
                </div>
            }
        >
            <Head title="At-risk" />
            <div className="py-8">
                <div className="mx-auto max-w-6xl overflow-x-auto sm:px-6 lg:px-8">
                    <table className="min-w-full text-left text-sm text-theme-muted">
                        <thead className="text-xs uppercase text-theme-dim">
                            <tr>
                                <th className="px-3 py-2">Course</th>
                                <th className="px-3 py-2">Reason</th>
                                <th className="px-3 py-2">Suggested</th>
                                <th className="px-3 py-2">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(atRiskRows || []).map((row, i) => (
                                <tr key={i} className="border-t border-theme-line">
                                    <td className="px-3 py-2">{row.course}</td>
                                    <td className="px-3 py-2">{row.reason}</td>
                                    <td className="px-3 py-2">{row.suggested_action}</td>
                                    <td className="px-3 py-2 text-xs text-theme-dim">
                                        {row.generated_at}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
