@php $user = auth()->user(); @endphp

<aside class="cp-sidebar">
    <div class="cp-sidebar-header">
        <div class="cp-brand" style="margin-bottom: 0;">
            <span class="cp-brand-logo">☁️</span>
            <span class="cp-brand-name"><span class="cp-brand-cloud">Cloud</span><span class="cp-brand-pet">Pet</span></span>
        </div>
        <p class="cp-sidebar-subtitle">Control Center Admin</p>
    </div>

    <nav class="cp-sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" wire:navigate
           class="cp-side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span>📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.plans') }}" wire:navigate
           class="cp-side-link {{ request()->routeIs('admin.plans') ? 'active' : '' }}">
            <span>🧩</span>
            <span>Kelola Plans</span>
        </a>

        <a href="{{ route('admin.users') }}" wire:navigate
           class="cp-side-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <span>👥</span>
            <span>Users & Billing</span>
        </a>

        <a href="{{ route('admin.logs') }}" wire:navigate
           class="cp-side-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
            <span>🛡️</span>
            <span>Logs & Monitoring</span>
        </a>
    </nav>

    <div class="cp-sidebar-user">
        <div class="cp-user-box">
            <span style="font-size: 1.7rem;">{{ $user->animal_avatar }}</span>
            <div>
                <p class="cp-user-name">{{ $user->name }}</p>
                <p class="cp-user-role">Super Admin</p>
            </div>
        </div>
        <div style="margin-top: 0.45rem;">
            <livewire:auth.logout variant="admin" />
        </div>
    </div>
</aside>
