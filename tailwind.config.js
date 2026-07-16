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

    safelist: [
        'bg-primary-50',
        'bg-primary-900',
        'bg-yellow-400',
        'bg-red-600',
        'bg-blue-600',
        'bg-yellow-50',
        'bg-red-50',
        'bg-blue-50',
        'border-yellow-200',
        'border-red-200',
        'border-blue-200',
        'bg-yellow-300',
        'bg-red-500',
        'bg-blue-500',
        'text-yellow-900',
        'text-yellow-800',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: 'hsl(169, 100%, 95%)',
                    100: 'hsl(173, 100%, 85%)',
                    200: 'hsl(174, 87%, 74%)',
                    300: 'hsl(174, 61%, 62%)',
                    400: 'hsl(175, 48%, 50%)',
                    500: 'hsl(176, 75%, 36%)',
                    600: 'hsl(176, 100%, 26%)',
                    700: 'hsl(176, 100%, 21%)',
                    800: 'hsl(176, 100%, 16%)',
                    900: 'hsl(176, 100%, 11%)',
                    950: 'hsl(174, 100%, 6%)',
                },
                secondary: {
                    50: 'hsl(22, 100%, 98%)',
                    100: 'hsl(29, 100%, 94%)',
                    200: 'hsl(32, 100%, 86%)',
                    300: 'hsl(34, 100%, 68%)',
                    400: 'hsl(39, 100%, 46%)',
                    500: 'hsl(39, 100%, 39%)',
                    600: 'hsl(38, 100%, 33%)',
                    700: 'hsl(38, 100%, 26%)',
                    800: 'hsl(37, 100%, 20%)',
                    900: 'hsl(36, 100%, 14%)',
                    950: 'hsl(33, 100%, 8%)',
                },
                tertiary: {
                    50: 'hsl(213, 100%, 98%)',
                    100: 'hsl(210, 100%, 95%)',
                    200: 'hsl(207, 100%, 89%)',
                    300: 'hsl(205, 86%, 77%)',
                    400: 'hsl(204, 57%, 66%)',
                    500: 'hsl(204, 43%, 55%)',
                    600: 'hsl(203, 44%, 44%)',
                    700: 'hsl(201, 61%, 33%)',
                    800: 'hsl(198, 100%, 21%)',
                    900: 'hsl(199, 100%, 15%)',
                    950: 'hsl(201, 100%, 9%)',
                },
                neutral: {
                    50: 'hsl(0, 9%, 98%)',
                    100: 'hsl(0, 3%, 94%)',
                    200: 'hsl(0, 2%, 89%)',
                    300: 'hsl(0, 0%, 78%)',
                    400: 'hsl(0, 0%, 67%)',
                    500: 'hsl(180, 0%, 57%)',
                    600: 'hsl(180, 0%, 46%)',
                    700: 'hsl(210, 1%, 37%)',
                    800: 'hsl(180, 1%, 28%)',
                    900: 'hsl(180, 2%, 19%)',
                    950: 'hsl(180, 4%, 11%)',
                },
            },
        },
    },

    plugins: [forms],
};
