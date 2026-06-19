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
                    blue: 'var(--ai-primary)',
                    blueDark: 'var(--ai-primary)',
                    cyan: 'var(--ai-accent-line)',
                    navy: {
                        950: 'var(--bg-main)',
                        900: 'var(--bg-canvas)',
                        800: 'var(--bg-surface-soft)',
                    },
                    accent: 'var(--ai-primary)',
                    'accent-deep': 'var(--ai-primary)',
                    glow: 'var(--ai-accent-line)',
                    'glow-soft': 'var(--ai-accent-line)',
                    muted: 'var(--text-muted)',
                    soft: 'var(--text-dim)',
                },
                light: {
                    bg: 'var(--bg-main)',
                    canvas: 'var(--bg-canvas)',
                    surface: 'var(--bg-surface)',
                    'surface-soft': 'var(--bg-surface-soft)',
                    border: 'var(--border-main)',
                    text: 'var(--text-strong)',
                    muted: 'var(--text-muted)',
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
                'ai-radial': 'var(--mesh-radial-image)',
                'ai-radial-soft': 'var(--mesh-radial-image)',
                'ai-radial-light': 'var(--mesh-radial-image)',
                'ai-radial-soft-light': 'var(--mesh-radial-image)',
                'ai-glow-conic': 'var(--mesh-conic-image)',
                'ai-glow-conic-light': 'var(--mesh-conic-image)',
                'ai-btn': 'var(--btn-primary-bg)',
                'ai-btn-light': 'var(--btn-primary-bg)',
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
                'ai-glow': 'var(--shadow-glow)',
                'ai-glow-light': 'var(--shadow-glow)',
                'ai-card': 'var(--shadow-card)',
                'ai-card-hover': 'var(--shadow-card-hover)',
                'ai-card-light': 'var(--shadow-card)',
                'ai-card-light-hover': 'var(--shadow-card-hover)',
                'theme-card': 'var(--shadow-card)',
                'theme-card-hover': 'var(--shadow-card-hover)',
            },
        },
    },

    plugins: [forms],
};
