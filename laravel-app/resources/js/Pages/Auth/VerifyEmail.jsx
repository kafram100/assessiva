import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />

            <div className="mb-6 text-center">
                <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                    Verify Your Email
                </h2>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Thanks for signing up! Please verify your email address by clicking on the link we sent to your inbox.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs font-medium text-emerald-600 dark:text-emerald-400 text-center">
                    A new verification link has been sent to the email address you provided during registration.
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all"
                        disabled={processing}
                    >
                        {processing ? 'Resending...' : 'Resend Verification Email'}
                    </PrimaryButton>
                </div>

                <div className="text-center pt-1">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 underline underline-offset-2 transition-colors cursor-pointer"
                    >
                        Log Out
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
