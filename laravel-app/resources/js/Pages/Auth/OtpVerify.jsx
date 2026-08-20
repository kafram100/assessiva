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

            {/* Inject keyframe animations */}
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
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
                .otp-digit-input:focus {
                    border-color: #4f46e5 !important;
                    box-shadow: 0 0 0 3px rgba(79,70,229,0.18) !important;
                    background: #f0eeff !important;
                }
            `}</style>

            <div style={styles.container}>
                {/* Icon */}
                <div style={styles.iconWrap}>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>

                <h1 style={styles.title}>Check your email</h1>
                <p style={styles.subtitle}>
                    We sent a 6-digit verification code to<br />
                    <strong style={styles.email}>{maskedEmail}</strong>
                </p>

                {/* Status / flash */}
                {status && (
                    <div style={styles.successBanner}>
                        <span>✅ {status}</span>
                    </div>
                )}

                {/* Error — with fade-in animation */}
                {errors?.otp && (
                    <div style={{
                        ...styles.errorBanner,
                        animation: 'fadeInScale 0.3s ease-out',
                    }}>
                        <span>⚠️ {errors.otp}</span>
                    </div>
                )}

                {/* Verifying indicator */}
                {verifying && (
                    <div style={{
                        ...styles.verifyingBanner,
                        animation: 'fadeInScale 0.2s ease-out',
                    }}>
                        <span style={styles.spinnerSmall} />
                        <span>Verifying your code…</span>
                    </div>
                )}

                {/* Expiry */}
                <div style={{ ...styles.expiryBadge, ...(isExpired ? styles.expiryExpired : {}) }}>
                    {isExpired ? '⏰ Code expired — please request a new one' : `⏱ Expires in ${fmt(expiryLeft)}`}
                </div>

                {/* OTP Inputs */}
                <form onSubmit={handleSubmit}>
                    <div
                        style={{
                            ...styles.digitRow,
                            ...(shaking ? { animation: 'shake 0.4s ease-in-out' } : {}),
                        }}
                        onPaste={handlePaste}
                    >
                        {digits.map((d, i) => (
                            <input
                                key={i}
                                ref={el => (inputRefs.current[i] = el)}
                                id={`otp-digit-${i}`}
                                className="otp-digit-input"
                                type="text"
                                inputMode="numeric"
                                maxLength={1}
                                value={d}
                                onChange={e => handleChange(i, e.target.value)}
                                onKeyDown={e => handleKeyDown(i, e)}
                                style={{
                                    ...styles.digitInput,
                                    ...(d ? styles.digitFilled : {}),
                                    ...(errors?.otp ? styles.digitError : {}),
                                    ...(verifying ? styles.digitVerifying : {}),
                                }}
                                autoComplete="one-time-code"
                                disabled={isExpired || verifying}
                                autoFocus={i === 0}
                            />
                        ))}
                    </div>

                    {/* Hidden submit for Enter key */}
                    <button type="submit" style={{ display: 'none' }} />
                </form>

                {/* Resend */}
                <div style={styles.resendRow}>
                    <span style={styles.resendLabel}>Didn't receive it?</span>
                    {resendCooldown > 0 ? (
                        <span style={styles.resendCooldown}>
                            Resend in {fmt(resendCooldown)}
                        </span>
                    ) : (
                        <button
                            onClick={handleResend}
                            disabled={resending}
                            style={styles.resendBtn}
                        >
                            {resending ? 'Sending…' : 'Resend code'}
                        </button>
                    )}
                </div>

                <p style={styles.hint}>
                    Check your spam folder if you don't see it within a minute.
                </p>
            </div>
        </GuestLayout>
    );
}

/* ── Styles ─────────────────────────────────────────────────────────────── */
const styles = {
    container: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        padding: '8px 0 16px',
        width: '100%',
    },
    iconWrap: {
        width: 64,
        height: 64,
        borderRadius: '50%',
        background: 'linear-gradient(135deg, #ede9fe, #ddd6fe)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: 20,
        boxShadow: '0 4px 16px rgba(79,70,229,0.15)',
    },
    title: {
        fontSize: 24,
        fontWeight: 700,
        color: '#1a1a2e',
        marginBottom: 8,
        textAlign: 'center',
    },
    subtitle: {
        fontSize: 14,
        color: '#6b7280',
        textAlign: 'center',
        lineHeight: 1.6,
        marginBottom: 20,
    },
    email: {
        color: '#4f46e5',
        fontWeight: 600,
    },
    successBanner: {
        width: '100%',
        background: '#dcfce7',
        border: '1px solid #bbf7d0',
        borderRadius: 8,
        padding: '10px 14px',
        fontSize: 13,
        color: '#15803d',
        marginBottom: 14,
        textAlign: 'center',
    },
    errorBanner: {
        width: '100%',
        background: '#fef2f2',
        border: '1px solid #fecaca',
        borderRadius: 8,
        padding: '10px 14px',
        fontSize: 13,
        color: '#dc2626',
        marginBottom: 14,
        textAlign: 'center',
        fontWeight: 500,
    },
    verifyingBanner: {
        width: '100%',
        background: '#f0eeff',
        border: '1px solid #ddd6fe',
        borderRadius: 8,
        padding: '10px 14px',
        fontSize: 13,
        color: '#4f46e5',
        marginBottom: 14,
        textAlign: 'center',
        fontWeight: 500,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 8,
    },
    expiryBadge: {
        fontSize: 13,
        fontWeight: 600,
        color: '#4f46e5',
        background: '#f0eeff',
        borderRadius: 20,
        padding: '5px 14px',
        marginBottom: 24,
    },
    expiryExpired: {
        color: '#dc2626',
        background: '#fef2f2',
    },
    digitRow: {
        display: 'flex',
        gap: 10,
        justifyContent: 'center',
        marginBottom: 24,
    },
    digitInput: {
        width: 48,
        height: 58,
        textAlign: 'center',
        fontSize: 22,
        fontWeight: 700,
        fontFamily: "'Courier New', monospace",
        border: '2px solid #e5e7eb',
        borderRadius: 10,
        outline: 'none',
        background: '#f9fafb',
        color: '#1a1a2e',
        caretColor: '#4f46e5',
        transition: 'border-color 0.2s, box-shadow 0.2s, background 0.2s',
        cursor: 'text',
    },
    digitFilled: {
        borderColor: '#4f46e5',
        background: '#f0eeff',
        boxShadow: '0 0 0 3px rgba(79,70,229,0.12)',
    },
    digitError: {
        borderColor: '#dc2626',
        background: '#fef2f2',
        boxShadow: '0 0 0 3px rgba(220,38,38,0.12)',
    },
    digitVerifying: {
        opacity: 0.6,
        animation: 'pulse 1s ease-in-out infinite',
    },
    spinnerSmall: {
        width: 14,
        height: 14,
        border: '2px solid rgba(79,70,229,0.3)',
        borderTopColor: '#4f46e5',
        borderRadius: '50%',
        animation: 'spin 0.7s linear infinite',
        display: 'inline-block',
    },
    resendRow: {
        display: 'flex',
        alignItems: 'center',
        gap: 8,
        marginBottom: 12,
    },
    resendLabel: {
        fontSize: 13,
        color: '#9ca3af',
    },
    resendBtn: {
        fontSize: 13,
        fontWeight: 600,
        color: '#4f46e5',
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        padding: 0,
        textDecoration: 'underline',
        textDecorationStyle: 'dotted',
    },
    resendCooldown: {
        fontSize: 13,
        color: '#6b7280',
        fontWeight: 500,
    },
    hint: {
        fontSize: 12,
        color: '#9ca3af',
        textAlign: 'center',
    },
};
