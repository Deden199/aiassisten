import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        container: {
            center: true,
            padding: {
                DEFAULT: '1rem',
                md: '1.5rem',
            },
            screens: {
                '2xl': '1536px',
            },
        },
        extend: {
            colors: {
                primary: 'rgb(var(--primary) / <alpha-value>)',
                'primary-foreground': 'rgb(var(--primary-foreground) / <alpha-value>)',
                secondary: 'rgb(var(--secondary) / <alpha-value>)',
                accent: 'rgb(var(--accent) / <alpha-value>)',
                muted: 'rgb(var(--muted) / <alpha-value>)',
                border: 'rgb(var(--border) / <alpha-value>)',
                ring: 'rgb(var(--ring) / <alpha-value>)',
                success: 'rgb(var(--success) / <alpha-value>)',
                warning: 'rgb(var(--warning) / <alpha-value>)',
                danger: 'rgb(var(--danger) / <alpha-value>)',
            },
            borderRadius: {
                sm: '8px',
                md: '12px',
                lg: '16px',
                xl: '24px',
                '2xl': '28px',
            },
            boxShadow: {
                xs: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                sm: '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px 0 rgb(0 0 0 / 0.06)',
                md: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
                lg: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
                xl: '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04)',
            },
            spacing: {
                1: '4px',
                2: '8px',
                3: '12px',
                4: '16px',
                6: '24px',
                8: '32px',
                12: '48px',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"Source Serif Pro"', ...defaultTheme.fontFamily.serif],
            },
        },
    },
    plugins: [],
};
