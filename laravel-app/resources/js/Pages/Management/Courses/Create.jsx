import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ departments, defaultDepartmentId }) {
    const form = useForm({
        code: '',
        name: '',
        description: '',
        department_id: defaultDepartmentId ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.management.courses.store'));
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Create course
                    </h2>
                    <Link
                        href={route('admin.management.courses.index')}
                        className="text-sm text-theme-dim underline hover:text-theme-strong"
                    >
                        Back to courses
                    </Link>
                </div>
            }
        >
            <Head title="Create course" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 rounded-lg border border-theme-line bg-theme-glass p-6 shadow"
                    >
                        <div>
                            <InputLabel htmlFor="code" value="Course code" />
                            <TextInput
                                id="code"
                                className="mt-1 block w-full font-mono"
                                value={form.data.code}
                                onChange={(e) =>
                                    form.setData('code', e.target.value)
                                }
                                required
                            />
                            <p className="mt-1 text-xs text-theme-dim">
                                Letters, numbers, dot, underscore, hyphen. Stored
                                uppercase.
                            </p>
                            <InputError
                                message={form.errors.code}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="name" value="Course name" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={form.errors.name}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="department_id"
                                value="Department (optional)"
                            />
                            <select
                                id="department_id"
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-elevated shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={form.data.department_id}
                                onChange={(e) =>
                                    form.setData(
                                        'department_id',
                                        e.target.value || '',
                                    )
                                }
                            >
                                <option value="">- None -</option>
                                {(departments ?? []).map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.school?.name
                                            ? `${d.school.name} · ${d.name}`
                                            : d.name}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={form.errors.department_id}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="description"
                                value="Description (optional)"
                            />
                            <textarea
                                id="description"
                                rows={4}
                                className="mt-1 block w-full rounded-md border-theme-line bg-theme-elevated shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={form.data.description}
                                onChange={(e) =>
                                    form.setData(
                                        'description',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.description}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link
                                href={route('admin.management.courses.index')}
                                className="text-sm text-theme-dim underline hover:text-theme-muted"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton disabled={form.processing}>
                                Save course
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
