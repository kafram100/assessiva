import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

export default function MashupData({ packages = [], balance = 0 }) {
    const user = usePage().props.auth.user;
    const [walletBalance, setWalletBalance] = useState(Number(balance));
    const [selectedPkg, setSelectedPkg] = useState(null);
    const [beneficiary, setBeneficiary] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const handleBuy = async () => {
        if (!selectedPkg) return;
        const num = beneficiary || (user?.phone || '');
        if (num.length < 10) {
            setError('Please enter a valid 10-digit beneficiary number.');
            return;
        }
        if (selectedPkg.price > walletBalance) {
            setError('Insufficient balance. Please top up your wallet.');
            return;
        }
        setLoading(true);
        setError(null);
        setSuccess(null);
        try {
            const res = await axios.post(route('agent.order.place'), {
                package_id: selectedPkg.id,
                beneficiary_number: num,
                network_type: 'MTN',
            });
            if (res.data.success) {
                setSuccess(res.data);
                setWalletBalance(Number(res.data.data.balance_after));
            } else {
                setError(res.data.message || 'Order failed');
                if (res.data.data?.balance_after !== undefined) {
                    setWalletBalance(Number(res.data.data.balance_after));
                }
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong');
        } finally {
            setLoading(false);
        }
    };

    const resetAll = () => {
        setSelectedPkg(null);
        setBeneficiary('');
        setError(null);
        setSuccess(null);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    MTN Mashup
                </h2>
            }
        >
            <Head title="MTN Mashup" />

            <div className="py-8">
                <div className="mx-auto max-w-lg px-4 sm:px-6 lg:px-8">
                    <div className="bg-gradient-to-br from-primary-600 to-tertiary-700 rounded-xl p-5 mb-6 text-white shadow-lg">
                        <p className="text-sm text-white/80 font-medium">Wallet Balance</p>
                        <p className="text-3xl font-bold mt-1">GH₵ {Number(walletBalance).toFixed(2)}</p>
                    </div>

                    {error && (
                        <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 mb-4 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                    )}

                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-5 dark:bg-neutral-900 dark:border-neutral-800">
                        {success ? (
                            <div className="text-center py-4">
                                <div className="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-green-900/30">
                                    <svg className="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-bold text-neutral-900 mb-1 dark:text-neutral-200">Order Placed!</h3>
                                <p className="text-sm text-neutral-500 mb-4 dark:text-neutral-400">MTN Mashup will be delivered shortly.</p>
                                <div className="bg-neutral-50 rounded-xl p-4 text-left mb-4 space-y-1 dark:bg-neutral-800">
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        Package: <span className="font-semibold text-neutral-900 dark:text-neutral-200">{selectedPkg.package_name}</span>
                                    </p>
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        Beneficiary: <span className="font-semibold text-neutral-900 dark:text-neutral-200">{success.data.order.beneficiary_number}</span>
                                    </p>
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        Amount: <span className="font-semibold text-red-600 dark:text-red-400">-GH₵ {Number(success.data.amount_deducted).toFixed(2)}</span>
                                    </p>
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        New Balance: <span className="font-semibold text-primary-600 dark:text-primary-400">GH₵ {Number(success.data.balance_after).toFixed(2)}</span>
                                    </p>
                                </div>
                                <button onClick={resetAll}
                                    className="w-full bg-primary-600 text-white py-2.5 rounded-xl font-semibold hover:bg-primary-700 transition">
                                    Buy Another
                                </button>
                            </div>
                        ) : !selectedPkg ? (
                            <>
                                {packages.length > 0 ? (
                                    <div className="grid grid-cols-2 gap-3">
                                        {packages.map(pkg => (
                                            <button key={pkg.id} onClick={() => { setSelectedPkg(pkg); setError(null); }}
                                                className="bg-yellow-400 rounded-xl p-4 text-left border-2 border-transparent hover:opacity-90 transition relative"
                                            >
                                                {pkg.non_expiring && (
                                                    <span className="absolute top-1 right-1 bg-green-600 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full">non-expiring</span>
                                                )}
                                                <div className="text-lg font-bold text-yellow-900">{pkg.package_name}</div>
                                                {pkg.package_size_gb > 0 && (
                                                    <div className="text-sm font-semibold text-yellow-800 mt-1">{pkg.package_size_gb} GB Data</div>
                                                )}
                                                {pkg.voice_minutes > 0 && (
                                                    <div className="text-sm font-semibold text-yellow-800">{pkg.voice_minutes} mins Voice</div>
                                                )}
                                                <div className="text-sm font-semibold text-yellow-900 mt-2">
                                                    GH₵ {Number(pkg.price).toFixed(2)}
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-neutral-400 text-center py-6 dark:text-neutral-500">No MTN Mashup packages available</p>
                                )}
                            </>
                        ) : (
                            <div>
                                <button onClick={() => { setSelectedPkg(null); setError(null); }}
                                    className="text-sm text-primary-600 hover:underline mb-4 inline-block dark:text-primary-400">&larr; Back</button>

                                <div className="bg-yellow-50 rounded-xl p-4 mb-5 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-700/30">
                                    <div className="text-xs text-yellow-700 uppercase tracking-wide font-semibold dark:text-yellow-400">MTN Mashup</div>
                                    <div className="text-2xl font-bold text-yellow-800 mt-1 dark:text-yellow-300">{selectedPkg.package_name}</div>
                                    {selectedPkg.package_size_gb > 0 && (
                                        <div className="text-lg font-semibold text-yellow-700 mt-1 dark:text-yellow-400">{selectedPkg.package_size_gb} GB Data</div>
                                    )}
                                    {selectedPkg.voice_minutes > 0 && (
                                        <div className="text-lg font-semibold text-yellow-700 dark:text-yellow-400">{selectedPkg.voice_minutes} mins Voice</div>
                                    )}
                                    <div className="text-2xl font-bold text-primary-600 mt-1 dark:text-primary-400">GH₵ {Number(selectedPkg.price).toFixed(2)}</div>
                                    <p className="text-xs text-yellow-600 mt-2 dark:text-yellow-400">Manual fulfillment — no auto-delivery</p>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Beneficiary Number</label>
                                        <input type="tel" value={beneficiary}
                                            onChange={e => setBeneficiary(e.target.value.replace(/\D/g, '').slice(0, 10))}
                                            placeholder="024XXX XXXX"
                                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 focus:ring-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:focus:border-primary-400" />
                                    </div>

                                    <button onClick={handleBuy} disabled={loading}
                                        className="w-full bg-yellow-500 text-yellow-900 py-3.5 rounded-xl font-bold text-lg hover:bg-yellow-400 disabled:opacity-50 transition shadow-lg shadow-yellow-200 dark:shadow-yellow-900/30">
                                        {loading ? 'Processing...' : `Buy - GH₵ ${Number(selectedPkg.price).toFixed(2)}`}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
