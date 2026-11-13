import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ departments }) {
    const destroy = (id) => {
        if (confirm('Delete this department?')) {
            router.delete(route('admin.departments.destroy', id));
        }
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Departments
                    </h2>
                    <Link
                        href={route('admin.departments.create')}
                        className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                    >
                        Add department
                    </Link>
                </div>
            }
        >
            <Head title="Departments" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="border-b border-theme-line px-4 py-3 text-sm font-medium text-theme-muted">
                        Departments
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">School</th>
                                    <th className="px-4 py-3">Code</th>
                                    <th className="px-4 py-3">Users</th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {departments.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No departments yet.
                                        </td>
                                    </tr>
                                )}
                                {departments.map((d) => (
                                    <tr key={d.id} className="hover:bg-theme-elevated">
                                        <td className="px-4 py-3 font-medium text-theme-strong">
                                            {d.name}
                                        </td>
                                        <td className="px-4 py-3 text-theme-dim">
                                            {d.school?.name ?? '-'}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {d.code ?? '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {d.users_count}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={route(
                                                    'admin.departments.edit',
                                                    d.id,
                                                )}
                                                className="me-2 text-sky-400 hover:text-sky-300"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => destroy(d.id)}
                                                className="text-rose-400 hover:text-rose-300"
                                            >
                                                Delete
                                            </button>
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
