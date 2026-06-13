import { useEffect, useState } from 'react';

/**
 * Reads semantic colors from CSS variables on :root (updates when `html.dark` toggles).
 */
export function readChartTheme() {
    if (typeof document === 'undefined') {
        return {
            tick: '#64748b',
            label: '#475569',
            grid: 'rgba(148, 163, 184, 0.2)',
            axisLine: 'rgba(148, 163, 184, 0.35)',
            tooltipBg: 'rgba(255, 255, 255, 0.95)',
            tooltipBorder: '#d8e6f3',
            tooltipLabel: '#0b1220',
            tooltipItem: '#475569',
        };
    }
    const s = getComputedStyle(document.documentElement);
    const v = (name, fallback) => {
        const raw = s.getPropertyValue(name).trim();
        return raw || fallback;
    };
    return {
        tick: v('--text-dim', '#64748b'),
        label: v('--text-muted', '#475569'),
        grid: v('--border-main', 'rgba(148, 163, 184, 0.2)'),
        axisLine: v('--border-main', 'rgba(148, 163, 184, 0.35)'),
        tooltipBg: v('--glass-surface-2', 'rgba(255, 255, 255, 0.95)'),
        tooltipBorder: v('--border-subtle', '#d8e6f3'),
        tooltipLabel: v('--text-strong', '#0b1220'),
        tooltipItem: v('--text-muted', '#475569'),
    };
}

/** Subscribe to `html` class changes (theme toggle) + cross-tab storage. */
export function useChartTheme() {
    const [theme, setTheme] = useState(() => readChartTheme());

    useEffect(() => {
        const sync = () => setTheme(readChartTheme());
        const obs = new MutationObserver(sync);
        obs.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
        window.addEventListener('storage', sync);
        return () => {
            obs.disconnect();
            window.removeEventListener('storage', sync);
        };
    }, []);

    return theme;
}
