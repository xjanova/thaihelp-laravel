import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                metal: {
                    dark: '#0a0e17',
                    base: '#111827',
                    mid: '#1e293b',
                    light: '#334155',
                    shine: '#94a3b8',
                    chrome: '#cbd5e1',
                },
            },
            fontFamily: {
                thai: ['Noto Sans Thai', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
