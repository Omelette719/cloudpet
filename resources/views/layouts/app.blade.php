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
<body class="h-full antialiased {{ auth()->user()?->isAdmin() ? 'theme-admin' : 'theme-user' }}">

<div class="cp-app-shell">

    {{-- Sidebar sesuai role --}}
    @if(auth()->user()?->isAdmin())
        @include('layouts.app.sidebar')
    @else
        @include('layouts.app.sidebar-user')
    @endif

    {{-- Area kanan --}}
    <div class="cp-content">

        {{-- Topbar --}}
        @include('layouts.app.topbar')

        {{-- Flash message --}}
        @if(session()->has('message'))
            <div class="cp-card" style="margin: 1rem 1.2rem 0;">
                ✅ {{ session('message') }}
            </div>
        @endif

        {{-- Konten halaman --}}
        <main class="cp-main">
            {{ $slot }}
        </main>

    </div>
</div>

@livewireScripts
</body>
</html>