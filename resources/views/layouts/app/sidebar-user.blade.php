@php $user = auth()->user(); @endphp

<aside class="cp-sidebar">

    {{-- Logo --}}
    <div class="cp-sidebar-header">
        <div class="cp-brand" style="margin-bottom: 0;">
            <span class="cp-brand-logo">☁️</span>
            <span class="cp-brand-name"><span class="cp-brand-cloud">Cloud</span><span
                    class="cp-brand-pet">Pet</span></span>
        </div>
        <p class="cp-sidebar-subtitle">Platform Awan Imut</p>
    </div>

    {{-- Navigasi --}}
    <nav class="cp-sidebar-nav">

        <a href="{{ route('user.dashboard') }}" wire:navigate
            class="cp-side-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('cloud.index') }}" wire:navigate
            class="cp-side-link {{ request()->routeIs('cloud.index') ? 'active' : '' }}">
            <span>☁️</span>
            <span>Layanan Cloud</span>
            <span class="cp-chip">Beta</span>
        </a>
        <a href="{{ route('cloud.storage') }}" wire:navigate
            class="cp-side-link {{ request()->routeIs('cloud.storage') ? 'active' : '' }}">
            <span>💾</span>
            <span>Storage</span>
            <span class="cp-chip">Beta</span>
        </a>
        <a class="cp-side-link soon">
            <span>🔑</span>
            <span>Akses Kunci</span>
            <span class="cp-chip">Soon</span>
        </a>
    </nav>

    {{-- User info + logout --}}
    <div class="cp-sidebar-user">
        <div class="cp-user-box">
            <span style="font-size: 1.7rem;">{{ $user->animal_avatar }}</span>
            <div>
                <p class="cp-user-name">{{ $user->name }}</p>
                <p class="cp-user-role">Member</p>
            </div>
        </div>
        <div style="margin-top: 0.45rem;">
            <livewire:auth.logout variant="user" />
        </div>
    </div>

</aside>
