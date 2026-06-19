import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Analytics({ questions }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Question bank analytics
                    </h2>
                    <Link
                        href={route('admin.management.question-bank.index')}
                        className="text-sm font-medium text-sky-700 hover:text-sky-900"
                    >
                        ← Question bank
                    </Link>
                </div>
            }
        >
            <Head title="Bank analytics" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl overflow-x-auto sm:px-6 lg:px-8">
                    <table className="min-w-full divide-y divide-theme bg-theme-glass text-sm shadow">
                        <thead className="bg-theme-surface-soft/60">
                            <tr>
                                <th className="px-4 py-3 text-left font-semibold text-theme-muted">
                                    Question
                                </th>
                                <th className="px-4 py-3 text-left font-semibold text-theme-muted">
                                    Type
                                </th>
                                <th className="px-4 py-3 text-left font-semibold text-theme-muted">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-semibold text-theme-muted">
                                    Uses
                                </th>
                                <th className="px-4 py-3 text-right font-semibold text-theme-muted">
                                    Avg score
                                </th>
                                <th className="px-4 py-3 text-right font-semibold text-theme-muted">
                                    % correct
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-theme">
                            {(questions || []).map((q) => (
                                <tr key={q.id}>
                                    <td className="max-w-md px-4 py-3 text-theme-strong">
                                        {q.question_text?.slice(0, 120)}
                                        {(q.question_text?.length || 0) > 120
                                            ? '…'
                                            : ''}
                                    </td>
                                    <td className="px-4 py-3 text-theme-dim">
                                        {q.question_type}
                                    </td>
                                    <td className="px-4 py-3 text-theme-dim">
                                        {q.status}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums text-theme-muted">
                                        {q.usage_stats?.times_used ?? 0}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums text-theme-muted">
                                        {q.usage_stats?.average_score ?? '-'}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums text-theme-muted">
                                        {q.usage_stats?.correct_percentage ?? '-'}
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
