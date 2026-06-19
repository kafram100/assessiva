import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function Row({ label, value, sensitive }) {
    return (
        <div className="border-b border-theme-line py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm font-medium text-theme-dim">{label}</dt>
            <dd className="mt-1 font-mono text-sm text-theme-strong sm:col-span-2 sm:mt-0">
                {sensitive && value
                    ? '•••••••• (configured)'
                    : value || (
                          <span className="text-theme-dim">(not set)</span>
                      )}
            </dd>
        </div>
    );
}

export default function Email({ settings }) {
    const user = usePage().props.auth.user;
    const isAdmin = user?.role === 'admin';

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-theme-strong">
                    Email delivery (read-only)
                </h2>
            }
        >
            <Head title="Email settings" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden border border-theme-line bg-theme-glass shadow sm:rounded-lg">
                        <div className="border-b border-theme-line px-6 py-4">
                            <p className="text-sm text-theme-dim">
                                These values are shared from the system mail
                                configuration (same keys as the admin mail
                                settings). Lecturers cannot edit them here.
                            </p>
                            <p className="mt-2 text-sm text-theme-dim">
                                {isAdmin ? (
                                    <>
                                        Admins can change them under{' '}
                                        <Link
                                            href={route(
                                                'admin.email-settings',
                                            )}
                                            className="font-medium text-sky-700 hover:text-sky-900"
                                        >
                                            Admin → Email settings
                                        </Link>
                                        .
                                    </>
                                ) : (
                                    'Contact an administrator if these need to change.'
                                )}
                            </p>
                        </div>
                        <dl className="divide-y divide-theme px-6">
                            <Row label="Mailer" value={settings.mail_mailer} />
                            <Row label="Host" value={settings.mail_host} />
                            <Row label="Port" value={settings.mail_port} />
                            <Row
                                label="Encryption"
                                value={settings.mail_encryption}
                            />
                            <Row
                                label="Username"
                                value={settings.mail_username}
                                sensitive
                            />
                            <Row
                                label="From address"
                                value={settings.mail_from_address}
                            />
                            <Row
                                label="From name"
                                value={settings.mail_from_name}
                            />
                            <Row
                                label="Reply-To"
                                value={settings.mail_reply_to}
                            />
                        </dl>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
