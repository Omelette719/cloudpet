@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
@endphp

<header class="cp-topbar">

    {{-- Judul --}}
    <h1>
        {{ $isAdmin ? 'Admin Dashboard ⚙️' : 'Dashboard Saya 🐾' }}
    </h1>

    {{-- Sapaan + avatar --}}
    <div class="cp-topbar-user">
        <span>
            Halo, <strong>{{ $user?->name }}</strong>
        </span>
        <span style="font-size: 1.7rem; line-height: 1;">{{ $user?->animal_avatar }}</span>
    </div>

</header>