import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            divideColor: {
                theme: 'var(--border-main)',
            },
            colors: {
                ai: {
                    blue: '#00A3FF',
                    blueDark: '#0090FF',
                    cyan: '#5EEBFF',
                    navy: {
                        950: '#050816',
                        900: '#071120',
                        800: '#0A1628',
                    },
                    accent: '#00A3FF',
                    'accent-deep': '#0090FF',
                    glow: '#5EEBFF',
                    'glow-soft': '#4FD1FF',
                    muted: '#B6C2D9',
                    soft: '#7E8AA3',
                },
                light: {
                    bg: '#F6FAFF',
                    canvas: '#EEF6FF',
                    surface: '#FFFFFF',
                    'surface-soft': '#F8FBFF',
                    border: '#D8E6F3',
                    text: '#0B1220',
                    muted: '#64748B',
                },
                /** Semantic tokens — map to CSS variables (light + `html.dark`). */
                theme: {
                    main: 'var(--bg-main)',
                    canvas: 'var(--bg-canvas)',
                    surface: 'var(--bg-surface)',
                    'surface-soft': 'var(--bg-surface-soft)',
                    elevated: 'var(--bg-elevated)',
                    fg: 'var(--text-main)',
                    strong: 'var(--text-strong)',
                    body: 'var(--text-body)',
                    muted: 'var(--text-muted)',
                    dim: 'var(--text-dim)',
                    line: 'var(--border-main)',
                    'line-solid': 'var(--border-subtle)',
                    glass: 'var(--glass-surface)',
                    'glass-2': 'var(--glass-surface-2)',
                    mesh: 'var(--mesh-bg)',
                    ring: 'var(--ring-offset)',
                    success: 'var(--color-success)',
                    warning: 'var(--color-warning)',
                    danger: 'var(--color-danger)',
                    'meter-track': 'var(--meter-track)',
                    highlight: 'var(--glass-highlight)',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            backgroundImage: {
                'ai-radial':
                    'radial-gradient(ellipse 80% 50% at 50% -20%, rgba(0, 163, 255, 0.12), transparent)',
                'ai-radial-soft':
                    'radial-gradient(ellipse 100% 70% at 50% 0%, rgba(0, 163, 255, 0.06), transparent)',
                'ai-radial-light':
                    'radial-gradient(ellipse 90% 55% at 50% -15%, rgba(0, 144, 255, 0.14), transparent)',
                'ai-radial-soft-light':
                    'radial-gradient(ellipse 100% 70% at 50% 0%, rgba(56, 189, 248, 0.12), transparent)',
                'ai-glow-conic':
                    'conic-gradient(from 180deg at 50% 50%, rgba(94, 235, 255, 0.06) 0deg, transparent 120deg, rgba(0, 163, 255, 0.05) 240deg, transparent 360deg)',
                'ai-glow-conic-light':
                    'conic-gradient(from 200deg at 50% 40%, rgba(56, 189, 248, 0.12) 0deg, transparent 130deg, rgba(0, 123, 255, 0.06) 260deg, transparent 360deg)',
                'ai-btn':
                    'linear-gradient(135deg, #00A3FF 0%, #0090FF 55%, #0080E6 100%)',
                'ai-btn-light':
                    'linear-gradient(135deg, #007BFF 0%, #0090FF 50%, #00A3FF 100%)',
            },
            ringOffsetColor: {
                ai: '#050816',
                theme: 'var(--ring-offset)',
            },
            animation: {
                'float-slow': 'floatY 7s ease-in-out infinite',
                'pulse-glow': 'pulseGlow 5s ease-in-out infinite',
                'grid-drift': 'gridDrift 22s linear infinite',
            },
            keyframes: {
                floatY: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                pulseGlow: {
                    '0%, 100%': { opacity: '0.35' },
                    '50%': { opacity: '0.65' },
                },
                gridDrift: {
                    '0%': { backgroundPosition: '0 0' },
                    '100%': { backgroundPosition: '120px 120px' },
                },
            },
            boxShadow: {
                'ai-glow':
                    '0 0 48px -10px rgba(0, 163, 255, 0.35), 0 0 96px -24px rgba(94, 235, 255, 0.12)',
                'ai-glow-light':
                    '0 0 40px -12px rgba(0, 144, 255, 0.25), 0 0 80px -20px rgba(56, 189, 248, 0.15)',
                'ai-card':
                    '0 0 0 1px rgba(255,255,255,0.06), 0 20px 60px -28px rgba(0, 0, 0, 0.65)',
                'ai-card-hover':
                    '0 0 0 1px rgba(255,255,255,0.1), 0 24px 70px -24px rgba(0, 163, 255, 0.12)',
                'ai-card-light':
                    '0 1px 0 rgba(15,23,42,0.06), 0 20px 40px rgba(15, 23, 42, 0.08)',
                'ai-card-light-hover':
                    '0 1px 0 rgba(15,23,42,0.08), 0 24px 48px rgba(15, 23, 42, 0.12)',
                'theme-card': 'var(--shadow-card)',
                'theme-card-hover': 'var(--shadow-card-hover)',
            },
        },
    },

    plugins: [forms],
};
