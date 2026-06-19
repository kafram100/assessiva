import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ quizzes }) {
    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-xl font-semibold text-theme-strong">Practice quizzes</h2>
                    <Link
                        href={route('admin.management.practice-quizzes.create')}
                        className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                    >
                        New practice quiz
                    </Link>
                </div>
            }
        >
            <Head title="Practice quizzes" />
            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="overflow-x-auto border border-theme-line bg-theme-glass shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-glass text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Title</th>
                                    <th className="px-4 py-3">Course</th>
                                    <th className="px-4 py-3">Public</th>
                                    <th className="px-4 py-3">Attempts</th>
                                    <th className="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {quizzes.map((q) => (
                                    <tr key={q.id}>
                                        <td className="px-4 py-3 font-medium text-theme-strong">{q.title}</td>
                                        <td className="px-4 py-3">{q.course_name}</td>
                                        <td className="px-4 py-3">
                                            {q.is_public_practice && q.is_published ? (
                                                <span className="text-emerald-400">Live on hub</span>
                                            ) : (
                                                <span className="text-theme-dim">Draft / private</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">{q.attempts_count ?? 0}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route('admin.management.practice-quizzes.edit', q.id)}
                                                className="text-sky-400 hover:text-sky-300"
                                            >
                                                Manage
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {quizzes.length === 0 ? (
                            <p className="p-4 sm:p-8 text-center text-theme-dim">No practice quizzes yet.</p>
                        ) : null}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
