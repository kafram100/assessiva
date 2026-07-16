import { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout';

const networkColors = {
    MTN: 'text-yellow-600 dark:text-yellow-400',
    VODAFONE: 'text-red-600 dark:text-red-400',
    TELECEL: 'text-red-600 dark:text-red-400',
    AIRTELTIGO: 'text-blue-600 dark:text-blue-400',
};

export default function OrderSuccess({ orderId }) {
    const [order, setOrder] = useState(null);

    useEffect(() => {
        if (!orderId) return;
        let attempts = 0;
        const maxAttempts = 30;
        const poll = setInterval(async () => {
            attempts++;
            if (attempts > maxAttempts) {
                clearInterval(poll);
                return;
            }
            try {
                const res = await axios.get(route('api.orders.show', orderId));
                if (res.data.success && res.data.data.order_reference) {
                    setOrder(res.data.data);
                    clearInterval(poll);
                }
            } catch {
                if (attempts > maxAttempts) clearInterval(poll);
            }
        }, 1000);
        return () => clearInterval(poll);
    }, [orderId]);

    return (
        <PublicLayout>
            <Head title="Order Successful - AirLinks" />
            <div className="max-w-lg mx-auto px-4 py-8">
                <div className="bg-white rounded-2xl shadow-lg p-6 text-center">
                    <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 className="text-2xl font-bold text-neutral-900 mb-2">Payment Successful!</h2>
                    <p className="text-neutral-600 mb-6">Your order is being processed.</p>

                    {order ? (
                        <>
                            <div className="bg-neutral-50 rounded-xl p-4 text-left mb-6 space-y-1">
                                <p className="text-sm text-neutral-500">Reference: <span className="font-semibold text-neutral-900">{order.order_reference}</span></p>
                                <p className="text-sm text-neutral-500">Tracking: <span className="font-semibold text-neutral-900">{order.tracking_key}</span></p>
                                <p className="text-sm text-neutral-500">
                                    Status: <span className={`font-semibold ${order.status === 'failed' ? 'text-red-600' : 'text-green-600'}`}>{order.status}</span>
                                </p>
                                <p className="text-sm text-neutral-500">Package: <span className="font-semibold text-neutral-900">{order.package_size}</span> <span className={`font-semibold ${networkColors[order.network_type] || 'text-neutral-900'}`}>{order.network_type}</span></p>
                                <p className="text-sm text-neutral-500">Amount: <span className="font-semibold text-neutral-900">GH₵{parseFloat(order.amount).toFixed(2)}</span></p>
                                <p className="text-sm text-neutral-500">Beneficiary: <span className="font-semibold text-neutral-900">{order.beneficiary_number}</span></p>
                            </div>
                            {order.status === 'failed' && (
                                <div className="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-left border border-red-200">
                                    <p className="font-semibold mb-1">Data Delivery Failed</p>
                                    <p className="text-sm mb-2 text-red-600">
                                        {order.api_response_data?.error || "The API provider failed to process the data bundle."}
                                    </p>
                                    <p className="text-xs mt-2 text-red-600">
                                        Your payment was successful but the bundle could not be delivered. Please contact support with your tracking ID.
                                    </p>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="bg-neutral-50 rounded-xl p-4 mb-6 text-center text-sm text-neutral-500">
                            <div className="animate-spin w-6 h-6 border-2 border-primary-600 border-t-transparent rounded-full mx-auto mb-2" />
                            Confirming payment...
                        </div>
                    )}

                    <div className="space-y-3">
                        <Link href={`/track/${order?.tracking_key || ''}`}
                            className="block w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
                            Track This Order
                        </Link>
                        <Link href="/"
                            className="block w-full text-neutral-600 py-2 text-sm hover:underline">
                            Buy More Data
                        </Link>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}