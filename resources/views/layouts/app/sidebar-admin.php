@php $user = auth()->user(); @endphp

<aside class="w-64 shrink-0 flex flex-col dashboard-panel theme-admin h-screen overflow-y-auto">

    {{-- Logo --}}
    <div class="px-5 py-5 border-b border-[rgba(118,79,47,0.35)]">
        <div class="flex items-center gap-2">
            <span class="text-2xl">☁️</span>
            <span class="font-display font-extrabold text-xl dashboard-title">
                <span>Cloud</span><span style="opacity:.82;">Pet</span>
            </span>
        </div>
        <p class="text-xs font-semibold mt-1 dashboard-subtitle">Admin Control Panel ⚙️</p>
    </div>

    {{-- Navigasi --}}
    <nav class="flex-1 px-3 py-4 space-y-1">
        <p class="px-3 text-[10px] font-extrabold uppercase tracking-widest dashboard-subtitle mb-2">Manajemen</p>

        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-bold transition-all
                  {{ request()->routeIs('admin.dashboard')
                     ? 'bg-[rgb(118,79,47)] text-white shadow-md'
                     : 'dashboard-subtitle hover:bg-[rgba(118,79,47,0.18)] hover:text-[rgb(233,220,202)]' }}">
            <span class="text-lg">🏠</span>
            <span>Dashboard</span>
        </a>

        <a class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-bold dashboard-subtitle cursor-not-allowed">
            <span class="text-lg">👥</span>
            <span>Kelola User</span>
            <span class="ml-auto text-[10px] bg-[rgba(118,79,47,0.22)] text-[rgb(233,220,202)] px-1.5 py-0.5 rounded-full font-bold">Soon</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-bold dashboard-subtitle cursor-not-allowed">
            <span class="text-lg">📦</span>
            <span>Paket Layanan</span>
            <span class="ml-auto text-[10px] bg-[rgba(118,79,47,0.22)] text-[rgb(233,220,202)] px-1.5 py-0.5 rounded-full font-bold">Soon</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-bold dashboard-subtitle cursor-not-allowed">
            <span class="text-lg">📜</span>
            <span>Log Aktivitas</span>
            <span class="ml-auto text-[10px] bg-[rgba(118,79,47,0.22)] text-[rgb(233,220,202)] px-1.5 py-0.5 rounded-full font-bold">Soon</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-bold dashboard-subtitle cursor-not-allowed">
            <span class="text-lg">⚙️</span>
            <span>Pengaturan</span>
            <span class="ml-auto text-[10px] bg-[rgba(118,79,47,0.22)] text-[rgb(233,220,202)] px-1.5 py-0.5 rounded-full font-bold">Soon</span>
        </a>
    </nav>

    {{-- Admin info + logout --}}
    <div class="px-3 py-4 border-t border-[rgba(118,79,47,0.35)]">
        <div class="flex items-center gap-3 px-3 py-3 rounded-2xl bg-[rgba(118,79,47,0.18)]">
            <span class="text-3xl shrink-0">{{ $user->animal_avatar }}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold dashboard-title truncate">{{ $user->name }}</p>
                <p class="text-xs font-semibold dashboard-subtitle">⭐ Administrator</p>
            </div>
        </div>
        <div class="mt-2 px-1">
            <livewire:auth.logout variant="admin" />
        </div>
    </div>

</aside>