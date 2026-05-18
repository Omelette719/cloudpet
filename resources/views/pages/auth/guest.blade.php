<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CloudPet ☁️</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased theme-guest cp-auth-page">

    {{-- Hewan mengambang di background --}}
    @include('partials.bg-animals')

    <div class="cp-auth-glow" aria-hidden="true"></div>

    <main class="cp-auth-shell">
        {{-- Konten halaman --}}
        {{ $slot }}

    </main>

    @livewireScripts
</body>
</html>