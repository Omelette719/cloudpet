<div>

    {{-- Welcome banner admin --}}
    <div class="cp-banner">
        <div style="position: absolute; right: 0.8rem; top: -0.95rem; font-size: 5.2rem; opacity: 0.18;">⚙️</div>
        <div style="position: absolute; right: 7rem; bottom: 0.15rem; font-size: 2.2rem; opacity: 0.15;">🛡️</div>

        <div style="position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
            <div>
                <p>Panel Administrasi</p>
                <h2>Halo, {{ auth()->user()->name }}! {{ auth()->user()->animal_avatar }}</h2>
                <p>Kelola platform CloudPet dari sini.</p>
            </div>
            <span class="cp-badge" style="background: rgba(255,255,255,0.2); color: #fff;">
                ⭐ Super Admin
            </span>
        </div>
    </div>

    {{-- Stats --}}
    <div>
        <h3 class="cp-section-title">📊 Ringkasan Platform</h3>
        <div class="cp-grid-3">
            @foreach([
                ['icon'=>'👥','label'=>'Total User',  'value'=> $totalUsers],
                ['icon'=>'⭐','label'=>'Total Admin', 'value'=> $totalAdmin],
                ['icon'=>'🐾','label'=>'Total Akun',  'value'=> $totalUsers + $totalAdmin],
            ] as $stat)
                <div class="cp-stat">
                    <span style="font-size: 1.8rem;">{{ $stat['icon'] }}</span>
                    <div>
                        <p class="cp-stat-label">{{ $stat['label'] }}</p>
                        <p class="cp-stat-value">{{ $stat['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Tabel pengguna --}}
    <div>
        <h3 class="cp-section-title">👥 Daftar Pengguna</h3>

        <div class="cp-table-wrap">

            {{-- Search & filter --}}
            <div class="cp-table-head">
                <p style="font-size: 0.85rem; font-weight: 700; color: #4a6344;">{{ $users->total() }} pengguna ditemukan</p>
                <div class="cp-inline-controls">
                    <input wire:model.live.debounce.400ms="search"
                           type="text"
                           class="cp-table-control"
                           placeholder="Cari nama / email..." />
                    <select wire:model.live="filter"
                            class="cp-table-control">
                        <option value="all">Semua Role</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            {{-- Table --}}
            <div style="overflow-x: auto;">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td style="font-size: 1.35rem;">{{ $u->animal_avatar }}</td>
                                <td style="font-weight: 700; color: #45613f;">{{ $u->name }}</td>
                                <td style="color: #677d62; font-weight: 600;">{{ $u->email }}</td>
                                <td>
                                    @if($u->role === 'admin')
                                        <span class="cp-badge">⭐ Admin</span>
                                    @else
                                        <span class="cp-badge">🌱 User</span>
                                    @endif
                                </td>
                                <td style="color: #809279; font-size: 0.75rem; font-weight: 600;">{{ $u->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 2.3rem 0.9rem; text-align: center; color: #8ca582; font-weight: 700;">
                                    <span style="font-size: 2rem; display: block; margin-bottom: 0.4rem;">🐾</span>
                                    Tidak ada pengguna ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div style="padding: 0.9rem 1rem; border-top: 1px solid #edf4e4;">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

</div>