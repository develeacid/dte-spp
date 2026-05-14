import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    hover: 'rgb(var(--color-primary-hover) / <alpha-value>)',
                    light: 'rgb(var(--color-primary-light) / <alpha-value>)',
                    dark: 'rgb(var(--color-primary-dark) / <alpha-value>)',
                },
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                background: 'rgb(var(--color-background) / <alpha-value>)',
                'portal-bg':      'rgb(var(--color-portal-bg)      / <alpha-value>)',
                'portal-surface': 'rgb(var(--color-portal-surface) / <alpha-value>)',
                'portal-border':  'rgb(var(--color-portal-border)  / <alpha-value>)',
                'portal-text':    'rgb(var(--color-portal-text)    / <alpha-value>)',
                'portal-muted':   'rgb(var(--color-portal-muted)   / <alpha-value>)',
                'portal-accent':  'rgb(var(--color-portal-accent)  / <alpha-value>)',
            },
            fontFamily: {
                sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
            },
            width: {
                sidebar: 'var(--sidebar-width)',
                'sidebar-collapsed': 'var(--sidebar-collapsed-width)',
            },
        },
    },

    plugins: [forms, typography],
};
