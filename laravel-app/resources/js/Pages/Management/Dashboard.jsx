import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ recentExams, examCount }) {
    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Lecturer dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <div className="overflow-hidden border border-theme-line bg-theme-glass shadow sm:rounded-lg">
                        <div className="p-6">
                            <p className="text-sm text-theme-dim">
                                You have{' '}
                                <span className="font-semibold text-theme-strong">
                                    {examCount}
                                </span>{' '}
                                exam{examCount === 1 ? '' : 's'}.
                            </p>
                            <div className="mt-4 flex flex-wrap gap-3">
                                <Link
                                    href={route('admin.management.exams.create')}
                                    className="inline-flex items-center rounded-md border border-transparent bg-theme-surface-soft px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-theme-elevated"
                                >
                                    Create exam
                                </Link>
                                <Link href={route('admin.management.exams.index')}>
                                    <button
                                        type="button"
                                        className="inline-flex items-center rounded-md border border-theme-line bg-theme-surface-soft/60 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-muted shadow-sm transition duration-150 ease-in-out hover:bg-theme-highlight/80"
                                    >
                                        View all exams
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden border border-theme-line bg-theme-glass shadow sm:rounded-lg">
                        <div className="border-b border-theme-line px-6 py-4">
                            <h3 className="text-lg font-medium text-theme-strong">
                                Recent exams
                            </h3>
                        </div>
                        <ul className="divide-y divide-theme">
                            {recentExams.length === 0 && (
                                <li className="px-6 py-8 text-center text-sm text-theme-dim">
                                    No exams yet.
                                </li>
                            )}
                            {recentExams.map((exam) => (
                                <li key={exam.id}>
                                    <Link
                                        href={route(
                                            'lecturer.exams.show',
                                            exam.id,
                                        )}
                                        className="flex items-center justify-between px-6 py-3 hover:bg-theme-surface-soft/60"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-theme-strong">
                                                {exam.title}
                                            </p>
                                            <p className="text-xs text-theme-dim">
                                                {exam.course_name}
                                            </p>
                                        </div>
                                        <span className="font-mono text-xs text-theme-dim">
                                            {exam.exam_code}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
