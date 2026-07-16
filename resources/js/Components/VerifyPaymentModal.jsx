import { useState } from 'react';
import axios from 'axios';
import Modal from '@/Components/Modal';

export default function VerifyPaymentModal({ show, onClose, routeName = 'verify-payment' }) {
    const [reference, setReference] = useState('');
    const [gateway, setGateway] = useState('paystack');
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);

    const handleVerify = async (e) => {
        e.preventDefault();
        const ref = reference.trim();
        if (!ref) {
            setError('Enter your payment reference');
            return;
        }
        setLoading(true);
        setError(null);
        setResult(null);
        try {
            const res = await axios.post(route(routeName), {
                reference: ref,
                gateway: gateway,
            });
            if (res.data.success) {
                setResult({ type: 'success', message: res.data.message });
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                setResult({ type: 'error', message: res.data.message || 'Verification failed' });
            }
        } catch (err) {
            const msg = err.response?.data?.message || 'Could not verify payment. Please check the reference and try again.';
            setResult({ type: 'error', message: msg });
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        setReference('');
        setGateway('paystack');
        setError(null);
        setResult(null);
        onClose();
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="md">
            <div className="p-6">
                <div className="flex items-center justify-between mb-5">
                    <h3 className="text-lg font-bold text-neutral-900">Verify Payment</h3>
                    <button onClick={handleClose} className="text-neutral-400 hover:text-neutral-600 transition p-1">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p className="text-sm text-neutral-500 mb-5">
                    Enter the payment reference from Paystack or Moolre to verify and credit your account.
                </p>

                <form onSubmit={handleVerify} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-neutral-700 mb-1">Payment Gateway</label>
                        <select
                            value={gateway}
                            onChange={e => { setGateway(e.target.value); setResult(null); setError(null); }}
                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-3 text-sm focus:border-primary-500 outline-none transition"
                        >
                            <option value="paystack">Paystack</option>
                            <option value="moolre">Moolre</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-neutral-700 mb-1">Payment Reference</label>
                        <input
                            type="text"
                            value={reference}
                            onChange={e => { setReference(e.target.value); setResult(null); setError(null); }}
                            placeholder="Enter the reference from your payment confirmation"
                            className="w-full border-2 border-neutral-200 rounded-xl px-4 py-3 text-sm focus:border-primary-500 outline-none transition font-mono"
                            autoFocus
                        />
                    </div>

                    {error && (
                        <p className="text-red-600 text-sm bg-red-50 rounded-lg p-3">{error}</p>
                    )}

                    {result && (
                        <div className={`rounded-xl p-4 text-sm font-medium ${
                            result.type === 'success'
                                ? 'bg-green-50 text-green-700 border border-green-200'
                                : 'bg-red-50 text-red-700 border border-red-200'
                        }`}>
                            <div className="flex items-start gap-3">
                                {result.type === 'success' ? (
                                    <svg className="w-5 h-5 mt-0.5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                ) : (
                                    <svg className="w-5 h-5 mt-0.5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                )}
                                <span>{result.message}</span>
                            </div>
                        </div>
                    )}

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={handleClose}
                            className="flex-1 py-3 border-2 border-neutral-200 rounded-xl font-semibold text-sm text-neutral-600 hover:bg-neutral-50 transition">
                            Cancel
                        </button>
                        <button type="submit" disabled={loading || !reference.trim()}
                            className="flex-1 bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 disabled:opacity-50 transition flex items-center justify-center gap-2">
                            {loading ? (
                                <>
                                    <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    Verifying...
                                </>
                            ) : (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Verify Payment
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
