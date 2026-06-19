import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, usePage, useForm } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

export default function Wallet({ tokenPrice, activeGateway }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const [amount, setAmount] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    
    const verifyForm = useForm({
        reference: ''
    });

    const handleVerify = (e) => {
        e.preventDefault();
        verifyForm.post(route('student.wallet.topup.verify'), {
            onSuccess: () => verifyForm.reset('reference')
        });
    };

    const handleTopUp = async (e) => {
        e.preventDefault();
        setError(null);
        if (!amount || amount < 1) {
            setError('Please enter a valid amount.');
            return;
        }

        setLoading(true);
        try {
            const res = await axios.post(route('student.wallet.topup.initialize'), { amount });
            
            if (res.data.authorization_url) {
                window.location.href = res.data.authorization_url;
            } else {
                setError(res.data.error || 'Failed to initialize payment.');
                setLoading(false);
            }
        } catch (err) {
            setError(err.response?.data?.message || err.response?.data?.error || 'Network error occurred.');
            setLoading(false);
        }
    };

    return (
        <PracticeHubLayout header="My Wallet">
            <Head title="Wallet" />
            
            <div className="max-w-3xl mx-auto py-8">
                {flash.success && (
                    <div className="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400">
                        {flash.error}
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 h-fit">
                        <h2 className="text-lg font-semibold text-theme-strong mb-4">Balance</h2>
                        <div className="space-y-4">
                            <div className="p-4 rounded-xl bg-sky-500/10 border border-sky-500/20">
                                <div className="text-sm text-theme-dim">Tokens</div>
                                <div className="text-3xl font-bold text-amber-400">{user.tokens || 0}</div>
                            </div>

                        </div>
                    </div>

                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 h-fit">
                        <h2 className="text-lg font-semibold text-theme-strong mb-4">Top Up Tokens</h2>
                        
                        <form onSubmit={handleTopUp} className="space-y-4">
                            <div>
                                <label className="block text-sm text-theme-dim mb-1">Amount to Pay</label>
                                <input
                                    type="number"
                                    min="1"
                                    step="0.01"
                                    className="w-full rounded-lg border border-theme-line bg-theme-layer-1 px-4 py-2 text-theme-strong focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                    placeholder="Enter amount"
                                />
                            </div>
                            
                            {error && <p className="text-sm text-red-400">{error}</p>}
                            
                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full rounded-xl bg-sky-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-sky-500 disabled:opacity-50"
                            >
                                {loading ? 'Processing...' : 'Pay with Card'}
                            </button>
                        </form>
                        
                        <div className="mt-6 pt-6 border-t border-theme-line">
                            <h3 className="text-sm font-medium text-theme-strong mb-2">Missing Tokens?</h3>
                            <p className="text-xs text-theme-dim mb-4 leading-relaxed">
                                If your payment went through but tokens didn't appear, you can manually trigger a scan of your pending payments.
                            </p>
                            <form onSubmit={handleVerify}>
                                <button
                                    type="submit"
                                    disabled={verifyForm.processing}
                                    className="w-full rounded-xl bg-theme-layer-2 border border-theme-line py-2.5 text-sm font-semibold text-theme-strong transition-all hover:bg-theme-layer-1 active:scale-[0.98] disabled:opacity-50 disabled:active:scale-100 flex items-center justify-center gap-2"
                                >
                                    {verifyForm.processing ? (
                                        <>
                                            <svg className="w-4 h-4 animate-spin text-theme-dim" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Scanning...
                                        </>
                                    ) : (
                                        <>
                                            <svg className="w-4 h-4 text-theme-dim" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Verify Pending Payments
                                        </>
                                    )}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </PracticeHubLayout>
    );
}
