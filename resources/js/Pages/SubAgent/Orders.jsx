import { Head, router } from '@inertiajs/react';
import SubAgentLayout from './SubAgentLayout';

export default function Orders({ orders }) {
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

    return (
        <SubAgentLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-neutral-800 dark:text-white">
                    Order History
                </h2>
            }
        >
            <Head title="Sub-Agent - Orders" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-[2rem] shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm min-w-max whitespace-nowrap">
                                <thead>
                                    <tr className="bg-white text-left dark:bg-neutral-900 border-b border-neutral-100 dark:border-neutral-800">
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">#</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Customer</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Network</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Package</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Amount</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Status</th>
                                        <th className="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wider dark:text-neutral-400">Date</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                    {orders.data.length === 0 ? (
                                        <tr>
                                            <td colSpan="7" className="px-5 py-12 text-center text-neutral-400 dark:text-neutral-500">No orders found</td>
                                        </tr>
                                    ) : (
                                        orders.data.map((order, i) => (
                                            <tr key={order.id} className="hover:bg-neutral-50 transition dark:hover:bg-neutral-800/50">
                                                <td className="px-5 py-3 text-neutral-500 dark:text-neutral-400">{orders.from + i}</td>
                                                <td className="px-5 py-3">
                                                    <div className="font-medium text-neutral-900 dark:text-white">{order.customer_name || '—'}</div>
                                                    {order.customer_phone && (
                                                        <div className="text-xs text-neutral-500 dark:text-neutral-400">{order.customer_phone}</div>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 font-medium text-neutral-900 dark:text-white">{order.network_type}</td>
                                                <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">{order.package_size}</td>
                                                <td className="px-5 py-3 font-medium text-neutral-900 dark:text-white">GH₵ {Number(order.amount || order.sold_price || 0).toFixed(2)}</td>
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
        </SubAgentLayout>
    );
}
