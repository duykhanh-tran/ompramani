/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        border: 'hsl(214.3 31.8% 91.4%)',
        background: 'hsl(0 0% 100%)',
        foreground: 'hsl(222.2 84% 4.9%)',
        primary: {
          DEFAULT: '#0284c7', // Sky-600
          foreground: '#ffffff',
          hover: '#0369a1',
        },
        sidebar: {
          DEFAULT: '#0f172a', // Slate-900
          foreground: '#f8fafc',
          muted: '#94a3b8',
          hover: '#1e293b',
          active: '#334155',
        },
      },
    },
  },
  plugins: [],
};
