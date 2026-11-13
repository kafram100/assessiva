import { useEffect, useRef, useState, useCallback } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, router, usePage } from '@inertiajs/react';

const OTP_LENGTH = 6;
const OTP_EXPIRY_SECONDS = 120;      // 2 minutes
const RESEND_COOLDOWN = 60;          // 1 minute

export default function OtpVerify({ maskedEmail, status }) {
    const [digits, setDigits] = useState(Array(OTP_LENGTH).fill(''));
    const [expiryLeft, setExpiryLeft] = useState(OTP_EXPIRY_SECONDS);
    const [resendCooldown, setResendCooldown] = useState(0);
    const [resending, setResending] = useState(false);
    const [verifying, setVerifying] = useState(false);
    const [shaking, setShaking] = useState(false);
    const inputRefs = useRef([]);
    const hasSubmittedRef = useRef(false);

    const { errors } = usePage().props;

    /* ── Auto-focus the first input on mount ──────────────────────────── */
    useEffect(() => {
        const timer = setTimeout(() => {
            inputRefs.current[0]?.focus();
        }, 100);
        return () => clearTimeout(timer);
    }, []);

    /* ── Expiry countdown ─────────────────────────────────────────────── */
    useEffect(() => {
        const timer = setInterval(() => {
            setExpiryLeft(prev => (prev > 0 ? prev - 1 : 0));
        }, 1000);
        return () => clearInterval(timer);
    }, []);

    /* ── Resend cooldown countdown ────────────────────────────────────── */
    useEffect(() => {
        if (resendCooldown <= 0) return;
        const timer = setInterval(() => {
            setResendCooldown(prev => (prev > 0 ? prev - 1 : 0));
        }, 1000);
        return () => clearInterval(timer);
    }, [resendCooldown]);

    /* ── Format seconds as mm:ss ──────────────────────────────────────── */
    const fmt = (s) => `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;

    /* ── Trigger shake animation on error ─────────────────────────────── */
    const triggerShake = useCallback(() => {
        setShaking(true);
        setTimeout(() => setShaking(false), 500);
    }, []);

    /* ── When server returns errors, shake and re-focus ────────────────── */
    useEffect(() => {
        if (errors?.otp) {
            setVerifying(false);
            hasSubmittedRef.current = false;
            triggerShake();
            // Clear digits and re-focus the first input after a short delay
            setTimeout(() => {
                setDigits(Array(OTP_LENGTH).fill(''));
                inputRefs.current[0]?.focus();
            }, 600);
        }
    }, [errors?.otp, triggerShake]);

    /* ── Auto-submit when all 6 digits are filled ─────────────────────── */
    const autoSubmit = useCallback((allDigits) => {
        const otp = allDigits.join('');
        if (otp.length === OTP_LENGTH && !hasSubmittedRef.current && !verifying) {
            hasSubmittedRef.current = true;
            setVerifying(true);
            router.post(route('otp.verify.store'), { otp }, {
                preserveScroll: true,
                onError: () => {
                    setVerifying(false);
                    hasSubmittedRef.current = false;
                },
                onFinish: () => {
                    // Only reset if not redirected (i.e., stayed on page = error)
                    setTimeout(() => {
                        setVerifying(false);
                    }, 300);
                },
            });
        }
    }, [verifying]);

    /* ── Digit input handlers ─────────────────────────────────────────── */
    const handleChange = (index, value) => {
        const char = value.replace(/\D/g, '').slice(-1);
        const next = [...digits];
        next[index] = char;
        setDigits(next);

        if (char && index < OTP_LENGTH - 1) {
            inputRefs.current[index + 1]?.focus();
        }

        // If this was the last digit, auto-submit
        if (char && next.every(d => d !== '')) {
            autoSubmit(next);
        }
    };

    const handleKeyDown = (index, e) => {
        if (e.key === 'Backspace') {
            if (digits[index]) {
                const next = [...digits];
                next[index] = '';
                setDigits(next);
                hasSubmittedRef.current = false;
            } else if (index > 0) {
                inputRefs.current[index - 1]?.focus();
            }
        }
        if (e.key === 'ArrowLeft' && index > 0) inputRefs.current[index - 1]?.focus();
        if (e.key === 'ArrowRight' && index < OTP_LENGTH - 1) inputRefs.current[index + 1]?.focus();
    };

    const handlePaste = (e) => {
        e.preventDefault();
        const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, OTP_LENGTH);
        const next = [...digits];
        pasted.split('').forEach((char, i) => { next[i] = char; });
        setDigits(next);
        const focusIndex = Math.min(pasted.length, OTP_LENGTH - 1);
        inputRefs.current[focusIndex]?.focus();

        // If pasted a complete OTP, auto-submit
        if (next.every(d => d !== '')) {
            autoSubmit(next);
        }
    };

    /* ── Manual submit fallback (pressing Enter) ──────────────────────── */
    const handleSubmit = (e) => {
        e.preventDefault();
        const otp = digits.join('');
        if (otp.length < OTP_LENGTH || verifying) return;
        hasSubmittedRef.current = false; // Reset to allow re-submit
        autoSubmit(digits);
    };

    /* ── Resend ───────────────────────────────────────────────────────── */
    const handleResend = () => {
        if (resendCooldown > 0 || resending) return;
        setResending(true);
        hasSubmittedRef.current = false;
        router.post(route('otp.resend'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                setDigits(Array(OTP_LENGTH).fill(''));
                setExpiryLeft(OTP_EXPIRY_SECONDS);
                setResendCooldown(RESEND_COOLDOWN);
                inputRefs.current[0]?.focus();
            },
            onFinish: () => setResending(false),
        });
    };

    const isExpired = expiryLeft === 0;

    return (
        <GuestLayout>
            <Head title="Verify Your Email" />

            <style>{`
                @keyframes spin { to { transform: rotate(360deg); } }
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
                    20%, 40%, 60%, 80% { transform: translateX(4px); }
                }
                @keyframes fadeInScale {
                    from { opacity: 0; transform: scale(0.95) translateY(4px); }
                    to { opacity: 1; transform: scale(1) translateY(0); }
                }
            `}</style>

            <div className="flex flex-col items-center py-2 sm:py-3 w-full">
                {/* Icon */}
                <div className="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-indigo-50 border border-indigo-200/80 dark:bg-indigo-950/40 dark:border-indigo-500/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4 shadow-sm">
                    <svg className="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>

                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white text-center mb-1">
                    Check your email
                </h1>
                <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 text-center leading-relaxed mb-4">
                    We sent a 6-digit verification code to<br />
                    <strong className="font-semibold text-indigo-600 dark:text-indigo-400">{maskedEmail}</strong>
                </p>

                {/* Status / flash */}
                {status && (
                    <div className="w-full rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs font-medium text-emerald-600 dark:text-emerald-400 text-center mb-4">
                        <span>{status}</span>
                    </div>
                )}

                {/* Error — with fade-in animation */}
                {errors?.otp && (
                    <div
                        className="w-full rounded-lg bg-rose-500/10 border border-rose-500/20 p-3 text-xs font-medium text-rose-600 dark:text-rose-400 text-center mb-4"
                        style={{ animation: 'fadeInScale 0.3s ease-out' }}
                    >
                        <span>{errors.otp}</span>
                    </div>
                )}

                {/* Verifying indicator */}
                {verifying && (
                    <div
                        className="w-full rounded-lg bg-indigo-500/10 border border-indigo-500/20 p-3 text-xs font-medium text-indigo-600 dark:text-indigo-400 text-center mb-4 flex items-center justify-center gap-2"
                        style={{ animation: 'fadeInScale 0.2s ease-out' }}
                    >
                        <span className="inline-block h-3.5 w-3.5 rounded-full border-2 border-indigo-600/30 border-t-indigo-600 dark:border-indigo-400/30 dark:border-t-indigo-400 animate-spin" />
                        <span>Verifying your code…</span>
                    </div>
                )}

                {/* Expiry */}
                <div
                    className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold mb-6 border transition-colors ${
                        isExpired
                            ? 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400'
                            : 'bg-indigo-500/10 border-indigo-500/20 text-indigo-700 dark:text-indigo-300'
                    }`}
                >
                    {isExpired ? 'Code expired — please request a new one' : `Expires in ${fmt(expiryLeft)}`}
                </div>

                {/* OTP Inputs */}
                <form onSubmit={handleSubmit} className="w-full">
                    <div
                        className="flex justify-center gap-2 sm:gap-2.5 mb-6"
                        style={shaking ? { animation: 'shake 0.4s ease-in-out' } : undefined}
                        onPaste={handlePaste}
                    >
                        {digits.map((d, i) => {
                            let inputStyleClass = 'border-slate-200 bg-white text-slate-900 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/90 dark:text-white dark:hover:border-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20';

                            if (errors?.otp) {
                                inputStyleClass = 'border-rose-500 bg-rose-50/50 text-rose-900 dark:border-rose-500 dark:bg-rose-950/30 dark:text-white ring-2 ring-rose-500/20';
                            } else if (d) {
                                inputStyleClass = 'border-indigo-500 bg-indigo-50/50 text-indigo-900 dark:border-indigo-400 dark:bg-indigo-950/30 dark:text-white ring-2 ring-indigo-500/20';
                            }

                            return (
                                <input
                                    key={i}
                                    ref={el => (inputRefs.current[i] = el)}
                                    id={`otp-digit-${i}`}
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={1}
                                    value={d}
                                    onChange={e => handleChange(i, e.target.value)}
                                    onKeyDown={e => handleKeyDown(i, e)}
                                    className={`h-12 w-10 sm:h-14 sm:w-12 text-center text-xl font-bold font-mono rounded-xl border transition-all outline-none shadow-sm ${inputStyleClass} ${
                                        verifying ? 'opacity-60 animate-pulse' : ''
                                    }`}
                                    autoComplete="one-time-code"
                                    disabled={isExpired || verifying}
                                    autoFocus={i === 0}
                                />
                            );
                        })}
                    </div>

                    {/* Hidden submit for Enter key */}
                    <button type="submit" className="hidden" />
                </form>

                {/* Resend */}
                <div className="flex items-center gap-2 mb-3 text-xs">
                    <span className="text-slate-500 dark:text-slate-400">Didn't receive it?</span>
                    {resendCooldown > 0 ? (
                        <span className="font-medium text-slate-500 dark:text-slate-400">
                            Resend in {fmt(resendCooldown)}
                        </span>
                    ) : (
                        <button
                            type="button"
                            onClick={handleResend}
                            disabled={resending}
                            className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 underline underline-offset-2 transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {resending ? 'Sending…' : 'Resend code'}
                        </button>
                    )}
                </div>

                <p className="text-xs text-slate-400 dark:text-slate-500 text-center">
                    Check your spam folder if you don't see it within a minute.
                </p>
            </div>
        </GuestLayout>
    );
}
