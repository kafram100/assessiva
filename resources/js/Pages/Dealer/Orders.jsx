import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Orders({ orders, filters }) {
    const [status, setStatus] = useState(filters?.status || '');
    const [search, setSearch] = useState(filters?.search || '');

    const applyFilters = () => {
        router.get(route('dealer.orders'), { status, search }, { preserveState: true, preserveScroll: true });
    };

    const statusBadge = (s) => {
        const colors = {
            completed: 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
            delivered: 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
            pending: 'bg-secondary-100 text-secondary-700 dark:bg-secondary-900/30 dark:text-secondary-300',
            processing: 'bg-tertiary-100 text-tertiary-700 dark:bg-tertiary-900/30 dark:text-tertiary-300',
            failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            cancelled: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400',
        };
        return colors[s] || 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400';
    };

    const typeBadge = (t) => {
        const colors = {
            'Sub-Agent': 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
            Storefront: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        };
        return colors[t] || 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300';
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-white">
                    Order History
                </h2>
            }
        >
            <Head title="Order History" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-[2rem] shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                        <div className="p-6 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50 flex flex-col sm:flex-row gap-4 items-center justify-between">
                            <div className="relative w-full sm:max-w-md">
                                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg className="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>
                                <input
                                    type="text"
                                    placeholder="Search by beneficiary, tracking key..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    className="w-full bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 rounded-xl pl-11 pr-4 py-2.5 text-sm font-medium outline-none transition-all duration-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:text-white"
                                />
                            </div>
                            <div className="flex w-full sm:w-auto items-center gap-3">
                                <select
                                    value={status}
                                    onChange={(e) => { setStatus(e.target.value); router.get(route('dealer.orders'), { status: e.target.value, search }, { preserveState: true, preserveScroll: true }); }}
                                    className="flex-1 sm:flex-none bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 rounded-xl px-4 py-2.5 text-sm font-medium outline-none transition-all duration-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:text-white"
                                >
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="failed">Failed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <button onClick={applyFilters} className="px-6 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-500 hover:shadow-lg shadow-primary-600/30 transition-all active:scale-95">
                                    Filter
                                </button>
                            </div>
                        </div>

                        {/* Data Display */}
                        <div className="w-full">
                            {/* Mobile Card View (hidden on md+) */}
                            <div className="md:hidden flex flex-col gap-3 p-4">
                                {orders.data.length === 0 ? (
                                    <div className="py-12 text-center text-neutral-400 dark:text-neutral-500 bg-neutral-50 dark:bg-neutral-800/50 rounded-2xl">
                                        No orders found
                                    </div>
                                ) : (
                                    orders.data.map((order, i) => (
                                        <div key={order.id} className="bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                                            {/* Colored left border based on status */}
                                            <div className={`absolute left-0 top-0 bottom-0 w-1 ${
                                                order.status === 'completed' || order.status === 'delivered' ? 'bg-primary-500' :
                                                order.status === 'failed' ? 'bg-red-500' :
                                                order.status === 'processing' ? 'bg-tertiary-500' : 'bg-secondary-500'
                                            }`}></div>
                                            
                                            <div className="flex justify-between items-start mb-3">
                                                <div>
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <span className={`inline-block text-[10px] px-1.5 py-0.5 rounded font-semibold ${typeBadge(order.type)}`}>
                                                            {order.type}
                                                        </span>
                                                        <span className="font-bold text-neutral-900 dark:text-white">{order.network_type}</span>
                                                    </div>
                                                    <div className="text-xs text-neutral-500 dark:text-neutral-400 font-medium bg-neutral-100 dark:bg-neutral-700 px-2 py-0.5 rounded-md inline-block mb-1">
                                                        {order.package_size}
                                                    </div>
                                                    <div className="text-sm text-neutral-600 dark:text-neutral-300 font-medium">
                                                        {order.beneficiary_number}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-bold text-neutral-900 dark:text-white mb-1">
                                                        GH₵ {order.amount}
                                                    </div>
                                                    <span className={`inline-block text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${statusBadge(order.status)}`}>
                                                        {order.status}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="flex justify-between items-center text-xs text-neutral-500 dark:text-neutral-400 pt-3 border-t border-neutral-100 dark:border-neutral-700">
                                                <span>#{orders.from + i}</span>
                                                <span>{order.created_at}</span>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            {/* Desktop Table View (hidden on mobile) */}
                            <div className="hidden md:block overflow-x-auto">
                                <table className="w-full text-sm min-w-max whitespace-nowrap">
                                    <thead>
                                        <tr className="bg-white text-left dark:bg-neutral-900 border-b border-neutral-100 dark:border-neutral-800">
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">#</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Type</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Network</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Package</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Beneficiary</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Amount</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Status</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Payment</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                        {orders.data.length === 0 ? (
                                            <tr>
                                                <td colSpan="9" className="px-5 py-12 text-center text-neutral-400 dark:text-neutral-500">No orders found</td>
                                            </tr>
                                        ) : (
                                            orders.data.map((order, i) => (
                                                <tr key={order.id} className="hover:bg-neutral-50 transition dark:hover:bg-neutral-800/50">
                                                    <td className="px-5 py-3 text-neutral-500 dark:text-neutral-400">{orders.from + i}</td>
                                                    <td className="px-5 py-3">
                                                        <span className={`inline-block text-[10px] px-1.5 py-0.5 rounded font-semibold ${typeBadge(order.type)}`}>
                                                            {order.type}
                                                        </span>
                                                    </td>
                                                    <td className="px-5 py-3 font-medium text-neutral-900 dark:text-white">{order.network_type}</td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{order.package_size}</td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{order.beneficiary_number}</td>
                                                    <td className="px-5 py-3 font-medium text-neutral-900 dark:text-white">GH₵ {order.amount}</td>
                                                    <td className="px-5 py-3">
                                                        <span className={`inline-block text-xs px-2 py-0.5 rounded-full font-medium ${statusBadge(order.status)}`}>
                                                            {order.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-5 py-3 text-neutral-500 dark:text-neutral-400 text-xs">{order.payment_method || '—'}</td>
                                                    <td className="px-5 py-3 text-neutral-500 dark:text-neutral-400 text-xs whitespace-nowrap">{order.created_at}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {orders.links && (
                            <div className="px-5 py-4 border-t border-neutral-100 dark:border-neutral-800 flex justify-between items-center text-sm">
                                <p className="text-neutral-500 dark:text-neutral-400">
                                    Showing {orders.from}–{orders.to} of {orders.total}
                                </p>
                                <div className="flex gap-1">
                                    {orders.links.map((link, i) => (
                                        <button
                                            key={i}
                                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                            disabled={!link.url}
                                            className={`px-3 py-1 rounded text-sm font-medium transition ${
                                                link.active
                                                    ? 'bg-primary-600 text-white'
                                                    : link.url
                                                        ? 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                                                        : 'text-neutral-300 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
