import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ exams }) {
    return (
        <LecturerLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        My exams
                    </h2>
                    <Link
                        href={route('lecturer.exams.create')}
                        className="inline-flex items-center rounded-md border border-transparent bg-theme-surface-soft px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-strong transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-theme-elevated"
                    >
                        Create exam
                    </Link>
                </div>
            }
        >
            <Head title="Exams" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden border border-theme-line bg-theme-glass shadow sm:rounded-md">
                        <ul className="divide-y divide-theme">
                            {exams.length === 0 && (
                                <li className="px-6 py-10 text-center text-sm text-theme-dim">
                                    No exams yet. Create your first exam to get
                                    started.
                                </li>
                            )}
                            {exams.map((exam) => (
                                <li key={exam.id}>
                                    <Link
                                        href={route(
                                            'lecturer.exams.show',
                                            exam.id,
                                        )}
                                        className="block px-6 py-4 hover:bg-theme-surface-soft/60"
                                    >
                                        <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p className="font-medium text-theme-strong">
                                                    {exam.title}
                                                </p>
                                                <p className="text-sm text-theme-dim">
                                                    {exam.course_name}
                                                    {exam.course_code
                                                        ? ` · ${exam.course_code}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                                <span className="rounded bg-theme-surface-soft px-2 py-1 font-mono text-theme-strong">
                                                    {exam.exam_code}
                                                </span>
                                                {exam.is_published ? (
                                                    <span className="rounded bg-green-100 px-2 py-1 text-green-800">
                                                        Published
                                                    </span>
                                                ) : (
                                                    <span className="rounded bg-amber-100 px-2 py-1 text-amber-900">
                                                        Draft
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </LecturerLayout>
    );
}
