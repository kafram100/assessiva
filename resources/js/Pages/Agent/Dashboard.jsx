import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

function StatCard({ label, value, color, gradient }) {
    return (
        <div className={`relative overflow-hidden bg-white dark:bg-neutral-900 rounded-2xl sm:rounded-[2rem] shadow-sm border border-neutral-200 dark:border-neutral-800 p-4 sm:p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group`}>
            {gradient && (
                <div className={`absolute inset-0 bg-gradient-to-br ${gradient} opacity-10 dark:opacity-20 transition-opacity duration-300 group-hover:opacity-20 dark:group-hover:opacity-30`} />
            )}
            <div className="relative z-10">
                <p className="text-sm text-neutral-500 font-medium dark:text-neutral-400">{label}</p>
                <p className={`text-3xl font-black mt-2 tracking-tight ${color}`}>{value}</p>
            </div>
            {/* Decorative blurs */}
            <div className="absolute -bottom-6 -right-6 w-24 h-24 bg-current opacity-[0.03] dark:opacity-[0.05] rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500" style={{ color: 'inherit' }} />
        </div>
    );
}

export default function Dashboard({ stats, recentOrders, recentBalanceHistory }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-bold leading-tight text-neutral-800 dark:text-neutral-200">
                    Dashboard
                </h2>
            }
        >
            <Head title="Agent Dashboard" />

            <div className="py-4 sm:py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Stats Grid */}
                    <div className="grid grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 mb-8">
                        <StatCard label="Wallet Balance" value={`GH₵ ${stats.balance.toFixed(2)}`} color="text-primary-600 dark:text-primary-400" gradient="from-primary-500 to-tertiary-500" />
                        <StatCard label="Total Orders" value={stats.total_orders} color="text-tertiary-600 dark:text-tertiary-400" />
                        <StatCard label="Successful" value={stats.successful_orders} color="text-primary-600 dark:text-primary-400" />
                        <StatCard label="Pending" value={stats.pending_orders} color="text-secondary-600 dark:text-secondary-400" />
                        <StatCard label="Failed" value={stats.failed_orders} color="text-red-600 dark:text-red-400" gradient="from-red-500 to-orange-500" />
                    </div>

                    <div className="grid lg:grid-cols-2 gap-6 sm:gap-8">
                        {/* Recent Orders */}
                        <div className="bg-white rounded-2xl sm:rounded-[2rem] shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                            <div className="px-6 py-5 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50">
                                <h3 className="text-lg font-bold text-neutral-900 dark:text-neutral-200">Recent Orders</h3>
                            </div>
                            <div className="p-2 sm:p-4">
                                {recentOrders.length === 0 ? (
                                    <p className="text-neutral-400 text-sm text-center py-10 dark:text-neutral-500">No orders yet</p>
                                ) : (
                                    <div className="space-y-1">
                                        {recentOrders.map((order) => (
                                            <div key={order.id} className="flex items-center justify-between p-4 rounded-2xl hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors border border-transparent hover:border-neutral-100 dark:hover:border-neutral-800">
                                                <div>
                                                    <p className="text-sm font-bold text-neutral-900 dark:text-neutral-200">
                                                        {order.network_type} - {order.package_size}
                                                    </p>
                                                    <p className="text-xs text-neutral-500 dark:text-neutral-400 font-medium mt-0.5">{order.beneficiary_number}</p>
                                                    <p className="text-xs text-neutral-400 dark:text-neutral-500 mt-1">{order.created_at}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-black text-neutral-900 dark:text-neutral-200 tracking-tight">GH₵ {order.amount}</p>
                                                    <span className={`inline-flex items-center gap-1.5 mt-2 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider ${
                                                        order.status === 'completed' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' :
                                                        order.status === 'pending' ? 'bg-secondary-100 text-secondary-700 dark:bg-secondary-900/30 dark:text-secondary-400' :
                                                        order.status === 'processing' ? 'bg-tertiary-100 text-tertiary-700 dark:bg-tertiary-900/30 dark:text-tertiary-400' :
                                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                    }`}>
                                                        <span className={`w-1.5 h-1.5 rounded-full ${
                                                            order.status === 'completed' ? 'bg-primary-500' :
                                                            order.status === 'pending' ? 'bg-secondary-500' :
                                                            order.status === 'processing' ? 'bg-tertiary-500' :
                                                            'bg-red-500'
                                                        }`}></span>
                                                        {order.status}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Balance History */}
                        <div className="bg-white rounded-2xl sm:rounded-[2rem] shadow-sm border border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800 overflow-hidden">
                            <div className="px-6 py-5 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50">
                                <h3 className="text-lg font-bold text-neutral-900 dark:text-neutral-200">Balance History</h3>
                            </div>
                            <div className="p-2 sm:p-4">
                                {recentBalanceHistory.length === 0 ? (
                                    <p className="text-neutral-400 text-sm text-center py-10 dark:text-neutral-500">No activity yet</p>
                                ) : (
                                    <div className="space-y-1">
                                        {recentBalanceHistory.map((entry) => (
                                            <div key={entry.id} className="flex items-center justify-between p-4 rounded-2xl hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors border border-transparent hover:border-neutral-100 dark:hover:border-neutral-800">
                                                <div>
                                                    <p className="text-sm font-bold text-neutral-900 capitalize dark:text-neutral-200">{entry.reason.replace(/_/g, ' ')}</p>
                                                    {entry.description && (
                                                        <p className="text-xs text-neutral-500 dark:text-neutral-400 font-medium mt-0.5">{entry.description}</p>
                                                    )}
                                                    <p className="text-xs text-neutral-400 dark:text-neutral-500 mt-1">{entry.created_at}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className={`text-sm font-black tracking-tight ${entry.change_amount >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-600 dark:text-red-400'}`}>
                                                        {entry.change_amount >= 0 ? '+' : ''}GH₵ {entry.change_amount.toFixed(2)}
                                                    </p>
                                                    <p className="text-[11px] font-medium text-neutral-400 dark:text-neutral-500 mt-1">Bal: GH₵ {entry.balance_after.toFixed(2)}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
