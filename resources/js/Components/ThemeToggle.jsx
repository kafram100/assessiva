import { useEffect, useState } from 'react';

const THEME_KEY = 'theme';

function getSystemPref() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function getStoredTheme() {
    return localStorage.getItem(THEME_KEY);
}

function resolveTheme(theme) {
    return theme === 'system' ? getSystemPref() : theme;
}

function applyTheme(theme) {
    const resolved = resolveTheme(theme);
    document.documentElement.classList.toggle('dark', resolved === 'dark');
}

export default function ThemeToggle() {
    const [theme, setTheme] = useState(() => getStoredTheme() || 'system');

    useEffect(() => {
        const stored = getStoredTheme();
        if (stored) {
            setTheme(stored);
            applyTheme(stored);
        } else {
            setTheme('system');
            applyTheme('system');
        }
    }, []);

    useEffect(() => {
        applyTheme(theme);
        localStorage.setItem(THEME_KEY, theme);

        if (theme === 'system') {
            const mq = window.matchMedia('(prefers-color-scheme: dark)');
            const handler = () => applyTheme('system');
            mq.addEventListener('change', handler);
            return () => mq.removeEventListener('change', handler);
        }
    }, [theme]);

    const cycle = () => {
        setTheme(prev => prev === 'light' ? 'dark' : prev === 'dark' ? 'system' : 'light');
    };

    const icon = theme === 'dark' ? (
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    ) : theme === 'light' ? (
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
    ) : (
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    );

    return (
        <button
            onClick={cycle}
            aria-label="Toggle theme"
            title={`Theme: ${theme}`}
            className="p-2 rounded-lg text-neutral-500 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition"
        >
            {icon}
        </button>
    );
}
