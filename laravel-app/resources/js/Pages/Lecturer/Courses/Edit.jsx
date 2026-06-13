import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import LecturerLayout from '@/Layouts/LecturerLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ course, departments }) {
    const form = useForm({
        code: course.code ?? '',
        name: course.name ?? '',
        description: course.description ?? '',
        department_id: course.department_id ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('lecturer.courses.update', course.id));
    };

    return (
        <LecturerLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                        Edit course
                    </h2>
                    <Link
                        href={route('lecturer.courses.show', course.id)}
                        className="text-sm text-theme-dim underline hover:text-theme-strong"
                    >
                        Back to course
                    </Link>
                </div>
            }
        >
            <Head title={`Edit ${course.code}`} />

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
                                <option value="">— None —</option>
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
                                href={route('lecturer.courses.show', course.id)}
                                className="text-sm text-theme-dim underline hover:text-theme-muted"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton disabled={form.processing}>
                                Save changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </LecturerLayout>
    );
}
