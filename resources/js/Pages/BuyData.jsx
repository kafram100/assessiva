import { useState, useEffect, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout';

const NETWORKS = [
    { id: 'MTN', name: 'MTN', bg: 'bg-yellow-400', text: 'text-yellow-900', iconBg: 'bg-yellow-500', accent: '#eab308' },
    { id: 'VODAFONE', name: 'Telecel', bg: 'bg-red-600', text: 'text-white', iconBg: 'bg-red-500', accent: '#dc2626' },
    { id: 'AIRTELTIGO', name: 'AirtelTigo', bg: 'bg-blue-600', text: 'text-white', iconBg: 'bg-blue-500', accent: '#2563eb' },
];

function NetworkIcon({ net }) {
    if (!net) return null;
    return (
        <div className={`w-14 h-14 rounded-2xl ${net.iconBg || 'bg-primary-500'} bg-opacity-30 flex items-center justify-center mx-auto transition-transform group-hover:scale-110 duration-300`}>
            <svg className={`w-7 h-7 ${net.text || 'text-primary-900'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
    );
}

function pkgSize(pkg) {
    const m = pkg.package_size?.match(/([\d.]+)\s*(GB|MB)/i);
    if (!m) return pkg.package_size;
    if (m[2] === 'GB') return `${m[1]}GB`;
    const gb = parseFloat(m[1]) / 1024;
    return gb % 1 === 0 ? `${gb}GB` : `${gb.toFixed(1)}GB`;
}

const pctFee = 0.02;

function pkgPrice(pkg) {
    return parseFloat(pkg.price || pkg.selling_price).toFixed(2);
}

export default function BuyData() {
    const [packages, setPackages] = useState({});
    const [netList, setNetList] = useState([]);
    const [activeNet, setActiveNet] = useState(null);
    const [selectedPkg, setSelectedPkg] = useState(null);
    const [beneficiary, setBeneficiary] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        let cancelled = false;
        axios.get(route('api.packages')).then(res => {
            if (cancelled) return;
            if (res.data.success) {
                setPackages(res.data.data);
                setNetList(res.data.networks || []);
            }
        }).catch(() => {
            if (!cancelled) setError('Failed to load packages. Please refresh the page.');
        });
        return () => { cancelled = true; };
    }, []);

    const hasMashup = useMemo(() => !!(packages['MTN_MASHUP']?.length), [packages]);
    const allNetworks = useMemo(() => [
        ...NETWORKS, 
        ...(hasMashup ? [{ id: 'MTN_MASHUP', name: 'MTN Mashup', bg: 'bg-yellow-400', text: 'text-yellow-900', iconBg: 'bg-yellow-500', accent: '#eab308' }] : [])
    ], [hasMashup]);

    const currentPkgs = useMemo(() => {
        return activeNet ? (packages[activeNet] || []) : [];
    }, [activeNet, packages]);

    const isMashup = useMemo(() => selectedPkg?.network_type === 'MTN_MASHUP', [selectedPkg]);

    const selectNetwork = (netId) => {
        setActiveNet(netId);
        setSelectedPkg(null);
        setError(null);
        setBeneficiary('');
    };

    const handleSelectPkg = (pkg) => {
        setSelectedPkg(pkg);
        setError(null);
        setBeneficiary('');
    };

    const closeModal = () => {
        setSelectedPkg(null);
        setError(null);
        setBeneficiary('');
    };

    const handlePayNow = async () => {
        const num = beneficiary.replace(/\D/g, '');
        if (num.length < 10) {
            setError('Please enter a valid 10-digit beneficiary number.');
            return;
        }
        setLoading(true);
        setError(null);
        try {
            const res = await axios.post(route('api.checkout'), {
                customer_phone: num,
                package_id: selectedPkg.id,
                beneficiary_number: num,
                is_mashup: isMashup ? 1 : 0,
            });
            if (res.data.success) {
                window.location.href = res.data.data.authorization_url;
                return;
            }
            setError(res.data.message || 'Something went wrong');
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong');
        }
        setLoading(false);
    };

    const activeNetInfo = allNetworks.find(n => n.id === activeNet);

    return (
        <PublicLayout>
            <Head title="AirLinks - Buy Data Instantly" />

            <div className="min-h-screen bg-neutral-50 dark:bg-neutral-900 font-sans selection:bg-primary-500/30 pb-20">
                {/* Immersive Header */}
                <header className="relative overflow-hidden pt-12 pb-20 sm:pt-16 sm:pb-24 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] bg-gradient-to-br from-primary-800 to-primary-600">
                    {/* Decorative Mesh Elements */}
                    <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, white 1px, transparent 0)', backgroundSize: '32px 32px' }} />
                    <div className="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div className="absolute top-1/2 -left-32 w-80 h-80 rounded-full bg-black/10 blur-3xl animate-float-delayed" />
                    <div className="absolute bottom-0 w-full h-32 bg-gradient-to-t from-black/20 to-transparent" />

                    <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col items-center text-center">
                            <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md rounded-full px-4 py-1.5 text-sm font-medium text-white mb-6 border border-white/20 shadow-inner">
                                <span className="relative flex h-2.5 w-2.5">
                                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                                </span>
                                Instant Delivery System Active
                            </div>
                            <div className="max-w-2xl">
                                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight drop-shadow-md">
                                    Data Bundles.<br />
                                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary-200 to-primary-100">Made Simple.</span>
                                </h1>
                                <p className="text-lg sm:text-xl text-white/90 mt-5 font-medium drop-shadow-sm max-w-xl mx-auto leading-relaxed">
                                    Buy data for any Ghanaian network instantly. Pay securely with mobile money or card.
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-10 relative z-30">
                    {!activeNet ? (
                        <div className="animate-in fade-in slide-in-from-bottom-4 duration-700 ease-out text-center">
                            <div className="text-center mb-10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-xl py-6 px-8 rounded-3xl shadow-sm border border-white/50 dark:border-white/5 inline-block mx-auto">
                                <h3 className="text-xl font-bold text-neutral-900 dark:text-white mb-1">Select Your Network</h3>
                                <p className="text-neutral-500 dark:text-neutral-400 text-sm">Choose your provider to see the best data deals</p>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 max-w-5xl mx-auto">
                                {allNetworks.map((net) => (
                                    <div key={net.id}
                                        onClick={() => selectNetwork(net.id)}
                                        className="group relative rounded-3xl p-8 text-center cursor-pointer transition-all duration-300 hover:-translate-y-2"
                                        style={{ '--tw-shadow-color': `${net.accent}40` }}>
                                        {/* Glassmorphic Background */}
                                        <div className={`absolute inset-0 rounded-3xl backdrop-blur-xl bg-white/70 dark:bg-neutral-800/80 border border-white/40 dark:border-white/10 shadow-xl shadow-[var(--tw-shadow-color)] transition-all duration-300 group-hover:shadow-2xl group-hover:shadow-[var(--tw-shadow-color)]`}></div>
                                        {/* Colored Glow behind icon */}
                                        <div className={`absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-10 transition-opacity duration-300 ${net.bg}`}></div>
                                        
                                        <div className="relative z-10">
                                            <NetworkIcon net={net} />
                                            <p className={`text-2xl font-extrabold mt-5 tracking-tight text-neutral-900 dark:text-white`}>{net.name}</p>
                                            <div className="mt-3 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform translate-y-2 group-hover:translate-y-0">
                                                <span className={`text-sm font-semibold flex items-center gap-1`} style={{ color: net.accent }}>
                                                    View Bundles
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"/></svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <div className="animate-in fade-in duration-500 ease-out">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-xl p-4 rounded-3xl border border-white/50 dark:border-white/5 shadow-sm max-w-5xl mx-auto">
                                <div className="flex items-center gap-3">
                                    <button onClick={() => { setActiveNet(null); setSelectedPkg(null); }}
                                        className="p-3 rounded-2xl bg-white dark:bg-neutral-700 shadow-sm text-neutral-500 hover:text-neutral-900 hover:shadow-md transition-all dark:text-neutral-300 dark:hover:text-white">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <div>
                                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-0.5">Selected Network</p>
                                        {activeNetInfo && (
                                            <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-sm font-bold ${activeNetInfo.bg} ${activeNetInfo.text} shadow-sm`}>
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                {activeNetInfo.name}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {currentPkgs.length === 0 ? (
                                <div className="text-center py-20 max-w-5xl mx-auto bg-white/50 dark:bg-neutral-800/50 backdrop-blur-md rounded-3xl border border-white/20 dark:border-white/5">
                                    <svg className="w-16 h-16 text-neutral-300 dark:text-neutral-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <p className="text-xl font-bold text-neutral-600 dark:text-neutral-400">No packages available right now.</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6 max-w-7xl mx-auto">
                                    {currentPkgs.map((pkg, idx) => {
                                        const isMtnMashup = activeNet === 'MTN_MASHUP';
                                        return (
                                        <div key={pkg.id}
                                            onClick={() => handleSelectPkg(pkg)}
                                            className="group relative bg-white/80 dark:bg-neutral-800/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 dark:border-white/5 overflow-hidden hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 cursor-pointer animate-in fade-in slide-in-from-bottom-4"
                                            style={{ animationDelay: `${idx * 50}ms`, animationFillMode: 'both' }}>
                                            <div className="absolute top-0 inset-x-0 h-1.5 opacity-80 group-hover:opacity-100 transition-opacity" style={{ backgroundColor: activeNetInfo?.accent || '#0d9488' }} />
                                            {pkg.non_expiring && (
                                                <div className="absolute top-3 right-3 bg-neutral-900 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm">
                                                    NO EXPIRY
                                                </div>
                                            )}
                                            <div className="p-5 sm:p-6 flex flex-col h-full">
                                                {isMtnMashup ? (
                                                    <>
                                                        <p className="text-xl font-black text-neutral-900 dark:text-white tracking-tight leading-tight">{pkg.package_name}</p>
                                                        {pkg.package_size_gb > 0 && <p className="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mt-2">{pkg.package_size_gb} GB Data</p>}
                                                        {pkg.voice_minutes > 0 && <p className="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{pkg.voice_minutes} Mins Voice</p>}
                                                    </>
                                                ) : (
                                                    <>
                                                        <p className="text-3xl font-black text-neutral-900 dark:text-white tracking-tight">{pkgSize(pkg)}</p>
                                                        <p className="text-xs font-medium text-neutral-500 mt-1 dark:text-neutral-400 line-clamp-2 min-h-[32px]">{pkg.package_name}</p>
                                                    </>
                                                )}
                                                
                                                <div className="mt-auto pt-6">
                                                    <p className="text-2xl font-extrabold mb-4" style={{ color: activeNetInfo?.accent || '#0d9488' }}>
                                                        <span className="text-sm font-semibold opacity-70">GH₵</span> {pkgPrice(pkg)}
                                                    </p>
                                                    <button className="w-full py-2.5 rounded-full text-sm font-bold text-white shadow-md transition-transform group-hover:scale-105 active:scale-95"
                                                        style={{ backgroundColor: activeNetInfo?.accent || '#0d9488' }}>
                                                        Buy Now
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    )})}
                                </div>
                            )}
                        </div>
                    )}
                </main>

                {/* Sleek Checkout Modal */}
                {selectedPkg && (
                    <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 animate-in fade-in duration-300">
                        <div className="absolute inset-0 bg-neutral-900/60 backdrop-blur-md" onClick={closeModal} />
                        <div className="relative bg-white/95 dark:bg-neutral-800/95 backdrop-blur-2xl rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full max-w-md p-8 pt-10 sm:p-10 border border-white/20 animate-in slide-in-from-bottom-8 sm:zoom-in-95 duration-500 ease-out"
                            onClick={e => e.stopPropagation()}>
                            <div className="absolute top-4 left-1/2 -translate-x-1/2 w-16 h-1.5 bg-neutral-200 dark:bg-neutral-700 rounded-full sm:hidden" />
                            <button onClick={closeModal}
                                className="absolute top-6 right-6 p-2 rounded-full text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 dark:hover:text-neutral-300 dark:hover:bg-neutral-700 transition duration-300">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            {activeNetInfo && (
                                <div className="flex justify-center mb-6">
                                    <span className={`inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-widest ${activeNetInfo.bg} ${activeNetInfo.text} shadow-sm`}>
                                        <NetworkIcon net={activeNetInfo} />
                                        {/* Override the icon width/height locally */}
                                        <style>{`.w-14.h-14 { width: 1.5rem; height: 1.5rem; } .w-14.h-14 svg { width: 1rem; height: 1rem; }`}</style>
                                        {activeNetInfo.name}
                                    </span>
                                </div>
                            )}

                            <div className="text-center mb-8">
                                {isMashup ? (
                                    <>
                                        <p className="text-3xl font-black text-neutral-900 dark:text-white tracking-tight leading-tight">{selectedPkg.package_name}</p>
                                        {selectedPkg.package_size_gb > 0 && <p className="text-lg font-semibold text-neutral-500 mt-2 dark:text-neutral-400">{selectedPkg.package_size_gb} GB Data</p>}
                                        {selectedPkg.voice_minutes > 0 && <p className="text-lg font-semibold text-neutral-500 dark:text-neutral-400">{selectedPkg.voice_minutes} Mins Voice</p>}
                                    </>
                                ) : (
                                    <>
                                        <p className="text-4xl font-black text-neutral-900 dark:text-white tracking-tighter">{pkgSize(selectedPkg)}</p>
                                        <p className="text-sm font-medium text-neutral-500 mt-2 dark:text-neutral-400">{selectedPkg.package_name}</p>
                                    </>
                                )}
                                
                                <div className="w-16 h-1 bg-gradient-to-r from-transparent via-neutral-300 dark:via-neutral-600 to-transparent mx-auto my-5" />
                                <p className="text-3xl font-extrabold tracking-tight" style={{ color: activeNetInfo?.accent || '#0d9488' }}>
                                    <span className="text-lg opacity-70 align-top">GH₵</span> {pkgPrice(selectedPkg)}
                                </p>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <label className="block text-sm font-bold text-neutral-700 mb-2 dark:text-neutral-300 ml-2">Beneficiary Number</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg className="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                        </div>
                                        <input type="tel" value={beneficiary}
                                            onChange={e => { setBeneficiary(e.target.value.replace(/\D/g, '').slice(0, 10)); setError(null); }}
                                            placeholder="024 XXX XXXX"
                                            autoFocus
                                            className="w-full bg-white/50 dark:bg-neutral-900/50 border-2 border-neutral-200 dark:border-neutral-700 rounded-2xl pl-11 pr-4 py-4 text-lg font-medium text-neutral-900 dark:text-white placeholder-neutral-400 outline-none transition-all duration-300 focus:bg-white dark:focus:bg-neutral-900" 
                                            style={{ '--tw-ring-color': activeNetInfo?.accent || '#0d9488', outlineColor: activeNetInfo?.accent || '#0d9488' }}
                                            onFocus={(e) => e.target.style.borderColor = activeNetInfo?.accent || '#0d9488'}
                                            onBlur={(e) => e.target.style.borderColor = ''}
                                        />
                                    </div>
                                </div>

                                {error && (
                                    <div className="flex items-center gap-2 text-red-600 text-sm font-medium bg-red-50 border border-red-100 rounded-xl px-4 py-3 dark:text-red-400 dark:bg-red-900/20 dark:border-red-900/30 animate-in shake">
                                        <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        {error}
                                    </div>
                                )}

                                <button onClick={handlePayNow} disabled={loading}
                                    style={{ backgroundColor: activeNetInfo?.accent || '#0d9488', boxShadow: `0 10px 25px -5px ${activeNetInfo?.accent || '#0d9488'}60` }}
                                    className="relative w-full text-white py-4 rounded-2xl font-bold text-lg hover:opacity-90 disabled:opacity-70 transition-all duration-300 active:scale-[0.98] overflow-hidden group">
                                    <div className="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                                    <div className="relative flex items-center justify-center gap-2">
                                        {loading ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Processing Securely...
                                            </>
                                        ) : (
                                            <>
                                                Pay <span className="opacity-80 font-medium">GH₵</span> {pkgPrice(selectedPkg)}
                                                <svg className="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            </>
                                        )}
                                    </div>
                                </button>
                                <p className="text-center text-xs text-neutral-400 dark:text-neutral-500 font-medium flex items-center justify-center gap-1.5 mt-2">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Secured by Paystack
                                </p>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}