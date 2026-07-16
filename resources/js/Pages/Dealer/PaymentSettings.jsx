import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function PaymentSettings({ paystack_public_key, has_secret_key, platform_public_key }) {
    const { flash } = usePage().props;
    const [form, setForm] = useState({
        paystack_public_key: paystack_public_key || '',
        paystack_secret_key: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [flashMsg, setFlashMsg] = useState(flash?.success || null);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            await router.post(route('dealer.payment-settings.update'), form, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setFlashMsg('Payment settings saved.');
                    setForm(prev => ({ ...prev, paystack_secret_key: '' }));
                },
                onError: (errs) => setError(Object.values(errs).flat().join(', ')),
            });
        } catch {
            setError('Something went wrong');
        } finally {
            setLoading(false);
        }
    };

    const showMasked = has_secret_key && !form.paystack_secret_key;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    Payment Settings
                </h2>
            }
        >
            <Head title="Payment Settings" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {flashMsg && (
                        <div className="px-5 py-3 rounded-xl text-sm font-medium bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                            {flashMsg}
                        </div>
                    )}

                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-6 dark:bg-neutral-900 dark:border-neutral-800">
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-200">Paystack Configuration</h3>

                            <div className="bg-primary-50 border border-primary-200 rounded-xl px-4 py-3 text-sm text-primary-800 dark:bg-primary-900/20 dark:border-primary-800 dark:text-primary-300">
                                <p className="font-medium">How it works</p>
                                <p className="mt-1">Enter your own Paystack keys to receive store and Sub-Agent payments directly into your Paystack account. When no custom keys are set, the platform default Paystack account is used.</p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Paystack Public Key</label>
                                <input
                                    type="text"
                                    value={form.paystack_public_key}
                                    onChange={e => setForm({ ...form, paystack_public_key: e.target.value })}
                                    placeholder="pk_live_xxxxxxxxxxxxxx"
                                    className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition font-mono text-sm dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                />
                                <p className="text-xs text-neutral-400 mt-1 dark:text-neutral-500">Platform default: <span className="font-mono">{platform_public_key ? platform_public_key.substring(0, 12) + '…' : 'Not set'}</span></p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Paystack Secret Key</label>
                                <div className="relative">
                                    <input
                                        type="password"
                                        value={form.paystack_secret_key}
                                        onChange={e => setForm({ ...form, paystack_secret_key: e.target.value })}
                                        placeholder={showMasked ? '••••••••••••••••' : 'sk_live_xxxxxxxxxxxxxx'}
                                        className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition font-mono text-sm dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                    />
                                    {showMasked && (
                                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-neutral-400 bg-neutral-100 px-2 py-0.5 rounded dark:bg-neutral-700 dark:text-neutral-500">
                                            Saved
                                        </span>
                                    )}
                                </div>
                                <p className="text-xs text-neutral-400 mt-1 dark:text-neutral-500">Leave blank to keep existing. Only enter if you want to change it.</p>
                            </div>

                            {error && (
                                <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                            )}

                            <button type="submit" disabled={loading}
                                className="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition">
                                {loading ? 'Saving…' : 'Save Payment Settings'}
                            </button>
                        </form>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-6 dark:bg-neutral-900 dark:border-neutral-800">
                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-200 mb-3">Where these keys are used</h3>
                        <ul className="space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                            <li className="flex items-start gap-2">
                                <svg className="w-4 h-4 mt-0.5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                <span><strong className="text-neutral-900 dark:text-neutral-200">Store payments</strong> — customers purchasing from your storefront pay through your Paystack account.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <svg className="w-4 h-4 mt-0.5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                <span><strong className="text-neutral-900 dark:text-neutral-200">Sub-Agent orders</strong> — orders placed by your Sub-Agents are paid into your Paystack account.</span>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
