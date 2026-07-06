<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
                },
                colors: {
                    border: "var(--border)",
                    input: "var(--input)",
                    ring: "var(--ring)",
                    background: "var(--background)",
                    foreground: "var(--foreground)",
                    primary: {
                        DEFAULT: "var(--primary)",
                        foreground: "var(--primary-foreground)",
                        hover: "var(--primary-hover)",
                    },
                    secondary: {
                        DEFAULT: "var(--secondary)",
                        foreground: "var(--secondary-foreground)",
                    },
                    destructive: {
                        DEFAULT: "var(--destructive)",
                        foreground: "var(--destructive-foreground)",
                    },
                    muted: {
                        DEFAULT: "var(--muted)",
                        foreground: "var(--muted-foreground)",
                    },
                    accent: {
                        DEFAULT: "var(--accent)",
                        foreground: "var(--accent-foreground)",
                    },
                    popover: {
                        DEFAULT: "var(--popover)",
                        foreground: "var(--popover-foreground)",
                    },
                    card: {
                        DEFAULT: "var(--card)",
                        foreground: "var(--card-foreground)",
                    },
                },
                borderRadius: {
                    lg: "var(--radius-lg)",
                    md: "var(--radius)",
                    sm: "calc(var(--radius) - 2px)",
                    xl: "var(--radius-xl)",
                },
                boxShadow: {
                    card: "var(--shadow-card)",
                    'card-hover': "var(--shadow-card-hover)",
                    nav: "var(--shadow-nav)",
                },
            },
        },
    }
</script>
