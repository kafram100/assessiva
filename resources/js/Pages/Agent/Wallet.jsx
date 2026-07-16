import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Dialog, DialogPanel, DialogTitle, Transition, TransitionChild } from '@headlessui/react';
import axios from 'axios';

export default function Wallet({ balance, feeRate = 0, topups, balanceHistory, auth }) {
    const [showTopup, setShowTopup] = useState(false);
    const [verifyingRef, setVerifyingRef] = useState(null);
    const [amount, setAmount] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [step, setStep] = useState('form');
    const [topupRef, setTopupRef] = useState(null);

    const [flash, setFlash] = useState(null);
    const pollTimer = useRef(null);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('topup') === 'success') {
            setFlash({ type: 'success', message: 'Wallet topped up successfully!' });
            window.history.replaceState({}, '', window.location.pathname);
        } else if (params.get('topup') === 'failed') {
            setFlash({ type: 'error', message: 'Top-up failed. Please try again.' });
            window.history.replaceState({}, '', window.location.pathname);
        }
    }, []);

    useEffect(() => {
        return () => {
            if (pollTimer.current) clearInterval(pollTimer.current);
        };
    }, []);

    const startPolling = (reference) => {
        if (pollTimer.current) clearInterval(pollTimer.current);
        pollTimer.current = setInterval(async () => {
            try {
                const res = await axios.get(route('wallet.topup.status', reference));
                if (res.data.status === 'completed') {
                    clearInterval(pollTimer.current);
                    pollTimer.current = null;
                    setTimeout(() => window.location.reload(), 800);
                } else if (res.data.status === 'failed') {
                    clearInterval(pollTimer.current);
                    pollTimer.current = null;
                    setError('Payment failed. Please try again.');
                    setStep('form');
                }
            } catch {
                // ignore polling errors
            }
        }, 4000);
    };

    const statusBadge = (s) => {
        const colors = {
            completed: 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
            pending: 'bg-secondary-100 text-secondary-700 dark:bg-secondary-900/30 dark:text-secondary-300',
            processing: 'bg-tertiary-100 text-tertiary-700 dark:bg-tertiary-900/30 dark:text-tertiary-300',
            failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            cancelled: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
        };
        return colors[s] || 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
    };

    const handleTopup = async () => {
        const val = parseFloat(amount);
        if (!val || val < 1) {
            setError('Minimum top-up is GH₵ 1');
            return;
        }
        setLoading(true);
        setError(null);
        try {
            const res = await axios.post(route('wallet.topup.initiate'), {
                amount: val,
            });
            if (res.data.success) {
                if (res.data.data.authorization_url) {
                    window.location.href = res.data.data.authorization_url;
                } else {
                    setTopupRef(res.data.data.reference);
                    setStep('waiting');
                    startPolling(res.data.data.reference);
                }
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to initiate top-up');
        } finally {
            setLoading(false);
        }
    };

    const checkStatusNow = async () => {
        if (!topupRef) return;
        setLoading(true);
        try {
            const res = await axios.get(route('wallet.topup.status', topupRef));
            if (res.data.status === 'completed') {
                if (pollTimer.current) clearInterval(pollTimer.current);
                setTimeout(() => window.location.reload(), 800);
            } else if (res.data.status === 'failed') {
                if (pollTimer.current) clearInterval(pollTimer.current);
                setError('Payment failed. Please try again.');
                setStep('form');
            } else {
                setError(null);
            }
        } catch {
            setError('Could not check status');
        } finally {
            setLoading(false);
        }
    };

    const resetModal = () => {
        setShowTopup(false);
        setStep('form');
        setAmount('');
        setError(null);
        setTopupRef(null);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    Wallet
                </h2>
            }
        >
            <Head title="Wallet" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {flash && (
                        <div className={`mb-6 px-5 py-3 rounded-xl text-sm font-medium ${
                            flash.type === 'success'
                                ? 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800'
                                : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800'
                        }`}>
                            {flash.message}
                        </div>
                    )}

                    <div className="bg-gradient-to-r from-primary-600 to-tertiary-700 rounded-xl shadow-lg p-6 text-white mb-8">
                        <p className="text-sm font-medium text-primary-100">Current Balance</p>
                        <p className="text-4xl font-bold mt-1">GH₵ {balance.toFixed(2)}</p>
                        <p className="text-xs text-primary-200 mt-2">Use your wallet balance to purchase data bundles</p>
                        <div className="mt-4">
                            <button
                                onClick={() => setShowTopup(true)}
                                className="inline-flex items-center gap-2 bg-white text-primary-700 px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-50 transition shadow-md"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Top Up Wallet
                            </button>
                        </div>
                    </div>

                    <div className="grid lg:grid-cols-2 gap-8">
                        <div className="bg-white rounded-xl shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800">
                            <div className="px-5 py-4 border-b border-neutral-100 dark:border-neutral-800">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-200">Balance History</h3>
                            </div>
                            <div className="p-5 max-h-[500px] overflow-y-auto">
                                {balanceHistory.length === 0 ? (
                                    <p className="text-neutral-400 text-sm text-center py-8 dark:text-neutral-500">No activity yet</p>
                                ) : (
                                    <div className="space-y-3">
                                        {balanceHistory.map((entry) => (
                                            <div key={entry.id} className="flex items-center justify-between py-2 border-b border-neutral-50 last:border-0 dark:border-neutral-800">
                                                <div>
                                                    <p className="text-sm font-medium text-neutral-900 capitalize dark:text-neutral-200">{entry.reason}</p>
                                                    {entry.description && (
                                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">{entry.description}</p>
                                                    )}
                                                    {entry.beneficiary_number && (
                                                        <p className="text-xs text-neutral-400 dark:text-neutral-500">Tel: {entry.beneficiary_number}</p>
                                                    )}
                                                    <p className="text-xs text-neutral-400 dark:text-neutral-500">{entry.created_at}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className={`text-sm font-semibold ${entry.change_amount >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-600 dark:text-red-400'}`}>
                                                        {entry.change_amount >= 0 ? '+' : ''}GH₵ {entry.change_amount.toFixed(2)}
                                                    </p>
                                                    <p className="text-xs text-neutral-400 dark:text-neutral-500">Bal: GH₵ {entry.balance_after.toFixed(2)}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800">
                            <div className="px-5 py-4 border-b border-neutral-100 dark:border-neutral-800">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-200">Top-up History</h3>
                            </div>
                            <div className="p-5 max-h-[500px] overflow-y-auto">
                                {topups.data.length === 0 ? (
                                    <p className="text-neutral-400 text-sm text-center py-8 dark:text-neutral-500">No top-ups yet</p>
                                ) : (
                                    <div className="space-y-3">
                                        {topups.data.map((topup) => (
                                            <div key={topup.id} className="flex items-center justify-between py-2 border-b border-neutral-50 last:border-0 dark:border-neutral-800">
                                                <div>
                                                    <p className="text-sm font-medium text-neutral-900 dark:text-neutral-200">
                                                        {topup.provider || 'Manual'} Top-up
                                                    </p>
                                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">Ref: {topup.reference}</p>
                                                    <p className="text-xs text-neutral-400 dark:text-neutral-500">{topup.created_at}</p>
                                                </div>
                                                <div className="text-right flex flex-col items-end gap-1.5">
                                                    <p className="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                                        +GH₵ {topup.base_amount.toFixed(2)}
                                                    </p>
                                                    {topup.processing_fee > 0 && (
                                                        <p className="text-xs text-neutral-400 dark:text-neutral-500">Fee: GH₵ {topup.processing_fee.toFixed(2)}</p>
                                                    )}
                                                    {topup.status === 'pending' ? (
                                                        <button
                                                            onClick={async () => {
                                                                setVerifyingRef(topup.reference);
                                                                try {
                                                                    const res = await axios.post(route('wallet.topup.verify-single', topup.reference));
                                                                    if (res.data.success) {
                                                                        window.location.reload();
                                                                    } else {
                                                                        alert(res.data.message);
                                                                    }
                                                                } catch {
                                                                    alert('Could not verify payment. Please try again.');
                                                                } finally {
                                                                    setVerifyingRef(null);
                                                                }
                                                            }}
                                                            disabled={verifyingRef === topup.reference}
                                                            className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 hover:bg-amber-200 disabled:opacity-50 transition dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50"
                                                        >
                                                            {verifyingRef === topup.reference ? (
                                                                <>
                                                                    <svg className="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                                                    Verifying...
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                                    Verify Payment
                                                                </>
                                                            )}
                                                        </button>
                                                    ) : (
                                                        <span className={`inline-block text-xs px-2 py-0.5 rounded-full font-medium ${statusBadge(topup.status)}`}>
                                                            {topup.status}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {topups.links && (
                                    <div className="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
                                        <div className="flex justify-between items-center text-sm">
                                            <p className="text-neutral-500 dark:text-neutral-400">
                                                Showing {topups.from}–{topups.to} of {topups.total}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Transition show={showTopup}>
                <Dialog as="div" className="relative z-50" onClose={() => step === 'form' ? resetModal() : null}>
                    <TransitionChild
                        enter="ease-out duration-200"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="ease-in duration-150"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="fixed inset-0 bg-black/40" />
                    </TransitionChild>

                    <div className="fixed inset-0 flex items-center justify-center p-4">
                        <TransitionChild
                            enter="ease-out duration-200"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <DialogPanel className="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 dark:bg-neutral-900 dark:border dark:border-neutral-800">

                                {step === 'form' && (
                                    <>
                                        <DialogTitle className="text-lg font-bold text-neutral-900 mb-1 dark:text-neutral-200">
                                            Top Up Wallet
                                        </DialogTitle>
                                        <p className="text-sm text-neutral-500 mb-5 dark:text-neutral-400">
                                            Enter the amount to top up your wallet{feeRate > 0 ? `. A ${Math.round(feeRate * 100)}% fee applies` : '.'}
                                        </p>

                                        <div className="mb-4">
                                            <label className="block text-sm font-medium text-neutral-700 mb-1.5 dark:text-neutral-300">Amount (GH₵)</label>
                                            <div className="relative">
                                                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400 font-semibold dark:text-neutral-500">GH₵</span>
                                                <input
                                                    type="number"
                                                    value={amount}
                                                    onChange={e => { setAmount(e.target.value); setError(null); }}
                                                    className="w-full pl-14 pr-4 py-3 text-2xl font-bold border-2 border-neutral-200 rounded-xl focus:border-primary-500 focus:ring-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:focus:border-primary-400"
                                                    placeholder="0.00"
                                                    min="1"
                                                    step="0.01"
                                                    autoFocus
                                                />
                                            </div>
                                        </div>



                                        {amount && parseFloat(amount) >= 1 && (
                                            <div className="bg-neutral-50 rounded-xl p-4 mt-1 mb-3 space-y-1.5 text-sm dark:bg-neutral-800">
                                                <div className="flex justify-between">
                                                    <span className="text-neutral-500 dark:text-neutral-400">Amount</span>
                                                    <span className="font-semibold text-neutral-900 dark:text-neutral-200">GH₵ {parseFloat(amount).toFixed(2)}</span>
                                                </div>
                                                {feeRate > 0 && (
                                                    <div className="flex justify-between">
                                                        <span className="text-neutral-500 dark:text-neutral-400">Fee ({Math.round(feeRate * 100)}%)</span>
                                                        <span className="font-semibold text-neutral-900 dark:text-neutral-200">GH₵ {(parseFloat(amount) * feeRate).toFixed(2)}</span>
                                                    </div>
                                                )}
                                                <div className="flex justify-between pt-1.5 border-t border-neutral-200 dark:border-neutral-700">
                                                    <span className="text-neutral-700 font-medium dark:text-neutral-300">Total</span>
                                                    <span className="font-bold text-primary-600 dark:text-primary-400">GH₵ {(parseFloat(amount) * (1 + feeRate)).toFixed(2)}</span>
                                                </div>
                                            </div>
                                        )}

                                        {error && (
                                            <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                                        )}

                                        <div className="flex gap-3 mt-5">
                                            <button onClick={resetModal}
                                                className="flex-1 py-3 border-2 border-neutral-200 rounded-xl font-semibold text-sm text-neutral-600 hover:bg-neutral-50 transition dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                                Cancel
                                            </button>
                                            <button onClick={handleTopup}
                                                disabled={loading || !amount || parseFloat(amount) < 1}
                                                className="flex-1 bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-200 dark:shadow-primary-900/30 flex items-center justify-center gap-2">
                                                {loading ? (
                                                    <>
                                                        <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                        </svg>
                                                        Processing...
                                                    </>
                                                ) : (
                                                    <>
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                        </svg>
                                                        Top Up
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </>
                                )}

                                {step === 'waiting' && (
                                    <div className="text-center py-6">
                                        <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-primary-900/30">
                                            <svg className="w-8 h-8 text-primary-600 animate-bounce dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <DialogTitle className="text-lg font-bold text-neutral-900 mb-2 dark:text-neutral-200">
                                            Check Your Phone
                                        </DialogTitle>
                                        <p className="text-sm text-neutral-500 mb-6 max-w-xs mx-auto dark:text-neutral-400">
                                            A payment request has been sent to your phone. Enter your Mobile Money PIN to approve the payment.
                                        </p>

                                        {topupRef && (
                                            <p className="text-xs text-neutral-400 mb-4 dark:text-neutral-500">Ref: {topupRef}</p>
                                        )}

                                        <div className="flex items-center justify-center gap-2 text-sm text-neutral-400 mb-6 dark:text-neutral-500">
                                            <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Waiting for payment confirmation...
                                        </div>

                                        <button onClick={checkStatusNow} disabled={loading}
                                            className="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-200 mb-2 dark:shadow-primary-900/30">
                                            {loading ? 'Checking...' : "I've Completed Payment"}
                                        </button>
                                        <button onClick={resetModal}
                                            className="w-full text-neutral-500 py-2 text-sm hover:underline dark:text-neutral-400 dark:hover:text-neutral-300">
                                            Cancel
                                        </button>

                                        {error && (
                                            <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 mt-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                                        )}
                                    </div>
                                )}

                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>

        </AuthenticatedLayout>
    );
}
