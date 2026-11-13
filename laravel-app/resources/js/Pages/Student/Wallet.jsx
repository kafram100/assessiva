import PracticeHubLayout from '@/Layouts/PracticeHubLayout';
import { Head, usePage, useForm, Link } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

export default function Wallet({ tokenPrice, activeGateway }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const [error, setError] = useState(null);
    const [loadingPackage, setLoadingPackage] = useState(null);
    
    const verifyForm = useForm({
        reference: ''
    });

    const handleVerify = (e) => {
        e.preventDefault();
        verifyForm.post(route('student.wallet.topup.verify'), {
            onSuccess: () => verifyForm.reset('reference')
        });
    };

    const packages = [3, 5, 10, 20, 50, 100];

    const handleTopUp = async (packageAmount) => {
        setError(null);
        setLoadingPackage(packageAmount);
        
        try {
            const res = await axios.post(route('student.wallet.topup.initialize'), { amount: packageAmount });
            
            if (res.data.authorization_url) {
                window.location.href = res.data.authorization_url;
            } else {
                setError(res.data.error || 'Failed to initialize payment.');
                setLoadingPackage(null);
            }
        } catch (err) {
            setError(err.response?.data?.message || err.response?.data?.error || 'Network error occurred.');
            setLoadingPackage(null);
        }
    };

    return (
        <PracticeHubLayout header="My Wallet">
            <Head title="Wallet" />
            
            <div className="max-w-3xl mx-auto py-8">
                <div className="mb-6">
                    <Link
                        href={route('practice-quizzes.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-theme-dim hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Practice Hub
                    </Link>
                </div>

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

                <div className="w-full">
                    <div className="rounded-2xl border border-theme-line bg-theme-elevated p-6 md:p-8 h-fit">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-semibold text-theme-strong">Top Up Tokens</h2>
                            <span className="text-sm font-medium text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                                1 Token = {tokenPrice} GHS
                            </span>
                        </div>
                        <div className="space-y-4">
                            {error && <p className="text-sm text-red-400 bg-red-400/10 border border-red-400/20 p-3 rounded-xl">{error}</p>}

                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                {packages.map((pkgAmount) => {
                                    const tokens = Math.floor(pkgAmount / tokenPrice);
                                    const isProcessing = loadingPackage === pkgAmount;
                                    
                                    return (
                                        <button
                                            key={pkgAmount}
                                            onClick={() => handleTopUp(pkgAmount)}
                                            disabled={loadingPackage !== null}
                                            className="relative flex flex-col items-center justify-center p-6 rounded-2xl border border-theme-line bg-theme-layer-1 hover:border-indigo-500 hover:shadow-[0_4px_20px_rgba(99,102,241,0.2)] transition-all group disabled:opacity-50 disabled:hover:border-theme-line"
                                        >
                                            <div className="text-2xl font-bold text-amber-400 mb-1">{tokens}</div>
                                            <div className="text-sm font-medium text-theme-strong mb-4">Tokens</div>
                                            
                                            <div className="text-xs text-theme-dim bg-theme-layer-2 px-3 py-1 rounded-full mb-4">
                                                Solves {tokens} questions
                                            </div>

                                            <div className="w-full pt-4 border-t border-theme-line text-center">
                                                <span className="text-lg font-bold text-theme-strong">{pkgAmount} GHS</span>
                                            </div>

                                            {isProcessing && (
                                                <div className="absolute inset-0 bg-theme-layer-1/80 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                                    <svg className="w-6 h-6 animate-spin text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </div>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
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
