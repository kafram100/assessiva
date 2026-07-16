import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

const NETWORKS = [
    { id: 'MTN', name: 'MTN', bg: 'bg-yellow-400', text: 'text-yellow-900' },
    { id: 'VODAFONE', name: 'Telecel', bg: 'bg-red-600', text: 'text-white' },
    { id: 'AIRTELTIGO', name: 'AirtelTigo', bg: 'bg-blue-600', text: 'text-white' },
];

function pkgSize(pkg) {
    if (pkg.package_size?.includes('GB') || pkg.package_size?.includes('MB')) {
        return pkg.package_size;
    }
    const m = pkg.package_size?.match(/([\d.]+)\s*(GB|MB)/i);
    if (!m) return pkg.package_size;
    return m[2] === 'GB' ? `${m[1]}GB` : `${m[1]}MB`;
}

export default function BuyData({ packagesData = {}, networks = [], balance = 0 }) {
    const user = usePage().props.auth.user;
    const [pkgGroups, setPkgGroups] = useState(packagesData);
    const [netList] = useState(networks);
    const [walletBalance, setWalletBalance] = useState(Number(balance));
    const netOrder = NETWORKS.map(n => n.id);
    const [activeNet, setActiveNet] = useState(
        netList.includes('MTN') ? 'MTN' : (netList.length > 0 ? netList[0] : null)
    );
    const [selectedPkg, setSelectedPkg] = useState(null);
    const [beneficiary, setBeneficiary] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const currentPkgs = pkgGroups[activeNet] || [];

    const netInfo = (id) => NETWORKS.find(n => n.id === id);

    const handleSelectPkg = (pkg) => {
        setSelectedPkg(pkg);
        setError(null);
        setSuccess(null);
    };

    const handlePlaceOrder = async () => {
        if (!selectedPkg) return;

        const beneficiaryNumber = beneficiary || (user?.phone || '');

        if (beneficiaryNumber.length < 10) {
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
                beneficiary_number: beneficiaryNumber,
                network_type: activeNet,
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
                    Buy Data
                </h2>
            }
        >
            <Head title="Buy Data - Dealer" />

            <div className="py-4 sm:py-8">
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
                                <p className="text-sm text-neutral-500 mb-4 dark:text-neutral-400">Data bundle will be delivered shortly.</p>
                                <div className="bg-neutral-50 rounded-xl p-4 text-left mb-4 space-y-1 dark:bg-neutral-800">
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        Package: <span className="font-semibold text-neutral-900 dark:text-neutral-200">{pkgSize(selectedPkg)} {activeNet}</span>
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
                        ) : (
                            <>
                                {!selectedPkg ? (
                                    <>
                                        {netList.length > 0 && (
                                            <div className="flex gap-2 mb-4 overflow-x-auto pb-1">
                                                {[...netList].sort((a, b) => netOrder.indexOf(a) - netOrder.indexOf(b)).map(nid => {
                                                    const n = netInfo(nid);
                                                    return (
                                                        <button key={nid} onClick={() => setActiveNet(nid)}
                                                            className={`shrink-0 px-5 py-2 rounded-full text-sm font-semibold transition border-2 ${
                                                                activeNet === nid
                                                                    ? `${n?.bg} ${n?.text} border-transparent`
                                                                    : 'bg-white text-neutral-600 border-neutral-200 hover:border-neutral-300 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                                            }`}
                                                        >
                                                            {n?.name || nid}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {activeNet && currentPkgs.length > 0 && (
                                            <div className="grid grid-cols-2 gap-3">
                                                {currentPkgs.map(pkg => {
                                                    const netColors = netInfo(activeNet);
                                                    return (
                                                    <button key={pkg.id} onClick={() => handleSelectPkg(pkg)}
                                                        className={`${netColors?.bg} rounded-xl p-4 text-left border-2 border-transparent hover:opacity-90 transition`}
                                                    >
                                                        <div className={`text-2xl font-bold ${netColors?.text}`}>{pkgSize(pkg)}</div>
                                                        <div className={`text-sm font-semibold ${netColors?.text} opacity-80 mt-1`}>
                                                            GH₵ {Number(pkg.price).toFixed(2)}
                                                        </div>
                                                    </button>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {activeNet && currentPkgs.length === 0 && (
                                            <p className="text-neutral-400 text-center py-6 dark:text-neutral-500">No packages available</p>
                                        )}

                                        {!activeNet && netList.length === 0 && (
                                            <p className="text-neutral-400 text-center py-6 dark:text-neutral-500">No packages available for your account</p>
                                        )}
                                    </>
                                ) : (
                                    <div>
                                        <button onClick={() => { setSelectedPkg(null); setError(null); }}
                                            className="text-sm text-primary-600 hover:underline mb-4 inline-block dark:text-primary-400">&larr; Back</button>

                                        <div className="bg-gradient-to-br from-primary-50 to-tertiary-50 rounded-xl p-4 mb-5 dark:from-primary-900/20 dark:to-tertiary-900/20">
                                            <div className="text-xs text-neutral-500 uppercase tracking-wide dark:text-neutral-400">{activeNet}</div>
                                            <div className="text-3xl font-bold text-neutral-900 mt-1 dark:text-neutral-200">{pkgSize(selectedPkg)}</div>
                                            <div className="text-2xl font-bold text-primary-600 mt-1 dark:text-primary-400">GH₵ {Number(selectedPkg.price).toFixed(2)}</div>
                                        </div>

                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Beneficiary Number</label>
                                                <input type="tel" value={beneficiary}
                                                    onChange={e => setBeneficiary(e.target.value.replace(/\D/g, '').slice(0, 10))}
                                                    placeholder="024XXX XXXX"
                                                    className="w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 focus:ring-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:focus:border-primary-400" />
                                            </div>

                                            <button onClick={handlePlaceOrder} disabled={loading}
                                                className="w-full bg-primary-600 text-white py-3.5 rounded-xl font-bold text-lg hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-200 dark:shadow-primary-900/30">
                                                {loading ? 'Processing...' : `Place Order - GH₵ ${Number(selectedPkg.price).toFixed(2)}`}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
