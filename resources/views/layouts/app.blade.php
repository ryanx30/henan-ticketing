<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Henan Ticketing System</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/hpsekuritas.ico?v=1">
    <link rel="shortcut icon" href="/hpsekuritas.ico?v=1">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        @include('partials.sidebar')

        {{-- Main area --}}
        <div class="min-w-0 flex-1 flex flex-col">
            @include('layouts.navigation')

            {{-- Page Content --}}
            <main class="min-w-0 flex-1 bg-gray-100">
                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>