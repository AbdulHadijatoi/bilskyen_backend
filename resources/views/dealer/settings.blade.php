@extends('layouts.dealer')

@section('title', 'General Settings - Dealer Panel')

@section('content')
<div class="flex w-full flex-col gap-4">
    <div>
        <h2 class="text-xl font-bold">General</h2>
        <p class="text-muted-foreground max-w-xl text-balance">
            Configure your app settings, including theme preferences and
            notifications. These settings will apply across the entire
            application.
        </p>
    </div>

    <hr class="my-3 border-border">

    <div class="flex flex-row items-center justify-between gap-5">
        <div class="space-y-0.5">
            <label class="text-base font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Theme</label>
            <p class="text-muted-foreground mt-1 max-w-xl text-sm">
                Choose your preferred theme. Each theme supports dark and light
                modes, and your selection applies across the app.
            </p>
        </div>

        <select id="theme-select" class="flex h-10 w-min items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 capitalize">
            <option value="light">Light</option>
            <option value="dark">Dark</option>
            <option value="system">System</option>
        </select>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.getElementById('theme-select');
    if (themeSelect) {
        // Get current theme
        const currentTheme = localStorage.getItem('theme') || 'system';
        themeSelect.value = currentTheme;
        
        // Handle theme change
        themeSelect.addEventListener('change', function() {
            const theme = this.value;
            localStorage.setItem('theme', theme);
            
            const root = document.documentElement;
            if (theme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
            } else if (theme === 'dark') {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        });
    }
});
</script>
@endsection

