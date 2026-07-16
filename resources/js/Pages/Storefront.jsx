import { Head } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

const NETWORKS = [
    { id: 'MTN', name: 'MTN', bg: 'bg-yellow-400', text: 'text-yellow-900', iconBg: 'bg-yellow-500', accent: '#eab308', prefix: ['024', '054', '055', '053', '059'] },
    { id: 'VODAFONE', name: 'Telecel', bg: 'bg-red-600', text: 'text-white', iconBg: 'bg-red-500', accent: '#dc2626', prefix: ['020', '050'] },
    { id: 'AIRTELTIGO', name: 'AirtelTigo', bg: 'bg-blue-600', text: 'text-white', iconBg: 'bg-blue-500', accent: '#2563eb', prefix: ['027', '057', '026', '056'] },
];

function pkgSize(pkg) {
    if (!pkg.package_size) return '';
    if (pkg.package_size.endsWith('GB')) return pkg.package_size;
    const mb = parseInt(pkg.package_size);
    if (mb >= 1024) return (mb / 1024) + ' GB';
    return pkg.package_size;
}

function NetworkIcon({ net }) {
    return (
        <div className={`w-14 h-14 rounded-2xl ${net.iconBg} bg-opacity-30 flex items-center justify-center mx-auto transition-transform group-hover:scale-110 duration-300`}>
            <svg className={`w-7 h-7 ${net.text}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
    );
}

export default function Storefront({ store, packagesData, dealer_name, ref_code, sub_agent_name, paymentStatus, storeClosed, closedReason }) {
    if (storeClosed) {
        const themeColor = store?.theme_color || '#1d4ed8';
        return (
            <>
                <Head title={store?.name || 'Store'} />
                <div className="min-h-screen bg-neutral-50 dark:bg-neutral-900 flex flex-col items-center justify-center p-8 relative overflow-hidden">
                    {/* Decorative Background for Closed Store */}
                    <div className="absolute top-0 w-full h-96 opacity-30 blur-3xl pointer-events-none" style={{ background: `linear-gradient(135deg, ${themeColor} 0%, transparent 100%)` }}></div>
                    <header className="relative w-full text-center py-12 mb-8 z-10">
                        {store?.logo_url && (
                            <div className="relative inline-block animate-pulse-glow mb-6">
                                <div className="absolute inset-0 bg-white/30 rounded-2xl blur-xl"></div>
                                <img src={store.logo_url} alt={store.name} className="relative w-24 h-24 rounded-2xl object-cover mx-auto ring-4 ring-white/40 shadow-2xl backdrop-blur-md" />
                            </div>
                        )}
                        <h1 className="text-4xl font-extrabold text-neutral-900 dark:text-white tracking-tight drop-shadow-sm">{store?.name || 'Store'}</h1>
                    </header>
                    <div className="relative bg-white/80 dark:bg-neutral-800/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 text-center max-w-md w-full border border-white/20 z-10">
                        <div className="w-20 h-20 bg-neutral-100/50 dark:bg-neutral-700/50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg className="w-10 h-10 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <h2 className="text-2xl font-bold text-neutral-900 dark:text-white mb-3">Store Unavailable</h2>
                        <p className="text-neutral-500 dark:text-neutral-400 text-lg leading-relaxed">{closedReason || 'This store is currently taking a break. Please check back later.'}</p>
                    </div>
                </div>
            </>
        );
    }
    const [activeNet, setActiveNet] = useState(null);
    const [selectedPkg, setSelectedPkg] = useState(null);
    const [phone, setPhone] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const pkgs = activeNet ? (packagesData[activeNet] || []) : [];

    const openModal = (pkg) => {
        setSelectedPkg(pkg);
        setPhone('');
        setError(null);
    };

    const closeModal = () => {
        setSelectedPkg(null);
        setError(null);
    };

    const handleBuy = async () => {
        const cleanPhone = phone.replace(/\D/g, '');
        if (cleanPhone.length !== 10) {
            setError('Enter a valid 10-digit phone number');
            return;
        }
        setLoading(true);
        setError(null);
        try {
            const res = await axios.post(route('store.checkout', store.slug), {
                package_id: selectedPkg.id,
                customer_phone: cleanPhone,
                ref_code: ref_code || null,
            });
            if (res.data.success && res.data.data.authorization_url) {
                window.location.href = res.data.data.authorization_url;
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong');
        } finally {
            setLoading(false);
        }
    };

    const themeColor = store.theme_color || '#1d4ed8';
    const accentColor = store.accent_color || '#f59e0b';
    const activeNetInfo = NETWORKS.find(n => n.id === activeNet);

    return (
        <>
            <Head title={store.name} />
            <div className="min-h-screen bg-neutral-50 dark:bg-neutral-900 flex flex-col font-sans selection:bg-primary-500/30">
                {paymentStatus === 'cancelled' && (
                    <div className="bg-amber-500/10 backdrop-blur-md border-b border-amber-500/20 px-4 py-4 text-center text-sm font-medium text-amber-800 dark:text-amber-300 shadow-sm z-50">
                        Payment was cancelled. No charges were made. You can try again below.
                    </div>
                )}
                {paymentStatus === 'failed' && (
                    <div className="bg-red-500/10 backdrop-blur-md border-b border-red-500/20 px-4 py-4 text-center text-sm font-medium text-red-700 dark:text-red-300 shadow-sm z-50">
                        Payment failed. Please try again or contact support.
                    </div>
                )}
                {/* Immersive Header */}
                <header className="relative overflow-hidden pt-12 pb-20 sm:pt-16 sm:pb-24 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)]" style={{ background: `linear-gradient(135deg, ${themeColor} 0%, ${themeColor}dd 100%)` }}>
                    {/* Decorative Mesh Elements */}
                    <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, white 1px, transparent 0)', backgroundSize: '32px 32px' }} />
                    <div className="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div className="absolute top-1/2 -left-32 w-80 h-80 rounded-full bg-black/10 blur-3xl animate-float-delayed" />
                    <div className="absolute bottom-0 w-full h-32 bg-gradient-to-t from-black/20 to-transparent" />

                    <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col items-center text-center">
                            {store.logo_url && (
                                <div className="shrink-0 mb-6 relative animate-pulse-glow group">
                                    <div className="absolute inset-0 bg-white/40 rounded-3xl blur-xl group-hover:bg-white/60 transition duration-500"></div>
                                    <img src={store.logo_url} alt={store.name}
                                        className="relative w-28 h-28 sm:w-32 sm:h-32 rounded-3xl object-cover ring-4 ring-white/30 shadow-[0_20px_50px_rgba(0,0,0,0.2)] backdrop-blur-xl transition-transform duration-500 group-hover:scale-105" />
                                </div>
                            )}
                            <div className="max-w-2xl">
                                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight drop-shadow-md">
                                    {store.name}
                                </h1>
                                {store.description && (
                                    <p className="text-lg sm:text-xl text-white/90 mt-4 font-medium drop-shadow-sm max-w-xl mx-auto leading-relaxed">{store.description}</p>
                                )}
                                {store.hero_headline && (
                                    <p className="mt-4 text-white/70 text-base sm:text-lg italic">{store.hero_headline}</p>
                                )}
                                {sub_agent_name && (
                                    <div className="mt-6 inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-black/20 backdrop-blur-md border border-white/10 text-white/90 text-sm font-medium shadow-inner">
                                        <svg className="w-5 h-5 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Referred by {sub_agent_name}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Announcement Banner */}
                {store.announcement_text && (
                    <div className="relative z-20 text-center text-sm font-bold tracking-wide py-3 px-4 shadow-md" style={{ backgroundColor: accentColor, color: '#fff' }}>
                        {store.announcement_text}
                    </div>
                )}

                <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-10 relative z-30">
                    {!activeNet ? (
                        <div className="animate-in fade-in slide-in-from-bottom-4 duration-700 ease-out text-center">
                            <div className="text-center mb-10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-xl py-6 px-8 rounded-3xl shadow-sm border border-white/50 dark:border-white/5 inline-block mx-auto">
                                <h3 className="text-xl font-bold text-neutral-900 dark:text-white mb-1">Select Your Network</h3>
                                <p className="text-neutral-500 dark:text-neutral-400 text-sm">Choose your provider to see the best data deals</p>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 max-w-4xl mx-auto">
                                {NETWORKS.map((net) => (
                                    <div key={net.id}
                                        onClick={() => { setActiveNet(net.id); setError(null); }}
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
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-xl p-4 rounded-3xl border border-white/50 dark:border-white/5 shadow-sm">
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

                            {pkgs.length === 0 ? (
                                <div className="text-center py-20 bg-white/50 dark:bg-neutral-800/50 backdrop-blur-md rounded-3xl border border-white/20 dark:border-white/5">
                                    <svg className="w-16 h-16 text-neutral-300 dark:text-neutral-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <p className="text-xl font-bold text-neutral-600 dark:text-neutral-400">No bundles available right now.</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
                                    {pkgs.map((pkg, idx) => (
                                        <div key={pkg.id}
                                            onClick={() => openModal(pkg)}
                                            className="group relative bg-white/80 dark:bg-neutral-800/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 dark:border-white/5 overflow-hidden hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 cursor-pointer animate-in fade-in slide-in-from-bottom-4"
                                            style={{ animationDelay: `${idx * 50}ms`, animationFillMode: 'both' }}>
                                            <div className="absolute top-0 inset-x-0 h-1.5 opacity-80 group-hover:opacity-100 transition-opacity" style={{ backgroundColor: activeNetInfo.accent }} />
                                            <div className="p-5 sm:p-6 flex flex-col h-full">
                                                <p className="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">{pkgSize(pkg)}</p>
                                                <p className="text-xs font-medium text-neutral-500 mt-1 dark:text-neutral-400 line-clamp-2 min-h-[32px]">{pkg.package_name}</p>
                                                
                                                <div className="mt-auto pt-6">
                                                    <p className="text-xl font-extrabold mb-4" style={{ color: activeNetInfo.accent }}>
                                                        <span className="text-sm font-semibold opacity-70">GH₵</span> {pkg.price.toFixed(2)}
                                                    </p>
                                                    <button className="w-full py-2.5 rounded-full text-sm font-bold text-white shadow-md transition-transform group-hover:scale-105 active:scale-95"
                                                        style={{ backgroundColor: activeNetInfo.accent }}>
                                                        Buy Now
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Contact & Footer */}
                    <div className="mt-20 pt-8 border-t border-neutral-200/50 dark:border-neutral-800/50 text-center text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        <div className="flex flex-wrap items-center justify-center gap-6 mb-6">
                            {store.contact_phone && (
                                <a href={`tel:${store.contact_phone}`} className="inline-flex items-center gap-2 hover:text-neutral-900 dark:hover:text-white transition-colors bg-white/50 dark:bg-neutral-800/50 backdrop-blur-md px-4 py-2 rounded-full border border-white/20 dark:border-white/5 shadow-sm">
                                    <div className="w-6 h-6 rounded-full bg-neutral-100 dark:bg-neutral-700 flex items-center justify-center"><svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg></div>
                                    {store.contact_phone}
                                </a>
                            )}
                            {store.contact_whatsapp && (
                                <a href={`https://wa.me/${store.contact_whatsapp}`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 hover:text-green-600 dark:hover:text-green-400 transition-colors bg-white/50 dark:bg-neutral-800/50 backdrop-blur-md px-4 py-2 rounded-full border border-white/20 dark:border-white/5 shadow-sm">
                                    <div className="w-6 h-6 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center"><svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" /></svg></div>
                                    WhatsApp
                                </a>
                            )}
                        </div>
                        {store.footer_tagline && (
                            <div className="pb-8 text-neutral-400 dark:text-neutral-600">
                                {store.footer_tagline}
                            </div>
                        )}
                    </div>
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
                                <p className="text-4xl font-black text-neutral-900 dark:text-white tracking-tighter">{pkgSize(selectedPkg)}</p>
                                <p className="text-sm font-medium text-neutral-500 mt-2 dark:text-neutral-400">{selectedPkg.package_name}</p>
                                <div className="w-16 h-1 bg-gradient-to-r from-transparent via-neutral-300 dark:via-neutral-600 to-transparent mx-auto my-5" />
                                <p className="text-3xl font-extrabold tracking-tight" style={{ color: themeColor }}>
                                    <span className="text-lg opacity-70 align-top">GH₵</span> {selectedPkg.price.toFixed(2)}
                                </p>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <label className="block text-sm font-bold text-neutral-700 mb-2 dark:text-neutral-300 ml-2">Mobile Number</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg className="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                        </div>
                                        <input type="tel" value={phone}
                                            onChange={e => { setPhone(e.target.value.replace(/\D/g, '').slice(0, 10)); setError(null); }}
                                            placeholder="024 XXX XXXX"
                                            autoFocus
                                            className="w-full bg-white/50 dark:bg-neutral-900/50 border-2 border-neutral-200 dark:border-neutral-700 rounded-2xl pl-11 pr-4 py-4 text-lg font-medium text-neutral-900 dark:text-white placeholder-neutral-400 outline-none transition-all duration-300 focus:bg-white dark:focus:bg-neutral-900" 
                                            style={{ '--tw-ring-color': themeColor, outlineColor: themeColor }}
                                            onFocus={(e) => e.target.style.borderColor = themeColor}
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

                                <button onClick={handleBuy} disabled={loading}
                                    style={{ backgroundColor: themeColor, boxShadow: `0 10px 25px -5px ${themeColor}60` }}
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
                                                Pay <span className="opacity-80 font-medium">GH₵</span> {selectedPkg.price.toFixed(2)}
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
        </>
    );
}
