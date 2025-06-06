module.exports = {
  content: [
    "./**/*.php",
    "./js/**/*.js"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  corePlugins: {
    preflight: false, // Disable Tailwind's base styles to avoid conflicts with WordPress
  }
}