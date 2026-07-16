import { Head, Link } from '@inertiajs/react';

const networkColors = {
    MTN: 'text-yellow-600 dark:text-yellow-400',
    VODAFONE: 'text-red-600 dark:text-red-400',
    TELECEL: 'text-red-600 dark:text-red-400',
    AIRTELTIGO: 'text-blue-600 dark:text-blue-400',
};

export default function StorefrontSuccess({ store, order }) {
    return (
        <>
            <Head title="Order Successful" />
            <div className="min-h-screen bg-neutral-100 flex items-center justify-center px-4 dark:bg-neutral-950">
                <div className="max-w-md w-full text-center">
                    <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-green-900/30">
                        <svg className="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 className="text-2xl font-bold text-neutral-900 mb-2 dark:text-white">Payment Successful!</h1>
                    <p className="text-sm text-neutral-500 mb-6 dark:text-neutral-400">Your order is being processed. Your data bundle will be delivered shortly.</p>

                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200 p-5 text-left dark:bg-neutral-900 dark:border-neutral-800">
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between"><span className="text-neutral-500 dark:text-neutral-400">Order</span><span className="font-mono font-medium text-neutral-900 dark:text-neutral-200">{order.order_ref}</span></div>
                            <div className="flex justify-between"><span className="text-neutral-500 dark:text-neutral-400">Tracking ID</span><span className="font-mono font-medium text-neutral-900 dark:text-neutral-200">{order.tracking_id}</span></div>
                            <div className="flex justify-between"><span className="text-neutral-500 dark:text-neutral-400">Phone</span><span className="text-neutral-900 dark:text-neutral-200">{order.customer_phone}</span></div>
                            <div className="flex justify-between"><span className="text-neutral-500 dark:text-neutral-400">Network</span><span className={`font-semibold ${networkColors[order.network_type] || 'text-neutral-900 dark:text-neutral-200'}`}>{order.network_type}</span></div>
                            <div className="flex justify-between"><span className="text-neutral-500 dark:text-neutral-400">Package</span><span className="text-neutral-900 dark:text-neutral-200">{order.package_size_gb} GB</span></div>
                            <div className="flex justify-between"><span className="text-neutral-500 dark:text-neutral-400">Amount</span><span className="font-bold text-primary-600 dark:text-primary-400">GH₵ {order.sold_price.toFixed(2)}</span></div>
                            <div className="flex justify-between">
                                <span className="text-neutral-500 dark:text-neutral-400">Status</span>
                                <span className={`font-medium ${order.status === 'failed' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'}`}>{order.status}</span>
                            </div>
                        </div>
                    </div>

                    {order.status === 'failed' && (
                        <div className="bg-red-50 text-red-700 p-4 rounded-xl mt-6 text-left border border-red-200 dark:bg-red-900/20 dark:border-red-900/30 dark:text-red-300">
                            <p className="font-semibold mb-1">Data Delivery Failed</p>
                            <p className="text-sm mb-2 text-red-600 dark:text-red-400">
                                {order.failure_reason || "The API provider failed to process the data bundle."}
                            </p>
                            <p className="text-xs mt-2 text-red-600 dark:text-red-400">
                                Your payment was successful but the bundle could not be delivered. Please contact support with your tracking ID.
                            </p>
                        </div>
                    )}

                    <Link href={`/store/${store.slug}`}
                        className="mt-6 inline-block bg-primary-600 text-white px-6 py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 transition">
                        Continue Shopping
                    </Link>
                </div>
            </div>

        </>
    );
}
