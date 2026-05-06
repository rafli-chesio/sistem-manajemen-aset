import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                jakarta: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Brand palette (indigo-based, consistent with the sidebar)
                brand: {
                    50:  '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    900: '#312e81',
                },
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                'card': '0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07)',
                'glass': '0 4px 30px rgba(0, 0, 0, 0.05)',
                'glow': '0 0 20px rgba(99, 102, 241, 0.4)',
                'soft': '0 10px 40px -10px rgba(0,0,0,0.08)',
            },
            animation: {
                'fade-in':   'fadeIn 0.3s ease-out',
                'slide-up':  'slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                'slide-down': 'slideDown 0.3s ease-out',
                'float':     'float 3s ease-in-out infinite',
            },
            keyframes: {
                fadeIn:  { from: { opacity: '0' },                        to: { opacity: '1' } },
                slideUp: { from: { opacity: '0', transform: 'translateY(15px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                slideDown: { from: { opacity: '0', transform: 'translateY(-10px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-5px)' } },
            },
        },
    },

    plugins: [forms],
};
