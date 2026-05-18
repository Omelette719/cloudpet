import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans:    ['Nunito', ...defaultTheme.fontFamily.sans],
                display: ['"Baloo 2"', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'float':      'float 6s ease-in-out infinite',
                'float-slow': 'float 9s ease-in-out infinite',
                'float-fast': 'float 4s ease-in-out infinite',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0) rotate(0deg)' },
                    '33%':      { transform: 'translateY(-14px) rotate(6deg)' },
                    '66%':      { transform: 'translateY(7px) rotate(-4deg)' },
                },
            },
        },
    },
    plugins: [],
};