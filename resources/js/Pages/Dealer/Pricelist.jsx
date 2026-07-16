import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const NETWORKS = [
    { id: 'MTN', name: 'MTN', bg: 'bg-yellow-400', text: 'text-yellow-900' },
    { id: 'VODAFONE', name: 'Telecel', bg: 'bg-red-600', text: 'text-white' },
    { id: 'AIRTELTIGO', name: 'AirtelTigo', bg: 'bg-green-500', text: 'text-white' },
];

export default function Pricelist({ packagesData = {}, networks = [] }) {
    const { flash } = usePage().props;
    const [activeNet, setActiveNet] = useState(networks.length > 0 ? networks[0] : null);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ customer_price: '', sub_agent_price: '' });
    const [loading, setLoading] = useState(false);
    const [flashMsg, setFlashMsg] = useState(flash?.success || null);

    const currentPkgs = packagesData[activeNet] || [];

    const netInfo = (id) => NETWORKS.find(n => n.id === id);

    const startEdit = (pkg) => {
        setEditing(pkg.id);
        setForm({
            customer_price: pkg.customer_price != null ? String(pkg.customer_price) : String(pkg.base_price),
            sub_agent_price: pkg.sub_agent_price != null ? String(pkg.sub_agent_price) : String(pkg.base_price),
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        setForm({ customer_price: '', sub_agent_price: '' });
    };

    const saveEdit = async (pkgId) => {
        setLoading(true);
        try {
            await router.post(route('dealer.pricelist.update'), {
                prices: [{
                    package_id: pkgId,
                    customer_price: parseFloat(form.customer_price) || 0,
                    sub_agent_price: parseFloat(form.sub_agent_price) || 0,
                }],
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    setFlashMsg('Price updated.');
                    setEditing(null);
                },
                onError: (errs) => {
                    const msg = Object.values(errs).flat().join(', ');
                    setFlashMsg('Error: ' + msg);
                },
            });
        } catch {
            setFlashMsg('Something went wrong');
        } finally {
            setLoading(false);
        }
    };

    const diffPercent = (base, custom) => {
        if (!base || !custom) return null;
        return ((custom - base) / base * 100).toFixed(1);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    Pricelist
                </h2>
            }
        >
            <Head title="Pricelist" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {flashMsg && (
                        <div className="px-5 py-3 rounded-xl text-sm font-medium bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                            {flashMsg}
                        </div>
                    )}

                    <div className="bg-white rounded-[2rem] shadow-sm border border-neutral-200 p-6 sm:p-8 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 pb-6 border-b border-neutral-100 dark:border-neutral-800">
                            <div>
                                <h3 className="text-xl font-bold text-neutral-900 dark:text-neutral-200">Package Pricing</h3>
                                <p className="text-sm text-neutral-500 mt-1 dark:text-neutral-400">Set prices for your store and Sub-Agents</p>
                            </div>
                        </div>

                        {networks.length > 0 && (
                            <div className="flex gap-3 mb-8 overflow-x-auto pb-2 scrollbar-hide">
                                {networks.map(nid => {
                                    const n = netInfo(nid);
                                    return (
                                        <button key={nid} onClick={() => setActiveNet(nid)}
                                            className={`shrink-0 px-6 py-2.5 rounded-xl text-sm font-bold transition-all border-2 ${
                                                activeNet === nid
                                                    ? `${n?.bg} ${n?.text} border-transparent shadow-md`
                                                    : 'bg-white text-neutral-600 border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            {n?.name || nid}
                                        </button>
                                    );
                                })}
                            </div>
                        )}

                        {activeNet && currentPkgs.length > 0 && (
                            <div className="w-full">
                                {/* Mobile Card View (hidden on md+) */}
                                <div className="md:hidden flex flex-col gap-3 p-4 -mx-6 sm:mx-0">
                                    {currentPkgs.map(pkg => {
                                        const isEditing = editing === pkg.id;
                                        const margin = diffPercent(pkg.base_price, pkg.customer_price);
                                        return (
                                            <div key={pkg.id} className="bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-2xl p-4 shadow-sm">
                                                <div className="flex justify-between items-start mb-3">
                                                    <div>
                                                        <div className="font-bold text-neutral-900 dark:text-neutral-200">{pkg.package_name}</div>
                                                        <div className="text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">{pkg.package_size}</div>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="text-[10px] uppercase font-bold tracking-wider text-neutral-400 dark:text-neutral-500 mb-0.5">Base Price</div>
                                                        <div className="font-mono text-sm font-medium text-neutral-900 dark:text-white">GH₵ {Number(pkg.base_price).toFixed(2)}</div>
                                                    </div>
                                                </div>
                                                
                                                {isEditing ? (
                                                    <div className="space-y-4 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <label className="block text-[10px] uppercase font-bold tracking-wider text-neutral-500 mb-1">Store Price</label>
                                                                <input type="number" step="0.01" min="0"
                                                                    value={form.customer_price}
                                                                    onChange={e => setForm({ ...form, customer_price: e.target.value })}
                                                                    className="w-full text-left border-2 border-neutral-200 rounded-xl px-3 py-2 focus:border-primary-500 outline-none transition font-mono text-sm dark:bg-neutral-900 dark:border-neutral-700 dark:text-white" />
                                                            </div>
                                                            <div>
                                                                <label className="block text-[10px] uppercase font-bold tracking-wider text-neutral-500 mb-1">Sub-Agent Price</label>
                                                                <input type="number" step="0.01" min="0"
                                                                    value={form.sub_agent_price}
                                                                    onChange={e => setForm({ ...form, sub_agent_price: e.target.value })}
                                                                    className="w-full text-left border-2 border-neutral-200 rounded-xl px-3 py-2 focus:border-primary-500 outline-none transition font-mono text-sm dark:bg-neutral-900 dark:border-neutral-700 dark:text-white" />
                                                            </div>
                                                        </div>
                                                        <div className="flex gap-2 justify-end">
                                                            <button onClick={cancelEdit}
                                                                className="flex-1 px-4 py-2.5 bg-neutral-100 text-neutral-600 rounded-xl text-sm font-bold hover:bg-neutral-200 transition dark:bg-neutral-700 dark:text-neutral-300">
                                                                Cancel
                                                            </button>
                                                            <button onClick={() => saveEdit(pkg.id)} disabled={loading}
                                                                className="flex-1 px-4 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-bold hover:bg-primary-500 shadow-lg shadow-primary-600/20 disabled:opacity-50 transition active:scale-95">
                                                                Save Prices
                                                            </button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-3 pt-3 border-t border-neutral-100 dark:border-neutral-700">
                                                        <div className="grid grid-cols-2 gap-3 bg-neutral-50 dark:bg-neutral-900/50 p-3 rounded-xl">
                                                            <div>
                                                                <div className="text-[10px] uppercase font-bold tracking-wider text-neutral-500 mb-0.5">Store Price</div>
                                                                <div className="font-mono text-sm font-medium">
                                                                    {pkg.customer_price != null ? (
                                                                        <span className={Number(pkg.customer_price) !== Number(pkg.base_price) ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-900 dark:text-neutral-200'}>
                                                                            GH₵ {Number(pkg.customer_price).toFixed(2)}
                                                                        </span>
                                                                    ) : <span className="text-neutral-400">—</span>}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div className="text-[10px] uppercase font-bold tracking-wider text-neutral-500 mb-0.5">Sub-Agent Price</div>
                                                                <div className="font-mono text-sm font-medium">
                                                                    {pkg.sub_agent_price != null ? (
                                                                        <span className={Number(pkg.sub_agent_price) !== Number(pkg.base_price) ? 'text-tertiary-600 dark:text-tertiary-400' : 'text-neutral-900 dark:text-neutral-200'}>
                                                                            GH₵ {Number(pkg.sub_agent_price).toFixed(2)}
                                                                        </span>
                                                                    ) : <span className="text-neutral-400">—</span>}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="flex justify-between items-center pt-1">
                                                            <div className="flex items-center gap-1.5">
                                                                <span className="text-xs font-medium text-neutral-500">Margin:</span>
                                                                {margin ? (
                                                                    <span className={`text-xs font-bold px-2 py-0.5 rounded-md ${parseFloat(margin) > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}>
                                                                        {margin}%
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-xs text-neutral-400">—</span>
                                                                )}
                                                            </div>
                                                            <button onClick={() => startEdit(pkg)}
                                                                className="px-4 py-2 bg-neutral-100 text-neutral-700 rounded-xl text-sm font-bold hover:bg-neutral-200 transition dark:bg-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-600 active:scale-95">
                                                                Edit Prices
                                                            </button>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>

                                {/* Desktop Table View (hidden on mobile) */}
                                <div className="hidden md:block overflow-x-auto -mx-6 sm:mx-0">
                                    <table className="w-full text-sm min-w-max whitespace-nowrap">
                                        <thead>
                                            <tr className="bg-neutral-50/50 dark:bg-neutral-900/50 border-y border-neutral-100 dark:border-neutral-800">
                                                <th className="text-left py-3 px-2 font-semibold text-neutral-600 dark:text-neutral-400">Package</th>
                                                <th className="text-right py-3 px-2 font-semibold text-neutral-600 dark:text-neutral-400">Base Price</th>
                                                <th className="text-right py-3 px-2 font-semibold text-neutral-600 dark:text-neutral-400">Store Price</th>
                                                <th className="text-right py-3 px-2 font-semibold text-neutral-600 dark:text-neutral-400">Sub-Agent Price</th>
                                                <th className="text-center py-3 px-2 font-semibold text-neutral-600 dark:text-neutral-400">Margin</th>
                                                <th className="py-3 px-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {currentPkgs.map(pkg => {
                                                const isEditing = editing === pkg.id;
                                                const margin = diffPercent(pkg.base_price, pkg.customer_price);
                                                return (
                                                    <tr key={pkg.id} className="border-b border-neutral-100 dark:border-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                                        <td className="py-3 px-2">
                                                            <div className="font-medium text-neutral-900 dark:text-neutral-200">{pkg.package_name}</div>
                                                            <div className="text-xs text-neutral-400 dark:text-neutral-500">{pkg.package_size}</div>
                                                        </td>
                                                        <td className="text-right py-3 px-2">
                                                            <span className="font-mono text-neutral-500 dark:text-neutral-400">GH₵ {Number(pkg.base_price).toFixed(2)}</span>
                                                        </td>
                                                        {isEditing ? (
                                                            <>
                                                                <td className="py-3 px-2">
                                                                    <input type="number" step="0.01" min="0"
                                                                        value={form.customer_price}
                                                                        onChange={e => setForm({ ...form, customer_price: e.target.value })}
                                                                        className="w-24 text-right border-2 border-neutral-200 rounded-lg px-2 py-1.5 focus:border-primary-500 outline-none transition font-mono text-sm dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                                                                </td>
                                                                <td className="py-3 px-2">
                                                                    <input type="number" step="0.01" min="0"
                                                                        value={form.sub_agent_price}
                                                                        onChange={e => setForm({ ...form, sub_agent_price: e.target.value })}
                                                                        className="w-24 text-right border-2 border-neutral-200 rounded-lg px-2 py-1.5 focus:border-primary-500 outline-none transition font-mono text-sm dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                                                                </td>
                                                                <td className="text-center py-3 px-2 text-xs text-neutral-400 dark:text-neutral-500">—</td>
                                                                <td className="py-3 px-2">
                                                                    <div className="flex gap-1 justify-end">
                                                                        <button onClick={() => saveEdit(pkg.id)} disabled={loading}
                                                                            className="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-xs font-semibold hover:bg-primary-700 disabled:opacity-50 transition">
                                                                            Save
                                                                        </button>
                                                                        <button onClick={cancelEdit}
                                                                            className="px-3 py-1.5 bg-neutral-100 text-neutral-600 rounded-lg text-xs font-semibold hover:bg-neutral-200 transition dark:bg-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-600">
                                                                            Cancel
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <td className="text-right py-3 px-2">
                                                                    {pkg.customer_price != null ? (
                                                                        <span className={`font-mono font-medium ${Number(pkg.customer_price) !== Number(pkg.base_price) ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-900 dark:text-neutral-200'}`}>
                                                                            GH₵ {Number(pkg.customer_price).toFixed(2)}
                                                                        </span>
                                                                    ) : (
                                                                        <span className="font-mono text-neutral-400 dark:text-neutral-500">—</span>
                                                                    )}
                                                                </td>
                                                                <td className="text-right py-3 px-2">
                                                                    {pkg.sub_agent_price != null ? (
                                                                        <span className={`font-mono font-medium ${Number(pkg.sub_agent_price) !== Number(pkg.base_price) ? 'text-tertiary-600 dark:text-tertiary-400' : 'text-neutral-900 dark:text-neutral-200'}`}>
                                                                            GH₵ {Number(pkg.sub_agent_price).toFixed(2)}
                                                                        </span>
                                                                    ) : (
                                                                        <span className="font-mono text-neutral-400 dark:text-neutral-500">—</span>
                                                                    )}
                                                                </td>
                                                                <td className="text-center py-3 px-2">
                                                                    {margin && (
                                                                        <span className={`text-xs font-semibold ${parseFloat(margin) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
                                                                            {margin}%
                                                                        </span>
                                                                    )}
                                                                </td>
                                                                <td className="py-3 px-2 text-right">
                                                                    <button onClick={() => startEdit(pkg)}
                                                                        className="px-3 py-1.5 bg-neutral-100 text-neutral-600 rounded-lg text-xs font-semibold hover:bg-neutral-200 transition dark:bg-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-600">
                                                                        Edit
                                                                    </button>
                                                                </td>
                                                            </>
                                                        )}
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {activeNet && currentPkgs.length === 0 && (
                            <p className="text-neutral-400 text-center py-8 dark:text-neutral-500">No packages available for this network.</p>
                        )}

                        {!activeNet && networks.length === 0 && (
                            <p className="text-neutral-400 text-center py-8 dark:text-neutral-500">No packages found.</p>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
