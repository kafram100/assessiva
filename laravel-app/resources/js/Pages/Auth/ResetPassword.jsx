import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

// ── Icons ────────────────────────────────────────────────────────────────────
function EyeIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    );
}

function EyeSlashIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        </svg>
    );
}

function CheckIcon({ passed }) {
    return passed ? (
        <svg className="h-3.5 w-3.5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    ) : (
        <svg className="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <circle cx="12" cy="12" r="9" />
        </svg>
    );
}

// ── Validation helpers ────────────────────────────────────────────────────────
const RULES = [
    { key: 'length',    label: 'At least 8 characters',       test: (p) => p.length >= 8 },
    { key: 'upper',     label: 'One uppercase letter (A–Z)',   test: (p) => /[A-Z]/.test(p) },
    { key: 'lower',     label: 'One lowercase letter (a–z)',   test: (p) => /[a-z]/.test(p) },
    { key: 'number',    label: 'One number (0–9)',             test: (p) => /[0-9]/.test(p) },
    { key: 'special',   label: 'One special character',        test: (p) => /[^A-Za-z0-9]/.test(p) },
];

const STRENGTH_LEVELS = [
    { label: 'Weak',        color: 'bg-red-500' },
    { label: 'Fair',        color: 'bg-orange-400' },
    { label: 'Strong',      color: 'bg-yellow-400' },
    { label: 'Very Strong', color: 'bg-emerald-500' },
];

function getStrength(passed) {
    if (passed <= 1) return 0;
    if (passed <= 2) return 1;
    if (passed <= 3) return 2;  // changed from 4 to allow "Strong" at 3
    return 3;
}

// ── Component ────────────────────────────────────────────────────────────────
export default function ResetPassword({ token, email }) {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [touched, setTouched] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    // Compute rule results on every keystroke
    const ruleResults = useMemo(() =>
        RULES.map((r) => ({ ...r, passed: r.test(data.password) })),
        [data.password]
    );

    const passedCount  = ruleResults.filter((r) => r.passed).length;
    const strengthIdx  = getStrength(passedCount);
    const strengthInfo = STRENGTH_LEVELS[strengthIdx];
    const allPassed    = passedCount === RULES.length;
    const passwordsMatch = data.password !== '' && data.password === data.password_confirmation;

    const handlePasswordChange = (e) => {
        setTouched(true);
        setData('password', e.target.value);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Reset Password" />

            <form onSubmit={submit}>
                {/* Email */}
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                {/* Password */}
                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <div className="relative mt-1">
                        <TextInput
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className="block w-full pr-10"
                            autoComplete="new-password"
                            isFocused={true}
                            onChange={handlePasswordChange}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((v) => !v)}
                            className="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            tabIndex={-1}
                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                        >
                            {showPassword ? <EyeSlashIcon /> : <EyeIcon />}
                        </button>
                    </div>

                    {/* Strength meter — shown once the user starts typing */}
                    {touched && data.password.length > 0 && (
                        <div className="mt-3 space-y-2">
                            {/* 4-segment bar */}
                            <div className="flex gap-1">
                                {STRENGTH_LEVELS.map((level, i) => (
                                    <div
                                        key={level.label}
                                        className={`h-1.5 flex-1 rounded-full transition-all duration-300 ${
                                            i <= strengthIdx ? strengthInfo.color : 'bg-gray-200 dark:bg-gray-700'
                                        }`}
                                    />
                                ))}
                            </div>
                            <p className={`text-xs font-medium transition-colors ${
                                strengthIdx === 0 ? 'text-red-500' :
                                strengthIdx === 1 ? 'text-orange-400' :
                                strengthIdx === 2 ? 'text-yellow-500' :
                                'text-emerald-500'
                            }`}>
                                {strengthInfo.label}
                            </p>

                            {/* Per-rule checklist */}
                            <ul className="mt-1 space-y-1">
                                {ruleResults.map((r) => (
                                    <li key={r.key} className="flex items-center gap-1.5">
                                        <CheckIcon passed={r.passed} />
                                        <span className={`text-xs transition-colors ${
                                            r.passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400'
                                        }`}>
                                            {r.label}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <InputError message={errors.password} className="mt-2" />
                </div>

                {/* Confirm Password */}
                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirm Password" />

                    <div className="relative mt-1">
                        <TextInput
                            type={showConfirmPassword ? 'text' : 'password'}
                            id="password_confirmation"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="block w-full pr-10"
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                        <button
                            type="button"
                            onClick={() => setShowConfirmPassword((v) => !v)}
                            className="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            tabIndex={-1}
                            aria-label={showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'}
                        >
                            {showConfirmPassword ? <EyeSlashIcon /> : <EyeIcon />}
                        </button>
                    </div>

                    {/* Live match feedback */}
                    {data.password_confirmation.length > 0 && (
                        <p className={`mt-1.5 text-xs font-medium flex items-center gap-1 ${
                            passwordsMatch ? 'text-emerald-500' : 'text-red-500'
                        }`}>
                            {passwordsMatch ? (
                                <>
                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Passwords match
                                </>
                            ) : (
                                <>
                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Passwords do not match
                                </>
                            )}
                        </p>
                    )}

                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        Reset Password
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}

