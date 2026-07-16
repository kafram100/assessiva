import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import SubAgentLayout from './SubAgentLayout';

const NETWORKS = [
    { id: 'MTN', name: 'MTN', bg: 'bg-yellow-400', text: 'text-yellow-900', iconBg: 'bg-yellow-500', accent: '#eab308' },
    { id: 'VODAFONE', name: 'Telecel', bg: 'bg-red-600', text: 'text-white', iconBg: 'bg-red-500', accent: '#dc2626' },
    { id: 'AIRTELTIGO', name: 'AirtelTigo', bg: 'bg-blue-600', text: 'text-white', iconBg: 'bg-blue-500', accent: '#2563eb' },
];

function NetworkIcon({ net }) {
    if (!net) return null;
    return (
        <div className={`w-12 h-12 rounded-xl ${net.iconBg || 'bg-primary-500'} bg-opacity-30 flex items-center justify-center mx-auto transition-transform group-hover:scale-110 duration-300`}>
            <svg className={`w-6 h-6 ${net.text || 'text-primary-900'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
    );
}

function pkgSize(pkg) {
    if (pkg.package_size?.includes('GB') || pkg.package_size?.includes('MB')) {
        return pkg.package_size;
    }
    const m = pkg.package_size?.match(/([\d.]+)\s*(GB|MB)/i);
    if (!m) return pkg.package_size;
    return m[2] === 'GB' ? `${m[1]}GB` : `${m[1]}MB`;
}

export default function Dashboard({ packages: packagesData = {}, networks = [], balance = 0 }) {
    const subAgent = usePage().props.subAgent;
    const [walletBalance, setWalletBalance] = useState(Number(balance));
    const [pkgGroups] = useState(packagesData);
    const [netList] = useState(networks);
    const netOrder = NETWORKS.map(n => n.id);
    const [activeNet, setActiveNet] = useState(
        netList.includes('MTN') ? 'MTN' : (netList.length > 0 ? netList[0] : null)
    );
    const [selectedPkg, setSelectedPkg] = useState(null);
    const [customerPhone, setCustomerPhone] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const currentPkgs = pkgGroups[activeNet] || [];
    const activeNetInfo = NETWORKS.find(n => n.id === activeNet);

    const netInfo = (id) => NETWORKS.find(n => n.id === id);

    const handleSelectPkg = (pkg) => {
        setSelectedPkg(pkg);
        setError(null);
        setSuccess(null);
    };

    const handlePlaceOrder = async () => {
        if (!selectedPkg) return;

        const phone = customerPhone.replace(/\D/g, '');
        if (phone.length < 10) {
            setError('Please enter a valid 10-digit customer phone number.');
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
            const res = await axios.post(route('Sub-Agent.order'), {
                package_id: selectedPkg.id,
                customer_phone: phone,
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
        setCustomerPhone('');
        setError(null);
        setSuccess(null);
    };

    return (
        <SubAgentLayout
            header={
                <h2 className="text-xl font-bold leading-tight text-neutral-800 dark:text-neutral-200">
                    Buy Data
                </h2>
            }
        >
            <Head title="Sub-Agent - Buy Data" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row gap-6 mb-8 items-center justify-between bg-white dark:bg-neutral-900 rounded-[2rem] p-6 sm:p-8 shadow-sm border border-neutral-200 dark:border-neutral-800 relative overflow-hidden">
                        <div className="absolute inset-0 bg-gradient-to-r from-primary-500/10 to-tertiary-500/10 pointer-events-none"></div>
                        <div className="relative z-10 flex-1">
                            <h1 className="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">Welcome, {subAgent?.full_name || subAgent?.username}</h1>
                            <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400 mt-1">Ready to sell some data?</p>
                        </div>
                        <div className="relative z-10 shrink-0 text-center md:text-right bg-primary-50 dark:bg-primary-900/20 px-8 py-4 rounded-[1.5rem] border border-primary-100 dark:border-primary-900/30 shadow-inner">
                            <p className="text-xs font-bold text-primary-600/80 dark:text-primary-400/80 uppercase tracking-widest mb-1">Wallet Balance</p>
                            <p className="text-4xl font-black text-primary-600 dark:text-primary-400 tracking-tighter">GH₵ {Number(walletBalance).toFixed(2)}</p>
                        </div>
                    </div>

                    {error && (
                        <div className="max-w-md mx-auto mb-6 px-4 py-3 rounded-xl text-sm font-medium bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-900/30 animate-in shake">
                            <div className="flex gap-2 items-center">
                                <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                {error}
                            </div>
                        </div>
                    )}

                    <div className="w-full transition-all duration-500">
                        {success ? (
                            <div className="max-w-md mx-auto animate-in zoom-in-95 duration-500">
                                <div className="bg-white rounded-[2rem] shadow-xl shadow-green-500/10 border border-neutral-200 p-8 text-center dark:bg-neutral-900 dark:border-neutral-800">
                                    <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 dark:bg-green-900/30 animate-in fade-in slide-in-from-bottom-4 duration-700">
                                        <svg className="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h3 className="text-2xl font-black text-neutral-900 mb-2 dark:text-white tracking-tight">Order Successful!</h3>
                                    <div className="bg-neutral-50 rounded-2xl p-5 text-left mb-6 space-y-2 text-sm dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700/50">
                                        <p className="text-neutral-500 dark:text-neutral-400 flex justify-between"><span>Package:</span> <span className="font-bold text-neutral-900 dark:text-neutral-200">{pkgSize(selectedPkg)} {activeNet}</span></p>
                                        <p className="text-neutral-500 dark:text-neutral-400 flex justify-between"><span>Phone:</span> <span className="font-bold text-neutral-900 dark:text-neutral-200">{success.data.order?.customer_phone || customerPhone}</span></p>
                                        <div className="h-px bg-neutral-200 dark:bg-neutral-700 my-2 w-full"></div>
                                        <p className="text-neutral-500 dark:text-neutral-400 flex justify-between"><span>Amount:</span> <span className="font-black text-red-600 dark:text-red-400">-GH₵ {Number(success.data.amount_deducted).toFixed(2)}</span></p>
                                        <p className="text-neutral-500 dark:text-neutral-400 flex justify-between"><span>New Balance:</span> <span className="font-black text-primary-600 dark:text-primary-400">GH₵ {Number(success.data.balance_after).toFixed(2)}</span></p>
                                    </div>
                                    <button onClick={resetAll}
                                        className="w-full bg-primary-600 text-white py-3.5 rounded-xl font-bold text-base hover:bg-primary-500 transition shadow-lg shadow-primary-600/30 hover:-translate-y-0.5">
                                        Buy Another Package
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <>
                                {!selectedPkg ? (
                                    <>
                                        {netList.length > 0 && (
                                            <div className="flex flex-wrap items-center justify-center gap-3 mb-10">
                                                {[...netList].sort((a, b) => netOrder.indexOf(a) - netOrder.indexOf(b)).map(nid => {
                                                    const n = netInfo(nid);
                                                    return (
                                                        <button key={nid} onClick={() => setActiveNet(nid)}
                                                            className={`px-6 py-2.5 rounded-full text-sm font-bold transition-all duration-300 ${
                                                                activeNet === nid
                                                                    ? `${n?.bg} ${n?.text} shadow-lg shadow-[var(--tw-shadow-color)] scale-105`
                                                                    : 'bg-white text-neutral-600 border border-neutral-200 hover:bg-neutral-50 hover:shadow dark:bg-neutral-900 dark:text-neutral-400 dark:border-neutral-800 dark:hover:bg-neutral-800 dark:hover:text-white'
                                                            }`} style={activeNet === nid ? { '--tw-shadow-color': `${n?.accent}50` } : {}}
                                                        >
                                                            {n?.name || nid}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {activeNet && currentPkgs.length > 0 && (
                                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                                                {currentPkgs.map((pkg, idx) => {
                                                    const netColors = netInfo(activeNet);
                                                    return (
                                                        <div key={pkg.id} onClick={() => handleSelectPkg(pkg)}
                                                            className="group relative bg-white dark:bg-neutral-900 rounded-3xl shadow-sm border border-neutral-200 dark:border-neutral-800 overflow-hidden hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 cursor-pointer animate-in fade-in slide-in-from-bottom-4"
                                                            style={{ animationDelay: `${idx * 50}ms`, animationFillMode: 'both' }}>
                                                            <div className="absolute top-0 inset-x-0 h-1.5 opacity-80 group-hover:opacity-100 transition-opacity" style={{ backgroundColor: netColors?.accent || '#0d9488' }} />
                                                            <div className="p-5 sm:p-6 flex flex-col h-full text-center">
                                                                <p className="text-4xl font-black text-neutral-900 dark:text-white tracking-tighter mt-2">{pkgSize(pkg)}</p>
                                                                <p className="text-xs font-medium text-neutral-500 mt-2 dark:text-neutral-400">GH₵ {Number(pkg.price).toFixed(2)}</p>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {activeNet && currentPkgs.length === 0 && (
                                            <div className="col-span-full text-center py-20 bg-white/50 dark:bg-neutral-900/50 rounded-[2rem] border border-neutral-200 dark:border-neutral-800">
                                                <svg className="w-12 h-12 text-neutral-300 dark:text-neutral-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                <p className="text-lg font-bold text-neutral-500 dark:text-neutral-400">No packages available</p>
                                            </div>
                                        )}

                                        {!activeNet && netList.length === 0 && (
                                            <div className="col-span-full text-center py-20 bg-white/50 dark:bg-neutral-900/50 rounded-[2rem] border border-neutral-200 dark:border-neutral-800">
                                                <p className="text-lg font-bold text-neutral-500 dark:text-neutral-400">No packages available for your account</p>
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="max-w-md mx-auto animate-in slide-in-from-bottom-4 duration-500">
                                        <button onClick={() => { setSelectedPkg(null); setError(null); setSuccess(null); }}
                                            className="flex items-center gap-1.5 text-sm font-semibold text-neutral-500 hover:text-neutral-900 mb-6 transition dark:text-neutral-400 dark:hover:text-white">
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                            Back to Packages
                                        </button>

                                        <div className="bg-white rounded-[2rem] shadow-xl border border-neutral-200 p-8 dark:bg-neutral-900 dark:border-neutral-800">
                                            {activeNetInfo && (
                                                <div className="flex justify-center mb-6">
                                                    <span className={`inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-widest ${activeNetInfo.bg} ${activeNetInfo.text} shadow-sm`}>
                                                        <NetworkIcon net={activeNetInfo} />
                                                        <style>{`.w-12.h-12 { width: 1.5rem; height: 1.5rem; rounded: 100%; } .w-12.h-12 svg { width: 1rem; height: 1rem; }`}</style>
                                                        {activeNetInfo.name}
                                                    </span>
                                                </div>
                                            )}

                                            <div className="text-center mb-8">
                                                <div className="text-4xl font-black text-neutral-900 dark:text-white tracking-tighter">{pkgSize(selectedPkg)}</div>
                                                <div className="w-16 h-1 bg-gradient-to-r from-transparent via-neutral-300 dark:via-neutral-600 to-transparent mx-auto my-5" />
                                                <div className="text-3xl font-extrabold" style={{ color: activeNetInfo?.accent || '#0d9488' }}>
                                                    <span className="text-lg opacity-70 align-top">GH₵</span> {Number(selectedPkg.price).toFixed(2)}
                                                </div>
                                            </div>

                                            <div className="mb-6">
                                                <label className="block text-sm font-bold text-neutral-700 mb-2 dark:text-neutral-300 ml-2">Customer Phone Number</label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                        <svg className="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                                    </div>
                                                    <input type="tel" value={customerPhone}
                                                        onChange={e => setCustomerPhone(e.target.value.replace(/\D/g, '').slice(0, 10))}
                                                        placeholder="054 123 4567"
                                                        className={`w-full bg-neutral-50 dark:bg-neutral-800/50 border-2 rounded-2xl pl-11 pr-4 py-4 text-lg font-medium outline-none transition-all duration-300 dark:text-white border-neutral-200 focus:border-primary-500 dark:border-neutral-700`} />
                                                </div>
                                            </div>

                                            <button onClick={handlePlaceOrder} disabled={loading}
                                                style={{ backgroundColor: activeNetInfo?.accent || '#0d9488', boxShadow: `0 10px 25px -5px ${activeNetInfo?.accent || '#0d9488'}60` }}
                                                className="w-full text-white py-4 rounded-2xl font-bold text-lg hover:opacity-90 disabled:opacity-70 transition-all duration-300 active:scale-[0.98]">
                                                {loading ? 'Processing...' : `Pay GH₵ ${Number(selectedPkg.price).toFixed(2)}`}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </SubAgentLayout>
    );
}
