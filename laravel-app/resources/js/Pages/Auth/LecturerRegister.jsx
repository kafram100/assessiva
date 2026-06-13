import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function LecturerRegister({ schools = [], departments = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        staff_id: '',
        phone: '',
        school_id: '',
        department_id: '',
        password: '',
        password_confirmation: '',
    });

    const filteredDepartments = useMemo(() => {
        if (!data.school_id) {
            return [];
        }
        const sid = String(data.school_id);
        return departments.filter(
            (d) => String(d.school_id) === sid,
        );
    }, [data.school_id, departments]);

    const submit = (e) => {
        e.preventDefault();
        post(route('lecturer.register.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Lecturer registration" />

            <div className="mb-6 text-center">
                <h1 className="text-lg font-semibold text-theme-strong">
                    Lecturer access request
                </h1>
                <p className="mt-2 text-sm text-theme-dim">
                    Submit your details for administrator approval. You will be
                    able to sign in only after your account is approved.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="name" value="Full name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="email"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="staff_id" value="Staff ID" />
                    <TextInput
                        id="staff_id"
                        value={data.staff_id}
                        className="mt-1 block w-full"
                        autoComplete="off"
                        onChange={(e) => setData('staff_id', e.target.value)}
                        required
                    />
                    <InputError message={errors.staff_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="phone" value="Phone number" />
                    <TextInput
                        id="phone"
                        type="tel"
                        value={data.phone}
                        className="mt-1 block w-full"
                        autoComplete="tel"
                        onChange={(e) => setData('phone', e.target.value)}
                        required
                    />
                    <InputError message={errors.phone} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="school_id" value="School / faculty" />
                    <select
                        id="school_id"
                        value={data.school_id}
                        onChange={(e) => {
                            setData('school_id', e.target.value);
                            setData('department_id', '');
                        }}
                        className="mt-1 block w-full rounded-md border border-theme-line bg-theme-glass px-3 py-2 text-sm text-theme-strong shadow-sm"
                        required
                    >
                        <option value="">Select school…</option>
                        {schools.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.code ? `${s.code} — ` : ''}
                                {s.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.school_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="department_id" value="Department" />
                    <select
                        id="department_id"
                        value={data.department_id}
                        disabled={!data.school_id}
                        onChange={(e) =>
                            setData('department_id', e.target.value)
                        }
                        className="mt-1 block w-full rounded-md border border-theme-line bg-theme-glass px-3 py-2 text-sm text-theme-strong shadow-sm disabled:opacity-50"
                        required
                    >
                        <option value="">
                            {data.school_id
                                ? 'Select department…'
                                : 'Choose a school first'}
                        </option>
                        {filteredDepartments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.code ? `${d.code} — ` : ''}
                                {d.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.department_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
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
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <Link
                        href={route('login')}
                        className="text-sm text-sky-400 underline decoration-sky-400/40 hover:text-sky-300"
                    >
                        Back to sign in
                    </Link>
                    <PrimaryButton disabled={processing}>
                        Submit request
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
