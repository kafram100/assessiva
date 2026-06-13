import { AI_THEME_STORAGE_KEY } from '@/constants/theme';
import { useCallback, useEffect, useState } from 'react';

function IconSun(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
            />
        </svg>
    );
}

function IconMoon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"
            />
        </svg>
    );
}

function readIsDark() {
    if (typeof document === 'undefined') {
        return true;
    }
    return document.documentElement.classList.contains('dark');
}

/**
 * Toggles `dark` on `document.documentElement`, persists to localStorage,
 * and listens for cross-tab updates. Initial paint is handled in `app.blade.php`.
 */
export default function ThemeToggle({ className = '' }) {
    const [isDark, setIsDark] = useState(readIsDark);

    const applyDom = useCallback((dark) => {
        const root = document.documentElement;
        if (dark) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        try {
            localStorage.setItem(AI_THEME_STORAGE_KEY, dark ? 'dark' : 'light');
        } catch {
            /* ignore */
        }
        setIsDark(dark);
    }, []);

    useEffect(() => {
        setIsDark(readIsDark());
    }, []);

    useEffect(() => {
        const onStorage = (e) => {
            if (e.key !== AI_THEME_STORAGE_KEY || e.newValue == null) {
                return;
            }
            const dark = e.newValue === 'dark';
            const root = document.documentElement;
            if (dark) {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
            setIsDark(dark);
        };
        window.addEventListener('storage', onStorage);
        return () => window.removeEventListener('storage', onStorage);
    }, []);

    const toggle = () => {
        applyDom(!readIsDark());
    };

    const label = isDark ? 'Switch to light theme' : 'Switch to dark theme';

    return (
        <button
            type="button"
            className={`theme-toggle-btn ${className}`.trim()}
            onClick={toggle}
            aria-label={label}
            title={label}
        >
            {isDark ? <IconSun className="h-5 w-5" /> : <IconMoon className="h-5 w-5" />}
        </button>
    );
}
