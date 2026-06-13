import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ departments = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        staff_id: '',
        phone: '',
        password: '',
        password_confirmation: '',
        department_id: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.hods.store'));
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold text-theme-strong">
                        Create Head of Department
                    </h2>
                    <Link
                        href={route('admin.hods.index')}
                        className="text-sm text-sky-400 underline hover:text-sky-300"
                    >
                        Back to HOD list
                    </Link>
                </div>
            }
        >
            <Head title="Create HOD" />

            <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-lg border border-theme-line bg-theme-glass p-6"
                >
                    <p className="text-sm text-theme-dim">
                        HOD accounts are created by administrators only. The
                        department must not already have an active HOD.
                    </p>

                    <div>
                        <InputLabel htmlFor="name" value="Full name" />
                        <TextInput
                            id="name"
                            value={data.name}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="username" value="Username" />
                        <TextInput
                            id="username"
                            value={data.username}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('username', e.target.value)}
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
                            className="mt-1 block w-full"
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="staff_id" value="Staff ID (optional)" />
                        <TextInput
                            id="staff_id"
                            value={data.staff_id}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('staff_id', e.target.value)}
                        />
                        <InputError message={errors.staff_id} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="phone" value="Phone (optional)" />
                        <TextInput
                            id="phone"
                            value={data.phone}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError message={errors.phone} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="department_id" value="Department" />
                        <select
                            id="department_id"
                            value={data.department_id}
                            onChange={(e) =>
                                setData('department_id', e.target.value)
                            }
                            className="mt-1 block w-full rounded-md border border-theme-line bg-theme-mesh px-3 py-2 text-sm text-theme-strong"
                            required
                        >
                            <option value="">Select department…</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                    {d.school?.name ? ` (${d.school.name})` : ''}
                                </option>
                            ))}
                        </select>
                        <InputError
                            message={errors.department_id}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="password" value="Password" />
                        <TextInput
                            id="password"
                            type="password"
                            value={data.password}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        <InputError message={errors.password} className="mt-2" />
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
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData(
                                    'password_confirmation',
                                    e.target.value,
                                )
                            }
                            required
                        />
                    </div>

                    <PrimaryButton disabled={processing} className="w-full sm:w-auto">
                        Create HOD account
                    </PrimaryButton>
                </form>
            </div>
        </AdminLayout>
    );
}
