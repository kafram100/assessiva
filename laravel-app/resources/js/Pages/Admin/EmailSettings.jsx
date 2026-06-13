import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function EmailSettings({ settings }) {
    const { data, setData, post, processing, errors } = useForm({
        mail_mailer: settings.mail_mailer ?? 'smtp',
        mail_host: settings.mail_host ?? '',
        mail_port: settings.mail_port ?? '587',
        mail_encryption: settings.mail_encryption ?? '',
        mail_username: settings.mail_username ?? '',
        mail_password: settings.mail_password ?? '',
        mail_from_address: settings.mail_from_address ?? '',
        mail_from_name: settings.mail_from_name ?? '',
        mail_reply_to: settings.mail_reply_to ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.email-settings.update'));
    };

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold text-theme-strong">
                    Email settings
                </h2>
            }
        >
            <Head title="Email settings" />

            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.dashboard')}
                    className="mb-6 inline-block text-sm text-theme-dim hover:text-theme-strong"
                >
                    ← Back to admin
                </Link>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-lg border border-theme-line bg-theme-glass p-6"
                >
                    <p className="text-sm text-theme-dim">
                        Shared SMTP sender used when the platform sends mail on
                        behalf of staff.
                    </p>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="mail_mailer" value="Mailer" />
                            <TextInput
                                id="mail_mailer"
                                value={data.mail_mailer}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_mailer', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.mail_mailer}
                                className="mt-2"
                            />
                        </div>
                        <div className="md:col-span-2">
                            <InputLabel htmlFor="mail_host" value="SMTP host" />
                            <TextInput
                                id="mail_host"
                                value={data.mail_host}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_host', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.mail_host}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="mail_port" value="Port" />
                            <TextInput
                                id="mail_port"
                                type="number"
                                value={data.mail_port}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_port', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.mail_port}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="mail_encryption"
                                value="Encryption (tls / ssl / empty)"
                            />
                            <TextInput
                                id="mail_encryption"
                                value={data.mail_encryption}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData(
                                        'mail_encryption',
                                        e.target.value,
                                    )
                                }
                                placeholder="tls"
                            />
                            <InputError
                                message={errors.mail_encryption}
                                className="mt-2"
                            />
                        </div>
                        <div className="md:col-span-2">
                            <InputLabel
                                htmlFor="mail_username"
                                value="SMTP username"
                            />
                            <TextInput
                                id="mail_username"
                                value={data.mail_username}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_username', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.mail_username}
                                className="mt-2"
                            />
                        </div>
                        <div className="md:col-span-2">
                            <InputLabel
                                htmlFor="mail_password"
                                value="SMTP password (leave blank to keep stored value)"
                            />
                            <TextInput
                                id="mail_password"
                                type="password"
                                value={data.mail_password}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_password', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.mail_password}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="mail_from_address"
                                value="From address"
                            />
                            <TextInput
                                id="mail_from_address"
                                type="email"
                                value={data.mail_from_address}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData(
                                        'mail_from_address',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={errors.mail_from_address}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="mail_from_name"
                                value="From name"
                            />
                            <TextInput
                                id="mail_from_name"
                                value={data.mail_from_name}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_from_name', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.mail_from_name}
                                className="mt-2"
                            />
                        </div>
                        <div className="md:col-span-2">
                            <InputLabel
                                htmlFor="mail_reply_to"
                                value="Reply-To (optional)"
                            />
                            <TextInput
                                id="mail_reply_to"
                                type="email"
                                value={data.mail_reply_to}
                                className="mt-1 block w-full border-theme-line bg-theme-surface-soft text-theme-strong"
                                onChange={(e) =>
                                    setData('mail_reply_to', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.mail_reply_to}
                                className="mt-2"
                            />
                        </div>
                    </div>

                    <PrimaryButton disabled={processing}>
                        Save email settings
                    </PrimaryButton>
                </form>
            </div>
        </AdminLayout>
    );
}
