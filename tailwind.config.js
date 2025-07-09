/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./views/**/*.php",    // ✅ tus formularios y páginas están aquí
    "./*.php"              // ✅ por si usas index.php u otros en la raíz
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}


