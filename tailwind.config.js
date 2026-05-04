import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // Layout
                sidebar:  '#11141a',
                main:     '#0c0e12',
                panel:    '#1a1a1a',
                border:   '#1e2330',

                // Brand
                accent:   '#129247',

                // Status
                live:     '#32b3e6',
                danger:   '#ef4444',
                warning:  '#f59e0b',

                // Text
                text:     '#e5e7eb',
                label:    '#9ca3af',
                muted:    '#6b7280',
            },

            fontFamily: {
                sans:  ['Inter', ...defaultTheme.fontFamily.sans],
                mono:  ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
        },
    },

    plugins: [forms],
};