import AdminLayout from '@/Layouts/AdminLayout';
import ExportExcelButton from '@/Components/ExportExcelButton';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FrequentCheaters({
    cheaters,
    limit,
    preset,
    studentIndex,
    studentName,
}) {
    const [local, setLocal] = useState({
        preset: String(preset ?? '20'),
        student_index: studentIndex ?? '',
        student_name: studentName ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        router.get(route('admin.frequent-cheaters'), local, {
            preserveState: true,
            replace: true,
        });
    };

    useEffect(() => {
        setLocal({
            preset: String(preset ?? '20'),
            student_index: studentIndex ?? '',
            student_name: studentName ?? '',
        });
    }, [preset, studentIndex, studentName]);

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    Frequent cheaters
                </h2>
            }
        >
            <Head title="Frequent cheaters" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.dashboard')}
                    className="mb-6 inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to admin
                </Link>

                <div className="mb-4 flex flex-wrap items-center gap-3">
                    <ExportExcelButton
                        href={route('admin.proctoring.export')}
                        className="normal-case tracking-normal"
                    >
                        Export proctoring activity (Excel)
                    </ExportExcelButton>
                </div>

                <form
                    onSubmit={submit}
                    className="mb-8 grid gap-4 rounded-lg border border-theme-line bg-theme-glass p-4 md:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <label className="block text-xs text-theme-dim">
                            Top N
                        </label>
                        <select
                            className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                            value={local.preset}
                            onChange={(e) =>
                                setLocal((p) => ({
                                    ...p,
                                    preset: e.target.value,
                                }))
                            }
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs text-theme-dim">
                            Student index
                        </label>
                        <input
                            type="text"
                            value={local.student_index}
                            onChange={(e) =>
                                setLocal((p) => ({
                                    ...p,
                                    student_index: e.target.value,
                                }))
                            }
                            className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                        />
                    </div>
                    <div>
                        <label className="block text-xs text-theme-dim">
                            Student name
                        </label>
                        <input
                            type="text"
                            value={local.student_name}
                            onChange={(e) =>
                                setLocal((p) => ({
                                    ...p,
                                    student_name: e.target.value,
                                }))
                            }
                            className="mt-1 w-full rounded-md border border-theme-line bg-theme-surface-soft px-2 py-2 text-sm text-theme-strong"
                        />
                    </div>
                    <div className="flex items-end">
                        <button
                            type="submit"
                            className="w-full rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                        >
                            Apply
                        </button>
                    </div>
                </form>

                <p className="mb-4 text-sm text-theme-dim">
                    Showing up to <strong className="text-theme-muted">{limit}</strong>{' '}
                    students by cheat event count (preset {preset}).
                </p>

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Index</th>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Level</th>
                                    <th className="px-4 py-3">Courses</th>
                                    <th className="px-4 py-3">Department</th>
                                    <th className="px-4 py-3">School</th>
                                    <th className="px-4 py-3 text-end">Events</th>
                                    <th className="px-4 py-3 text-end">Quizzes</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {cheaters.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No cheat events match these filters.
                                        </td>
                                    </tr>
                                )}
                                {cheaters.map((c, i) => (
                                    <tr key={i} className="hover:bg-theme-elevated">
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {c.student_index}
                                        </td>
                                        <td className="px-4 py-3">
                                            {c.student_fullname ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {c.student_level ?? '—'}
                                        </td>
                                        <td className="max-w-xs px-4 py-3 text-xs text-theme-dim">
                                            {c.courses ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {c.department_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {c.school_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-end font-medium text-amber-200">
                                            {c.cheat_count}
                                        </td>
                                        <td className="px-4 py-3 text-end text-xs">
                                            {c.quiz_count}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
