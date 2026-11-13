import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

export default function BillingSettings({ settings }) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        active_payment_gateway: settings.active_payment_gateway || 'paystack',
        token_price_per_question: settings.token_price_per_question || '1',
        paystack_public_key: settings.paystack_public_key || '',
        paystack_secret_key: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.billing-settings.update'));
    };

    return (
        <AdminLayout header="Billing Settings">
            <Head title="Billing Settings" />

            <div className="max-w-4xl mx-auto py-8">
                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6">
                        <h2 className="text-lg font-semibold text-theme-strong mb-4">General Settings</h2>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm text-theme-dim mb-1">Active Gateway</label>
                                <select
                                    className="w-full rounded-lg border border-theme-line bg-theme-layer-1 px-4 py-2 text-theme-strong focus:border-sky-500"
                                    value={data.active_payment_gateway}
                                    onChange={(e) => setData('active_payment_gateway', e.target.value)}
                                >
                                    <option value="paystack">Paystack</option>
                                    <option value="moolre" disabled>
                                        Moolre (integration deferred)
                                    </option>
                                </select>
                                {errors.active_payment_gateway && <p className="mt-1 text-sm text-red-400">{errors.active_payment_gateway}</p>}
                            </div>

                            <div>
                                <label className="block text-sm text-theme-dim mb-1">Price per Token (Money)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    className="w-full rounded-lg border border-theme-line bg-theme-layer-1 px-4 py-2 text-theme-strong focus:border-sky-500"
                                    value={data.token_price_per_question}
                                    onChange={(e) => setData('token_price_per_question', e.target.value)}
                                />
                                {errors.token_price_per_question && <p className="mt-1 text-sm text-red-400">{errors.token_price_per_question}</p>}
                            </div>
                        </div>
                        <p className="mt-4 text-sm text-theme-dim">
                            Choose a gateway and save to apply it to new top-ups. Moolre is deferred and unavailable until its live API integration is configured.
                        </p>
                    </div>

                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6">
                        <h2 className="text-lg font-semibold text-theme-strong mb-4">Paystack Config</h2>
                        <p className="mb-4 text-sm text-theme-dim">
                            {settings.paystack_secret_configured
                                ? 'A secret key is stored. Leave the secret field blank to keep it, or enter a replacement.'
                                : 'Add your Paystack secret key to enable payments.'}
                        </p>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm text-theme-dim mb-1">Public Key</label>
                                <input
                                    type="text"
                                    className="w-full rounded-lg border border-theme-line bg-theme-layer-1 px-4 py-2 text-theme-strong focus:border-sky-500"
                                    value={data.paystack_public_key}
                                    onChange={(e) => setData('paystack_public_key', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-theme-dim mb-1">Secret Key</label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-theme-line bg-theme-layer-1 px-4 py-2 text-theme-strong focus:border-sky-500"
                                    value={data.paystack_secret_key}
                                    onChange={(e) => setData('paystack_secret_key', e.target.value)}
                                    autoComplete="new-password"
                                    placeholder={settings.paystack_secret_configured ? 'Stored — leave blank to keep' : 'Enter secret key'}
                                />
                                {errors.paystack_secret_key && <p className="mt-1 text-sm text-red-400">{errors.paystack_secret_key}</p>}
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6">
                        <h2 className="text-lg font-semibold text-theme-strong mb-2">Moolre</h2>
                        <p className="text-sm text-theme-dim">
                            Moolre is disabled until its official API integration is implemented and verified. Only Paystack can currently accept payments.
                        </p>
                    </div>

                    <div className="flex justify-end">
                        <div className="flex items-center gap-4">
                            {recentlySuccessful && <span className="text-sm text-emerald-400">Settings saved.</span>}
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-sky-500 disabled:opacity-50"
                            >
                                Save Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
