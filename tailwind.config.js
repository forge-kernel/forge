module.exports = {
  content: [
    '/app/resources/**/*.php',
    '/app/resources/**/*.js',
    '/app/resources/**/*.css',
    '/modules/**/src/Resources/**/*.php',
    '/modules/**/src/Resources/**/*.js',
    '/modules/**/src/Resources/**/*.css',
  ],
  theme: {
    extends: {},
  },
  plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography")],
};
