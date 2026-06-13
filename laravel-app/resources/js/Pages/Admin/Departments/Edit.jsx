import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ department, schools }) {
    const { data, setData, put, processing, errors } = useForm({
        school_id: String(department.school_id),
        name: department.name ?? '',
        code: department.code ?? '',
        description: department.description ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.departments.update', department.id));
    };

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    Edit department
                </h2>
            }
        >
            <Head title="Edit department" />

            <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.departments')}
                    className="mb-6 inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to departments
                </Link>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-lg border border-theme-line bg-theme-glass p-6"
                >
                    <div>
                        <InputLabel htmlFor="school_id" value="School" />
                        <select
                            id="school_id"
                            className="mt-1 block w-full rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong"
                            value={data.school_id}
                            onChange={(e) =>
                                setData('school_id', e.target.value)
                            }
                            required
                        >
                            {schools.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                        <InputError
                            message={errors.school_id}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput
                            id="name"
                            value={data.name}
                            className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="code" value="Code (optional)" />
                        <TextInput
                            id="code"
                            value={data.code}
                            className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                            onChange={(e) => setData('code', e.target.value)}
                        />
                        <InputError message={errors.code} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="description"
                            value="Description (optional)"
                        />
                        <textarea
                            id="description"
                            value={data.description}
                            rows={3}
                            className="mt-1 block w-full rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.description}
                            className="mt-2"
                        />
                    </div>
                    <PrimaryButton disabled={processing}>Update</PrimaryButton>
                </form>
            </div>
        </AdminLayout>
    );
}
