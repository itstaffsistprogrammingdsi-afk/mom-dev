import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                display: ['var(--text-display)', { lineHeight: 'var(--leading-tight)', letterSpacing: '-0.02em' }],
                title: ['var(--text-title)', { lineHeight: 'var(--leading-tight)', letterSpacing: '-0.015em' }],
                heading: ['var(--text-heading)', { lineHeight: 'var(--leading-snug)', letterSpacing: '-0.01em' }],
                subheading: ['var(--text-subheading)', { lineHeight: 'var(--leading-snug)', letterSpacing: '-0.005em' }],
                body: ['var(--text-body)', { lineHeight: 'var(--leading-body)' }],
                small: ['var(--text-small)', { lineHeight: 'var(--leading-snug)' }],
                meta: ['var(--text-meta)', { lineHeight: 'var(--leading-snug)' }],
            },
            borderRadius: {
                xs: 'var(--radius-xs)',
                sm: 'var(--radius-sm)',
                md: 'var(--radius-md)',
                lg: 'var(--radius-lg)',
                xl: 'var(--radius-xl)',
                '2xl': 'var(--radius-2xl)',
                '3xl': 'var(--radius-3xl)',
                pill: 'var(--radius-pill)',
            },
            spacing: {
                page: 'var(--page-gutter)',
                'page-lg': 'var(--page-gutter-lg)',
                section: 'var(--section-gap)',
                card: 'var(--card-padding)',
                'card-lg': 'var(--card-padding-lg)',
            },
            maxWidth: {
                reading: 'var(--reading-measure)',
            },
            boxShadow: {
                'soft-xs': '0 1px 2px 0 hsl(var(--shadow-color) / 0.04)',
                'soft-sm': '0 1px 3px 0 hsl(var(--shadow-color) / 0.05), 0 1px 2px -1px hsl(var(--shadow-color) / 0.05)',
                'soft-md': '0 4px 12px -2px hsl(var(--shadow-color) / 0.08), 0 2px 6px -2px hsl(var(--shadow-color) / 0.05)',
                'soft-lg': '0 12px 32px -8px hsl(var(--shadow-color) / 0.12), 0 4px 12px -4px hsl(var(--shadow-color) / 0.06)',
                'soft-xl': '0 24px 64px -16px hsl(var(--shadow-color) / 0.16), 0 8px 24px -12px hsl(var(--shadow-color) / 0.08)',
                popover: '0 12px 32px -8px hsl(var(--shadow-color) / 0.16), 0 4px 12px -4px hsl(var(--shadow-color) / 0.08)',
                modal: '0 32px 80px -24px hsl(var(--shadow-color) / 0.3), 0 12px 32px -12px hsl(var(--shadow-color) / 0.16)',
                focus: '0 0 0 4px hsl(var(--ring) / 0.18)',
            },
            transitionDuration: {
                fast: 'var(--duration-fast)',
                base: 'var(--duration-base)',
                slow: 'var(--duration-slow)',
            },
            transitionTimingFunction: {
                'out-soft': 'var(--ease-out)',
                'in-out-soft': 'var(--ease-in-out)',
            },
            colors: {
                background: 'hsl(var(--background) / <alpha-value>)',
                foreground: 'hsl(var(--foreground) / <alpha-value>)',
                surface: {
                    DEFAULT: 'hsl(var(--surface) / <alpha-value>)',
                    foreground: 'hsl(var(--surface-foreground) / <alpha-value>)',
                },
                card: {
                    DEFAULT: 'hsl(var(--card) / <alpha-value>)',
                    foreground: 'hsl(var(--card-foreground) / <alpha-value>)',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover) / <alpha-value>)',
                    foreground: 'hsl(var(--popover-foreground) / <alpha-value>)',
                },
                primary: {
                    DEFAULT: 'hsl(var(--primary) / <alpha-value>)',
                    foreground: 'hsl(var(--primary-foreground) / <alpha-value>)',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary) / <alpha-value>)',
                    foreground: 'hsl(var(--secondary-foreground) / <alpha-value>)',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted) / <alpha-value>)',
                    foreground: 'hsl(var(--muted-foreground) / <alpha-value>)',
                },
                subtle: {
                    foreground: 'hsl(var(--faint-foreground) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent) / <alpha-value>)',
                    foreground: 'hsl(var(--accent-foreground) / <alpha-value>)',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive) / <alpha-value>)',
                    foreground: 'hsl(var(--destructive-foreground) / <alpha-value>)',
                    soft: 'hsl(var(--destructive-soft) / <alpha-value>)',
                    strong: 'hsl(var(--destructive-strong) / <alpha-value>)',
                },
                success: {
                    DEFAULT: 'hsl(var(--success) / <alpha-value>)',
                    soft: 'hsl(var(--success-soft) / <alpha-value>)',
                    strong: 'hsl(var(--success-strong) / <alpha-value>)',
                },
                warning: {
                    DEFAULT: 'hsl(var(--warning) / <alpha-value>)',
                    soft: 'hsl(var(--warning-soft) / <alpha-value>)',
                    strong: 'hsl(var(--warning-strong) / <alpha-value>)',
                },
                info: {
                    DEFAULT: 'hsl(var(--info) / <alpha-value>)',
                    soft: 'hsl(var(--info-soft) / <alpha-value>)',
                    strong: 'hsl(var(--info-strong) / <alpha-value>)',
                },
                ai: {
                    DEFAULT: 'hsl(var(--ai) / <alpha-value>)',
                    foreground: 'hsl(var(--ai-foreground) / <alpha-value>)',
                    soft: 'hsl(var(--ai-soft) / <alpha-value>)',
                    border: 'hsl(var(--ai-border) / <alpha-value>)',
                },
                tag: {
                    person: 'hsl(var(--tag-person) / <alpha-value>)',
                    'person-soft': 'hsl(var(--tag-person-soft) / <alpha-value>)',
                    'person-border': 'hsl(var(--tag-person-border) / <alpha-value>)',
                    division: 'hsl(var(--tag-division) / <alpha-value>)',
                    'division-soft': 'hsl(var(--tag-division-soft) / <alpha-value>)',
                    'division-border': 'hsl(var(--tag-division-border) / <alpha-value>)',
                    topic: 'hsl(var(--tag-topic) / <alpha-value>)',
                    'topic-soft': 'hsl(var(--tag-topic-soft) / <alpha-value>)',
                    'topic-border': 'hsl(var(--tag-topic-border) / <alpha-value>)',
                },
                border: 'hsl(var(--border) / <alpha-value>)',
                input: 'hsl(var(--input) / <alpha-value>)',
                ring: 'hsl(var(--ring) / <alpha-value>)',
                chart: {
                    1: 'hsl(var(--chart-1) / <alpha-value>)',
                    2: 'hsl(var(--chart-2) / <alpha-value>)',
                    3: 'hsl(var(--chart-3) / <alpha-value>)',
                    4: 'hsl(var(--chart-4) / <alpha-value>)',
                    5: 'hsl(var(--chart-5) / <alpha-value>)',
                },
                sidebar: {
                    DEFAULT: 'hsl(var(--sidebar-background) / <alpha-value>)',
                    foreground: 'hsl(var(--sidebar-foreground) / <alpha-value>)',
                    primary: 'hsl(var(--sidebar-primary) / <alpha-value>)',
                    'primary-foreground': 'hsl(var(--sidebar-primary-foreground) / <alpha-value>)',
                    accent: 'hsl(var(--sidebar-accent) / <alpha-value>)',
                    'accent-foreground': 'hsl(var(--sidebar-accent-foreground) / <alpha-value>)',
                    border: 'hsl(var(--sidebar-border) / <alpha-value>)',
                    ring: 'hsl(var(--sidebar-ring) / <alpha-value>)',
                },
            },
        },
    },
    plugins: [require('tailwindcss-animate')],
};
