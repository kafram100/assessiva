import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState, useRef } from 'react';

export default function Store({ store }) {
    const { flash } = usePage().props;
    const fileInputRef = useRef(null);
    const [form, setForm] = useState({
        store_name: store.store_name || '',
        store_slug: store.store_slug || '',
        store_description: store.store_description || '',
        is_active: store.is_active === true,
        theme_color: store.theme_color || '#1d4ed8',
        accent_color: store.accent_color || '#f59e0b',
        contact_phone: store.contact_phone || '',
        contact_whatsapp: store.contact_whatsapp || '',
        hero_headline: store.hero_headline || '',
        announcement_text: store.announcement_text || '',
        footer_tagline: store.footer_tagline || '',
    });
    const [logoFile, setLogoFile] = useState(null);
    const [logoPreview, setLogoPreview] = useState(store.logo_url || null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [copied, setCopied] = useState(false);

    const [flashMsg, setFlashMsg] = useState(flash?.success || null);

    const storeUrl = window.location.origin + '/store/' + form.store_slug;

    const handleLogoChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        setLogoFile(file);
        setLogoPreview(URL.createObjectURL(file));
    };

    const removeLogo = () => {
        setLogoFile(null);
        setLogoPreview(null);
    };

    const copyLink = () => {
        navigator.clipboard.writeText(storeUrl).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        const payload = new FormData();
        Object.entries(form).forEach(([key, val]) => {
            payload.append(key, key === 'is_active' ? (val ? '1' : '0') : val);
        });
        if (logoFile) {
            payload.append('logo', logoFile);
        }

        try {
            await router.post(route('dealer.store.update'), payload, {
                preserveState: true,
                preserveScroll: true,
                headers: { 'Content-Type': 'multipart/form-data' },
                onSuccess: () => {
                    setFlashMsg('Store settings updated successfully.');
                    if (logoFile) setLogoFile(null);
                },
                onError: (errs) => setError(Object.values(errs).flat().join(', ')),
            });
        } catch {
            setError('Something went wrong');
        } finally {
            setLoading(false);
        }
    };

    const inputClass = 'w-full border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    Store Settings
                </h2>
            }
        >
            <Head title="Store Settings" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {flashMsg && (
                        <div className="px-5 py-3 rounded-xl text-sm font-medium bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                            {flashMsg}
                        </div>
                    )}

                    {/* Store Link Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-5 dark:bg-neutral-900 dark:border-neutral-800">
                        <h3 className="text-sm font-semibold text-neutral-700 mb-3 dark:text-neutral-300">Your Store Link</h3>
                        <div className="flex items-center gap-2">
                            <input type="text" value={storeUrl} readOnly
                                className="flex-1 border-2 border-neutral-200 rounded-xl px-4 py-2.5 text-sm bg-neutral-50 text-neutral-600 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300" />
                            <a href={storeUrl} target="_blank" rel="noopener noreferrer"
                                className="p-2.5 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition" title="Preview store">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <button onClick={copyLink}
                                className="p-2.5 bg-neutral-100 text-neutral-600 rounded-xl hover:bg-neutral-200 transition dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700" title="Copy link">
                                {copied ? (
                                    <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                ) : (
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                )}
                            </button>
                        </div>
                        <p className="text-xs text-neutral-400 mt-2">Share this link with customers to let them buy from your store.</p>
                    </div>

                    {/* Store Settings Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-6 dark:bg-neutral-900 dark:border-neutral-800">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Header: Title + Toggle */}
                            <div className="flex items-center justify-between pb-4 border-b border-neutral-100 dark:border-neutral-800">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-neutral-200">Store Configuration</h3>
                                <label className="inline-flex items-center cursor-pointer gap-3">
                                    <span className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                        {form.is_active ? 'Open' : 'Closed'}
                                    </span>
                                    <div className="relative">
                                        <input type="checkbox" checked={form.is_active}
                                            onChange={e => setForm({ ...form, is_active: e.target.checked })}
                                            className="sr-only peer" />
                                        <div className="w-11 h-6 bg-neutral-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600 dark:bg-neutral-600"></div>
                                    </div>
                                </label>
                            </div>

                            {/* Logo Upload */}
                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-2 dark:text-neutral-300">Store Logo</label>
                                <div className="flex items-center gap-4">
                                    <div className="w-20 h-20 rounded-xl border-2 border-dashed border-neutral-300 flex items-center justify-center overflow-hidden bg-neutral-50 dark:bg-neutral-800 dark:border-neutral-600">
                                        {logoPreview ? (
                                            <img src={logoPreview} alt="Logo" className="w-full h-full object-cover" />
                                        ) : (
                                            <svg className="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <input type="file" ref={fileInputRef} accept="image/*"
                                            onChange={handleLogoChange}
                                            className="hidden" />
                                        <button type="button" onClick={() => fileInputRef.current?.click()}
                                            className="px-4 py-2 border-2 border-neutral-200 rounded-xl text-sm font-medium text-neutral-600 hover:bg-neutral-50 transition dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                                            {logoPreview ? 'Change Logo' : 'Upload Logo'}
                                        </button>
                                        {logoPreview && (
                                            <button type="button" onClick={removeLogo}
                                                className="ml-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-xl transition dark:text-red-400 dark:hover:bg-red-900/20">
                                                Remove
                                            </button>
                                        )}
                                        <p className="text-xs text-neutral-400 mt-1">JPEG, PNG, WebP. Max 2MB.</p>
                                    </div>
                                </div>
                            </div>

                            {/* Store Name & Slug */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Store Name *</label>
                                    <input type="text" value={form.store_name}
                                        onChange={e => setForm({ ...form, store_name: e.target.value })}
                                        className={inputClass} required />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Store Slug *</label>
                                    <input type="text" value={form.store_slug}
                                        onChange={e => setForm({ ...form, store_slug: e.target.value })}
                                        className={inputClass} required />
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Description</label>
                                <textarea value={form.store_description}
                                    onChange={e => setForm({ ...form, store_description: e.target.value })}
                                    rows="3" className={inputClass} />
                            </div>

                            {/* Hero Headline */}
                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Hero Headline</label>
                                <input type="text" value={form.hero_headline}
                                    onChange={e => setForm({ ...form, hero_headline: e.target.value })}
                                    placeholder="e.g. Get the best data deals at amazing prices!"
                                    className={inputClass} />
                                <p className="text-xs text-neutral-400 mt-1">Big text shown at the top of your store page.</p>
                            </div>

                            {/* Announcement */}
                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Announcement Banner</label>
                                <input type="text" value={form.announcement_text}
                                    onChange={e => setForm({ ...form, announcement_text: e.target.value })}
                                    placeholder="e.g. Free delivery on all bundles this week!"
                                    className={inputClass} />
                                <p className="text-xs text-neutral-400 mt-1">A highlighted banner shown below the header.</p>
                            </div>

                            {/* Footer Tagline */}
                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Footer Tagline</label>
                                <input type="text" value={form.footer_tagline}
                                    onChange={e => setForm({ ...form, footer_tagline: e.target.value })}
                                    placeholder="e.g. Powered by AirLinks — Fast & Reliable Data Delivery"
                                    className={inputClass} />
                            </div>

                            {/* Contact Info */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Contact Phone</label>
                                    <input type="tel" value={form.contact_phone}
                                        onChange={e => setForm({ ...form, contact_phone: e.target.value })}
                                        className={inputClass} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">WhatsApp</label>
                                    <input type="tel" value={form.contact_whatsapp}
                                        onChange={e => setForm({ ...form, contact_whatsapp: e.target.value })}
                                        className={inputClass} />
                                </div>
                            </div>

                            {/* Colors */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Theme Color</label>
                                    <div className="flex gap-2">
                                        <input type="color" value={form.theme_color}
                                            onChange={e => setForm({ ...form, theme_color: e.target.value })}
                                            className="w-10 h-10 rounded-lg border border-neutral-200 cursor-pointer p-0.5 dark:border-neutral-700" />
                                        <input type="text" value={form.theme_color}
                                            onChange={e => setForm({ ...form, theme_color: e.target.value })}
                                            className="flex-1 border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white font-mono text-sm" />
                                    </div>
                                    <p className="text-xs text-neutral-400 mt-1">Header & button color.</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Accent Color</label>
                                    <div className="flex gap-2">
                                        <input type="color" value={form.accent_color}
                                            onChange={e => setForm({ ...form, accent_color: e.target.value })}
                                            className="w-10 h-10 rounded-lg border border-neutral-200 cursor-pointer p-0.5 dark:border-neutral-700" />
                                        <input type="text" value={form.accent_color}
                                            onChange={e => setForm({ ...form, accent_color: e.target.value })}
                                            className="flex-1 border-2 border-neutral-200 rounded-xl px-4 py-2.5 focus:border-primary-500 outline-none transition dark:bg-neutral-800 dark:border-neutral-700 dark:text-white font-mono text-sm" />
                                    </div>
                                    <p className="text-xs text-neutral-400 mt-1">Badges & highlights color.</p>
                                </div>
                            </div>

                            {/* Payment (read-only) */}
                            <div>
                                <label className="block text-sm font-medium text-neutral-700 mb-1 dark:text-neutral-300">Payment Gateway</label>
                                <input type="text" value="Paystack" disabled
                                    className={inputClass + ' bg-neutral-50 text-neutral-500 dark:bg-neutral-800'} />
                                <p className="text-xs text-neutral-400 mt-1">Paystack is the only payment gateway for storefronts. Configure your keys in <a href={route('dealer.payment-settings')} className="text-primary-600 underline">Payment Settings</a>.</p>
                            </div>

                            {error && (
                                <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3 dark:text-red-400 dark:bg-red-900/20">{error}</p>
                            )}

                            <button type="submit" disabled={loading}
                                className="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition">
                                {loading ? 'Saving...' : 'Save Settings'}
                            </button>
                        </form>
                    </div>

                    {/* Preview Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-6 dark:bg-neutral-900 dark:border-neutral-800">
                        <h3 className="text-sm font-semibold text-neutral-700 mb-3 dark:text-neutral-300">Store Preview</h3>
                        <div className="border border-neutral-200 rounded-xl overflow-hidden dark:border-neutral-700">
                            <div className="p-4 text-center" style={{ backgroundColor: form.theme_color || '#1d4ed8' }}>
                                {logoPreview && (
                                    <img src={logoPreview} alt="Logo" className="h-12 mx-auto mb-2 rounded-lg" />
                                )}
                                <h4 className="text-white font-bold text-lg">{form.store_name || 'Your Store'}</h4>
                                {form.hero_headline && (
                                    <p className="text-white/80 text-sm mt-1">{form.hero_headline}</p>
                                )}
                            </div>
                            {form.announcement_text && (
                                <div className="px-4 py-2 text-center text-sm font-medium"
                                    style={{ backgroundColor: form.accent_color || '#f59e0b', color: '#fff' }}>
                                    {form.announcement_text}
                                </div>
                            )}
                            <div className="px-4 py-6 text-center text-sm text-neutral-400 bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-500">
                                Packages will appear here in a grid layout
                            </div>
                            {form.footer_tagline && (
                                <div className="px-4 py-3 text-center text-xs text-neutral-400 border-t border-neutral-200 dark:border-neutral-700">
                                    {form.footer_tagline}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
