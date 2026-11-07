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
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            // YuLuk brand, shared with the mobile apps.
            colors: {
                plum: {
                    DEFAULT: '#2E1B2E',
                    dark: '#241322',
                    soft: '#EDE6EC',
                },
                gold: {
                    DEFAULT: '#C99A3F',
                    soft: '#F6ECD8',
                },
                cream: '#FBF7F2',
                ink: '#2A222A',
                muted: '#8A7F88',
            },
        },
    },

    plugins: [forms, typography],
};
