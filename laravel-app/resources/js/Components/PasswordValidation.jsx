const rules = [
    { key: 'length', label: 'At least 8 characters', test: (value) => value.length >= 8 },
    { key: 'upper', label: 'One uppercase letter', test: (value) => /[A-Z]/.test(value) },
    { key: 'lower', label: 'One lowercase letter', test: (value) => /[a-z]/.test(value) },
    { key: 'number', label: 'One number', test: (value) => /[0-9]/.test(value) },
    { key: 'symbol', label: 'One symbol', test: (value) => /[^A-Za-z0-9]/.test(value) },
];

const strengthLevels = [
    { label: 'Weak', color: 'bg-red-500' },
    { label: 'Fair', color: 'bg-orange-400' },
    { label: 'Good', color: 'bg-yellow-400' },
    { label: 'Strong', color: 'bg-emerald-500' },
];

export function isPasswordValid(password) {
    return rules.every((rule) => rule.test(password));
}

export function passwordsMatch(password, confirmation) {
    return password.length > 0 && password === confirmation;
}

export default function PasswordValidation({
    password,
    confirmation,
    showRules = true,
}) {
    const results = rules.map((rule) => ({
        ...rule,
        passed: rule.test(password),
    }));
    const passedCount = results.filter((rule) => rule.passed).length;
    const strengthIndex =
        passedCount <= 1 ? 0 : passedCount <= 2 ? 1 : passedCount <= 4 ? 2 : 3;
    const strength = strengthLevels[strengthIndex];

    return (
        <div className="mt-2 space-y-2" aria-live="polite">
            {showRules && (
                password.length === 0 ? (
                    <p className="text-xs text-theme-dim">
                        Use at least 8 characters with uppercase, lowercase, a number, and a symbol.
                    </p>
                ) : (
                    <>
                        <div className="flex gap-1" aria-hidden="true">
                            {strengthLevels.map((level, index) => (
                                <div
                                    key={level.label}
                                    className={`h-1.5 flex-1 rounded-full ${
                                        index <= strengthIndex
                                            ? strength.color
                                            : 'bg-slate-200 dark:bg-slate-700'
                                    }`}
                                />
                            ))}
                        </div>
                        <p className="text-xs text-theme-dim">
                            Password strength: {strength.label}
                        </p>
                        <ul className="space-y-1">
                            {results.map((rule) => (
                                <li
                                    key={rule.key}
                                    className={`flex items-center gap-1.5 text-xs ${
                                        rule.passed
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-theme-dim'
                                    }`}
                                >
                                    <span aria-hidden="true">
                                        {rule.passed ? '✓' : '○'}
                                    </span>
                                    {rule.label}
                                </li>
                            ))}
                        </ul>
                    </>
                )
            )}

            {confirmation !== undefined &&
                (confirmation.length > 0 || password.length > 0) && (
                <p
                    className={`text-xs font-medium ${
                        passwordsMatch(password, confirmation)
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-red-500'
                    }`}
                >
                    {passwordsMatch(password, confirmation)
                        ? 'Passwords match'
                        : 'Passwords do not match'}
                </p>
            )}
        </div>
    );
}
