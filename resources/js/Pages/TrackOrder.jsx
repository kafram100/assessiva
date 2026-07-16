import { useState } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout';

const statusColors = {
    pending: 'bg-secondary-100 text-secondary-800',
    processing: 'bg-tertiary-100 text-tertiary-800',
    completed: 'bg-primary-100 text-primary-800',
    delivered: 'bg-primary-100 text-primary-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-neutral-100 text-neutral-800',
};

const networkColors = {
    MTN: 'text-yellow-600 dark:text-yellow-400',
    VODAFONE: 'text-red-600 dark:text-red-400',
    TELECEL: 'text-red-600 dark:text-red-400',
    AIRTELTIGO: 'text-blue-600 dark:text-blue-400',
};

export default function TrackOrder({ reference }) {
    const [searchRef, setSearchRef] = useState(reference || '');
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSearch = async (e) => {
        e.preventDefault();
        if (!searchRef.trim()) return;
        setLoading(true);
        setError(null);
        try {
            const res = await axios.get(route('api.orders.track', searchRef));
            if (res.data.success) {
                setOrder(res.data.data);
            }
        } catch (err) {
            setError('Order not found. Please check your reference number.');
            setOrder(null);
        } finally {
            setLoading(false);
        }
    };

    return (
        <PublicLayout>
            <Head title="Track Order - AirLinks" />
            <div className="max-w-lg mx-auto px-4">
                <div className="bg-white rounded-2xl shadow-lg p-6">
                    <h2 className="text-xl font-bold text-neutral-900 mb-4">Track Your Order</h2>
                    <form onSubmit={handleSearch} className="flex space-x-2 mb-6">
                        <input type="text" value={searchRef} onChange={e => setSearchRef(e.target.value)}
                            className="flex-1 border-neutral-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Order reference or tracking key" />
                        <button type="submit" disabled={loading}
                            className="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                            {loading ? '...' : 'Track'}
                        </button>
                    </form>

                    {error && <p className="text-red-600 text-sm mb-4">{error}</p>}

                    {order && (
                        <div className="space-y-4">
                            <div className="bg-neutral-50 rounded-xl p-4">
                                <div className="grid grid-cols-2 gap-3 text-sm">
                                    <div><span className="text-neutral-500">Reference</span><p className="font-semibold">{order.order_reference}</p></div>
                                    <div><span className="text-neutral-500">Status</span>
                                        <p className={`inline-block px-2 py-1 rounded text-xs font-semibold ${statusColors[order.status] || 'bg-neutral-100'}`}>
                                            {order.status.toUpperCase()}
                                        </p>
                                    </div>
                                    <div><span className="text-neutral-500">Network</span><p className={`font-semibold ${networkColors[order.network_type] || 'text-neutral-900'}`}>{order.network_type}</p></div>
                                    <div><span className="text-neutral-500">Amount</span><p className="font-semibold">GH₵{parseFloat(order.amount).toFixed(2)}</p></div>
                                    <div><span className="text-neutral-500">Beneficiary</span><p className="font-semibold">{order.beneficiary_number}</p></div>
                                    <div><span className="text-neutral-500">Package</span><p className="font-semibold">{order.package_size}</p></div>
                                </div>
                            </div>

                            {order.status_history?.length > 0 && (
                                <div>
                                    <h3 className="font-semibold text-neutral-900 mb-2">Status History</h3>
                                    <div className="space-y-2">
                                        {order.status_history.map(h => (
                                            <div key={h.id} className="flex items-start text-sm border-l-2 border-primary-500 pl-3 py-1">
                                                <div>
                                                    <span className="font-medium">{h.new_status}</span>
                                                    <p className="text-neutral-500 text-xs">{new Date(h.created_at).toLocaleString()}</p>
                                                    {h.change_reason && <p className="text-neutral-400 text-xs">{h.change_reason}</p>}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
