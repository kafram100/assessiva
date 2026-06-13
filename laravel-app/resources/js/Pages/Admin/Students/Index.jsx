import AdminPagination from '@/Components/AdminPagination';
import InputError from '@/Components/InputError';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

function fmt(dt) {
    if (!dt) {
        return '—';
    }
    return new Date(dt).toLocaleString();
}

function StudentEmailRow({ row }) {
    const { data, setData, put, processing, errors } = useForm({
        student_email: row.student_email ?? '',
    });

    const save = (e) => {
        e.preventDefault();
        put(
            route('admin.students.email.update', row.student_index),
            { preserveScroll: true },
        );
    };

    return (
        <tr className="hover:bg-theme-elevated">
            <td className="px-4 py-3 font-mono text-xs">{row.student_index}</td>
            <td className="px-4 py-3">{row.student_fullname ?? '—'}</td>
            <td className="px-4 py-3 text-center">{row.attempt_count}</td>
            <td className="whitespace-nowrap px-4 py-3 text-xs text-theme-dim">
                {fmt(row.last_attempt)}
            </td>
            <td className="px-4 py-3">
                <form
                    onSubmit={save}
                    className="flex flex-col gap-1 sm:flex-row sm:items-center"
                >
                    <input
                        type="email"
                        value={data.student_email}
                        onChange={(e) =>
                            setData('student_email', e.target.value)
                        }
                        className="w-full min-w-[12rem] rounded-md border border-theme-line bg-theme-surface-soft px-2 py-1 text-xs text-theme-strong sm:max-w-xs"
                        required
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded bg-sky-600 px-2 py-1 text-xs font-semibold text-white hover:bg-sky-500 disabled:opacity-50"
                    >
                        Save
                    </button>
                    <InputError message={errors.student_email} />
                </form>
            </td>
        </tr>
    );
}

export default function Index({ students, search }) {
    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    All students
                </h2>
            }
        >
            <Head title="Students" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.dashboard')}
                    className="mb-6 inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to admin
                </Link>

                <form
                    method="get"
                    action={route('admin.students')}
                    className="mb-6 flex flex-wrap items-end gap-3"
                >
                    <div>
                        <label className="block text-xs text-theme-dim">
                            Search
                        </label>
                        <input
                            type="text"
                            name="q"
                            defaultValue={search}
                            placeholder="Index, name, email…"
                            className="mt-1 rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong"
                        />
                    </div>
                    <button
                        type="submit"
                        className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                    >
                        Search
                    </button>
                </form>

                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Index</th>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3 text-center">Attempts</th>
                                    <th className="px-4 py-3">Last attempt</th>
                                    <th className="px-4 py-3">Email</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {students.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No students found.
                                        </td>
                                    </tr>
                                )}
                                {students.data.map((row) => (
                                    <StudentEmailRow
                                        key={row.student_index}
                                        row={row}
                                    />
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination links={students.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
