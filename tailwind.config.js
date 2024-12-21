/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  safelist: [
    'from-white',
    'to-gray-50',
    'to-indigo-50',
    'from-indigo-500',
    'to-indigo-600',
    'group-hover:scale-110',
    'hover:-translate-y-1',
    'hover:shadow-2xl',
    'from-gray-50',
    'to-white',
    'to-purple-500',
    'hover:shadow-lg',
    '-translate-x-1/2'
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}