import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function StoreOrders({ storeOrders, SubAgents, filters }) {
    const [status, setStatus] = useState(filters?.status || '');
    const [search, setSearch] = useState(filters?.search || '');
    const [SubAgentId, setSubAgentId] = useState(filters?.sub_agent_id || '');

    const applyFilters = () => {
        router.get(route('dealer.store-orders'), { status, search, sub_agent_id: SubAgentId }, { preserveState: true, preserveScroll: true });
    };

    const statusBadge = (s) => {
        const colors = {
            completed: 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
            delivered: 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
            pending: 'bg-secondary-100 text-secondary-700 dark:bg-secondary-900/30 dark:text-secondary-300',
            processing: 'bg-tertiary-100 text-tertiary-700 dark:bg-tertiary-900/30 dark:text-tertiary-300',
            failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            cancelled: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
        };
        return colors[s] || 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-neutral-200">
                    Store Orders
                </h2>
            }
        >
            <Head title="Store Orders" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800">
                        <div className="p-5 border-b border-neutral-100 dark:border-neutral-800 flex flex-wrap gap-3 items-center">
                            <input
                                type="text"
                                placeholder="Search by phone, order ref..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                className="flex-1 min-w-[180px] rounded-lg border-neutral-300 text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                            />
                            <select
                                value={SubAgentId}
                                onChange={(e) => setSubAgentId(e.target.value)}
                                className="rounded-lg border-neutral-300 text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                            >
                                <option value="">All Sub-Agents</option>
                                {SubAgents.map((s) => (
                                    <option key={s.id} value={s.id}>{s.username}</option>
                                ))}
                            </select>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="rounded-lg border-neutral-300 text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="delivered">Delivered</option>
                                <option value="failed">Failed</option>
                            </select>
                            <button onClick={applyFilters} className="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition">
                                Filter
                            </button>
                        </div>

                        <div className="w-full">
                            {/* Mobile Card View (hidden on md+) */}
                            <div className="md:hidden flex flex-col gap-3 p-4 bg-neutral-50 dark:bg-neutral-900/50">
                                {storeOrders.data.length === 0 ? (
                                    <div className="py-12 text-center text-neutral-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 rounded-2xl shadow-sm">
                                        No store orders found
                                    </div>
                                ) : (
                                    storeOrders.data.map((order) => (
                                        <div key={order.id} className="bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                                            {/* Top Row: Ref & Date */}
                                            <div className="flex justify-between items-center mb-3">
                                                <div className="font-mono text-xs text-neutral-500 dark:text-neutral-400">
                                                    #{order.order_ref}
                                                </div>
                                                <div className="text-xs text-neutral-400 dark:text-neutral-500 font-medium">
                                                    {order.created_at}
                                                </div>
                                            </div>

                                            {/* Sub-Agent & Status */}
                                            <div className="flex justify-between items-center mb-3">
                                                <div className="flex items-center gap-1.5">
                                                    <div className="w-6 h-6 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                                        {order.sub_agent.substring(0, 2)}
                                                    </div>
                                                    <span className="font-bold text-neutral-900 dark:text-white text-sm">
                                                        {order.sub_agent}
                                                    </span>
                                                </div>
                                                <span className={`inline-block text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${statusBadge(order.status)}`}>
                                                    {order.status}
                                                </span>
                                            </div>

                                            {/* Customer & Package */}
                                            <div className="mb-4">
                                                <div className="text-xs text-neutral-500 dark:text-neutral-400 mb-0.5">Customer</div>
                                                <div className="text-sm font-medium text-neutral-900 dark:text-neutral-200">
                                                    {order.customer_name || order.customer_phone}
                                                </div>
                                                <div className="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                                    {order.network_type} • <span className="font-medium text-neutral-700 dark:text-neutral-300">{order.package_size_gb} GB</span>
                                                </div>
                                            </div>

                                            {/* Financials */}
                                            <div className="grid grid-cols-2 gap-3 pt-3 border-t border-neutral-100 dark:border-neutral-700">
                                                <div>
                                                    <div className="text-[10px] uppercase font-bold tracking-wider text-neutral-400 dark:text-neutral-500 mb-0.5">Sold Price</div>
                                                    <div className="font-mono text-sm font-medium text-neutral-900 dark:text-white">
                                                        GH₵ {order.sold_price.toFixed(2)}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-[10px] uppercase font-bold tracking-wider text-neutral-400 dark:text-neutral-500 mb-0.5">Your Profit</div>
                                                    <div className={`font-mono text-sm font-bold ${order.your_profit >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-600 dark:text-red-400'}`}>
                                                        {order.your_profit >= 0 ? '+' : ''}GH₵ {order.your_profit.toFixed(2)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            {/* Desktop Table View (hidden on mobile) */}
                            <div className="hidden md:block overflow-x-auto">
                                <table className="w-full text-sm min-w-max whitespace-nowrap">
                                    <thead>
                                        <tr className="bg-neutral-50 text-left dark:bg-neutral-800">
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Order Ref</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Sub-Agent</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Customer</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Network</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Package</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Sold Price</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Profit</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                            <th className="px-5 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                        {storeOrders.data.length === 0 ? (
                                            <tr>
                                                <td colSpan="9" className="px-5 py-12 text-center text-neutral-400 dark:text-neutral-500">No store orders found</td>
                                            </tr>
                                        ) : (
                                            storeOrders.data.map((order) => (
                                                <tr key={order.id} className="hover:bg-neutral-50 transition dark:hover:bg-neutral-800/50">
                                                    <td className="px-5 py-3 font-mono text-xs text-neutral-500 dark:text-neutral-400">{order.order_ref}</td>
                                                    <td className="px-5 py-3 font-medium text-neutral-900 dark:text-neutral-200">{order.sub_agent}</td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">
                                                        {order.customer_name || order.customer_phone}
                                                    </td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{order.network_type}</td>
                                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{order.package_size_gb} GB</td>
                                                    <td className="px-5 py-3 font-medium text-neutral-900 dark:text-neutral-200">GH₵ {order.sold_price.toFixed(2)}</td>
                                                    <td className={`px-5 py-3 font-medium ${order.your_profit >= 0 ? 'text-primary-600' : 'text-red-600'}`}>
                                                        GH₵ {order.your_profit.toFixed(2)}
                                                    </td>
                                                    <td className="px-5 py-3">
                                                        <span className={`inline-block text-xs px-2 py-0.5 rounded-full font-medium ${statusBadge(order.status)}`}>
                                                            {order.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-5 py-3 text-neutral-500 dark:text-neutral-400 text-xs whitespace-nowrap">{order.created_at}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {storeOrders.links && (
                            <div className="px-5 py-4 border-t border-neutral-100 dark:border-neutral-800 flex justify-between items-center text-sm">
                                <p className="text-neutral-500 dark:text-neutral-400">
                                    Showing {storeOrders.from}–{storeOrders.to} of {storeOrders.total}
                                </p>
                                <div className="flex gap-1">
                                    {storeOrders.links.map((link, i) => (
                                        <button
                                            key={i}
                                            onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                            disabled={!link.url}
                                            className={`px-3 py-1 rounded text-sm font-medium transition ${
                                                link.active
                                                    ? 'bg-primary-600 text-white'
                                                    : link.url
                                                        ? 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
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
