/**
 * Extrait tailwind.config.js — sidebar LOGIQUALI (Tailwind v3).
 * ------------------------------------------------------------------
 * Projet actuel = Tailwind v4 (voir resources/css/app.css @theme).
 * Si votre projet est en v3 (darkMode: 'class'), fusionnez cet extrait
 * dans votre tailwind.config.js existant.
 *
 * Couleurs : green (#1A6B3C + déclinaisons), gold, danger, card, border,
 * surface-muted, foreground, muted-foreground (clair + sombre via CSS vars).
 * Les variables sont définies dans resources/css/sidebar-tokens.css.
 *
 * Largeurs sidebar : 74px réduit / 260px élargi / 270px drawer mobile.
 */

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './app/View/**/*.php',
    './config/navigation.php',
  ],
  theme: {
    extend: {
      colors: {
        // Vert marque LOGIQUALI (fond actif = green, highlight = green/10).
        green: {
          DEFAULT: 'var(--color-green)',
          dark: 'var(--color-green-dark)',
          light: 'var(--color-green-light)',
          50: '#f0f7f2',
          100: 'var(--color-green-light)',
          600: 'var(--color-green)',
          700: '#1A6B3C',
          800: 'var(--color-green-dark)',
        },
        gold: 'var(--color-gold)',
        danger: 'var(--color-danger)',
        card: 'var(--color-card)',
        border: 'var(--color-border)',
        'surface-muted': 'var(--color-surface-muted)',
        foreground: 'var(--color-foreground)',
        'muted-foreground': 'var(--color-muted-foreground)',
      },
      width: {
        sidebar: '260px',
        'sidebar-collapsed': '74px',
        'sidebar-mobile': '270px',
      },
      height: {
        'brand-bar': '64px',
      },
    },
  },
  plugins: [],
};
