/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.html',
        './app/Http/**/*.php',
    ],
    safelist: [
        'bg-primary', 'bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'bg-sky',
        'bg-white', 'bg-gray-100', 'text-ink', 'text-white', 'text-danger',
    ],
    theme: {
        extend: {
            colors: {
                ink: '#1A1A1A',
                cream: '#FFF4E6',
                paper: '#FFFFFF',
                primary: 'var(--color-primary, #FFD93D)',
                success: '#3CCFBF',
                danger: '#FF6B6B',
                info: '#9B7EDE',
                warning: '#FF9F5A',
                sky: '#4DA8F0',
            },
            boxShadow: {
                brutal: '4px 4px 0 0 #1A1A1A',
                'brutal-sm': '2px 2px 0 0 #1A1A1A',
                'brutal-lg': '6px 6px 0 0 #1A1A1A',
            },
            borderWidth: { 3: '3px' },
            borderRadius: {
                button: '12px',
                card: '16px',
                pill: '9999px',
            },
            fontFamily: {
                sans: ['Space Grotesk', 'system-ui', 'sans-serif'],
                display: ['Space Grotesk', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
