/**@type {import('tailwindcss').Config} */
export default {
  content: [
    "./src/**/*.{html,js,php}",
    "./public/**/*.{html,js,php}",
    "./views/**/*.php",
    "./**/*.{html,js,php}"  // Include all HTML and PHP files
  ],
  theme: {
    extend: {
      colors: {
        gold: {
          50: '#FEF9E8',
          100: '#FDF0C4',
          200: '#FAE190',
          300: '#F7D15C',
          400: '#F4C029',
          500: '#E5AD0F',
          600: '#B98A0C',
          700: '#8E6809',
          800: '#624605',
          900: '#352503',
        },
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}