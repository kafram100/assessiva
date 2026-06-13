import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ schools }) {
    const destroy = (id) => {
        if (
            confirm(
                'Delete this school and all its departments? This cannot be undone.',
            )
        ) {
            router.delete(route('admin.schools.destroy', id));
        }
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Schools
                    </h2>
                    <Link
                        href={route('admin.schools.create')}
                        className="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500"
                    >
                        Add school
                    </Link>
                </div>
            }
        >
            <Head title="Schools" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="overflow-hidden rounded-lg border border-theme-line bg-theme-glass">
                    <div className="border-b border-theme-line px-4 py-3 text-sm font-medium text-theme-muted">
                        University schools
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-theme text-sm">
                            <thead className="bg-theme-elevated text-left text-xs uppercase text-theme-dim">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Code</th>
                                    <th className="px-4 py-3">Departments</th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-theme text-theme-muted">
                                {schools.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-10 text-center text-theme-dim"
                                        >
                                            No schools yet. Add one to get started.
                                        </td>
                                    </tr>
                                )}
                                {schools.map((s) => (
                                    <tr key={s.id} className="hover:bg-theme-elevated">
                                        <td className="px-4 py-3 font-medium text-theme-strong">
                                            {s.name}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {s.code ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {s.departments_count}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={route(
                                                    'admin.schools.edit',
                                                    s.id,
                                                )}
                                                className="me-2 text-sky-400 hover:text-sky-300"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => destroy(s.id)}
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
