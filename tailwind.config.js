import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: [
                    '"Inter"',
                    "-apple-system",
                    "BlinkMacSystemFont",
                    "sans-serif",
                ],
                display: ['"Fraunces"', "serif"],
            },
            colors: {
                "ios-bg": "#FBF6EF",
                "ios-card": "#FFFFFF",
                "ios-blue": "#BD5D34",
                "ios-blue-hover": "#A34E29",
                "ios-highlight": "#F3E3D3",
                "ios-green": "#6E8B4F",
                "ios-red": "#B4472F",
                "ios-amber": "#C98A34",
                "ios-label": "#3A2E25",
                "ios-secondary": "#8C7A6B",
                "ios-border": "#E9DCC9",
            },
            borderRadius: {
                ios: "1rem",
            },
        },
    },

    plugins: [forms],
};
