<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-authenticated" content="false">
    <meta name="theme-color" content="#1a5e5e">
    <title>@yield('title', 'Sign in') — RAHS Task System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='%231a5e5e'/><text x='50' y='68' font-size='48' text-anchor='middle' font-family='sans-serif' font-weight='bold' fill='%2380CBC4'>R</text></svg>">
</head>
<body class="auth-ui min-h-screen bg-background text-foreground antialiased" x-data x-init="$store.theme.init()">
<div class="auth-frame flex min-h-[100dvh] items-center justify-center p-4 sm:p-8">
    @yield('content')
</div>
@yield('scripts')
@include('partials.sw-cleanup')
</body>
</html>
