import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ departments }) {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'lecturer',
        department_id: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.lecturers.store'));
    };

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    Add staff
                </h2>
            }
        >
            <Head title="Add staff" />

            <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.lecturers')}
                    className="mb-6 inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to staff
                </Link>

                {departments.length === 0 ? (
                    <div className="rounded-lg border border-amber-800 bg-amber-950/30 p-6 text-sm text-amber-100">
                        Create at least one department before adding staff.
                        <Link
                            href={route('admin.departments.create')}
                            className="ms-2 font-semibold text-sky-400 hover:text-sky-300"
                        >
                            Add department
                        </Link>
                    </div>
                ) : (
                    <form
                    onSubmit={submit}
                    className="space-y-4 rounded-lg border border-theme-line bg-theme-glass p-6"
                >
                    <div>
                        <InputLabel htmlFor="username" value="Username" />
                        <TextInput
                            id="username"
                            value={data.username}
                            className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                            autoComplete="username"
                            onChange={(e) =>
                                setData('username', e.target.value)
                            }
                            required
                        />
                        <InputError
                            message={errors.username}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="password" value="Password" />
                        <TextInput
                            id="password"
                            type="password"
                            value={data.password}
                            className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            required
                        />
                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="password_confirmation"
                            value="Confirm password"
                        />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                            onChange={(e) =>
                                setData(
                                    'password_confirmation',
                                    e.target.value,
                                )
                            }
                            required
                        />
                    </div>
                    <div>
                        <InputLabel htmlFor="role" value="Role" />
                        <select
                            id="role"
                            className="mt-1 block w-full rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                        >
                            <option value="lecturer">Lecturer</option>
                        </select>
                        <InputError message={errors.role} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="department_id"
                            value="Department (optional)"
                        />
                        <select
                            id="department_id"
                            className="mt-1 block w-full rounded-md border border-theme-line bg-theme-surface-soft px-3 py-2 text-sm text-theme-strong"
                            value={data.department_id}
                            onChange={(e) =>
                                setData('department_id', e.target.value)
                            }
                        >
                            <option value="">-</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                    {d.school ? ` - ${d.school.name}` : ''}
                                </option>
                            ))}
                        </select>
                        <InputError
                            message={errors.department_id}
                            className="mt-2"
                        />
                    </div>
                    <PrimaryButton disabled={processing}>
                        Create account
                    </PrimaryButton>
                </form>
                )}
            </div>
        </AdminLayout>
    );
}
