import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Акцентная палитра сайта — бежево-оранжевые тона взамен исходной
            // синей/индиго (см. запрос пользователя). 300/500/800 привязаны к
            // присланным HEX (#ceb195 / #a78768 / #705123), остальные ступени
            // рассчитаны интерполяцией в том же тоне (H≈31°), чтобы шкала вела
            // себя как обычная шкала Tailwind (bg-primary-600, text-primary-700 и т.д.).
            colors: {
                primary: {
                    50: '#fcf7f3',
                    100: '#f6ede4',
                    200: '#e7d7c5',
                    300: '#ceb195',
                    400: '#b99d7e',
                    500: '#a78768',
                    600: '#9b744b',
                    700: '#866037',
                    800: '#705123',
                    900: '#50361b',
                    950: '#302212',
                },
            },
        },
    },

    plugins: [forms, flowbite],
};
