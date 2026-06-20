<div>
    {{-- Welcome banner --}}
    <div class="cp-banner">
        <div style="position: absolute; right: 1rem; top: -0.8rem; font-size: 4.8rem; opacity: 0.22;">{{ $user->animal_avatar }}</div>
        <div style="position: absolute; right: 6rem; bottom: 0.1rem; font-size: 2.5rem; opacity: 0.15;">☁️</div>

        <div style="position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
            <div>
                <p>Selamat datang kembali!</p>
                <h2>{{ $user->name }} {{ $user->animal_avatar }}</h2>
                <p>Member sejak {{ $user->created_at->translatedFormat('F Y') }}</p>
            </div>
            <span class="cp-badge" style="background: rgba(255,255,255,0.2); color: #fff;">
                🌱 Member Aktif
            </span>
        </div>
    </div>

    {{-- Stats Dinamis --}}
    <div>
        <h3 class="cp-section-title">📊 Ringkasan Akun</h3>
        <div class="cp-grid-4">
            @foreach([
                ['icon'=>'☁️','label'=>'Layanan Aktif',  'value'=> '1'],
                ['icon'=>'💾','label'=>'Storage Terpakai','value'=> 'N/A'],
                ['icon'=>'📦','label'=>'Bucket Aktif',   'value'=> $bucketCount],
                ['icon'=>'🔑','label'=>'Access Key',     'value'=> $bucketCount > 0 ? 'Tersedia' : '-'],
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

    {{-- Info akun + tips --}}
    <div class="cp-grid-2">
        {{-- Info akun --}}
        <div>
            <h3 class="cp-section-title">👤 Info Akun</h3>
            <div class="cp-card" style="padding: 0; overflow: hidden;">
                @foreach([
                    ['label'=>'Avatar',    'value'=> $user->animal_avatar . ' ' . $user->name],
                    ['label'=>'Email',     'value'=> $user->email],
                    ['label'=>'Status',    'value'=> '🌱 Member Aktif'],
                    ['label'=>'Bergabung', 'value'=> $user->created_at->translatedFormat('d F Y')],
                ] as $row)
                    <div style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1rem; border-bottom: 1px solid #eef5e7;">
                        <span style="width: 6rem; flex-shrink: 0; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #89a081;">{{ $row['label'] }}</span>
                        <span style="font-size: 0.84rem; font-weight: 700; color: #455b41;">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tips --}}
        <div>
            <h3 class="cp-section-title">💡 Info & Tips</h3>
            <div class="cp-card" style="display: grid; gap: 0.65rem;">
                @foreach([
                    ['emoji'=>'📦','title'=>'Isolasi Bucket','desc'=>'Setiap bucket yang Anda buat terisolasi dengan aman.'],
                    ['emoji'=>'🔑','title'=>'Jaga Kredensial Anda','desc'=>'Access Key dan Secret Key hanya dapat digunakan oleh Anda.'],
                    ['emoji'=>'☁️','title'=>'MiniStack IaaS','desc'=>'Infrastruktur penyediaan otomatis layaknya AWS.'],
                ] as $tip)
                    <div class="cp-tip">
                        <span style="font-size: 1.4rem;">{{ $tip['emoji'] }}</span>
                        <div>
                            <p style="font-size: 0.84rem; font-weight: 800; color: #496443;">{{ $tip['title'] }}</p>
                            <p style="font-size: 0.74rem; color: #768971; font-weight: 600; margin-top: 0.2rem;">{{ $tip['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Manajemen Bucket IaaS --}}
    <div style="margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="cp-section-title">📦 Daftar Storage Bucket Saya</h3>
            
            {{-- Tombol Pembuatan Bucket (Memanggil Komponen CreateBucket) --}}
            @livewire('bucket.create-bucket')
        </div>
        
        <div class="cp-table-wrap">
            <div style="overflow-x: auto;">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Nama Bucket</th>
                            <th>Access Key</th>
                            <th>Secret Key</th>
                            <th>Dibuat Pada</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buckets as $bucket)
                            <tr>
                                <td style="font-weight: 700;">
                                    <a href="{{ route('bucket.manager', $bucket->id) }}" wire:navigate style="color: #2e7d32; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                                        📂 {{ $bucket->bucket_name }}
                                    </a>
                                </td>
                                <td><code style="background: #f0f4ec; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $bucket->access_key }}</code></td>
                                <td><code style="background: #f0f4ec; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $bucket->secret_key }}</code></td>
                                <td style="color: #809279; font-size: 0.75rem; font-weight: 600;">{{ $bucket->created_at->format('d M Y H:i') }}</td>
                                <td style="text-align: right;">
                                    {{-- Tombol Terminasi (Hapus) --}}
                                    <button 
                                        wire:click="deleteBucket('{{ $bucket->id }}')" 
                                        wire:confirm="Yakin ingin menghapus bucket {{ $bucket->bucket_name }} secara permanen? Semua data di dalamnya akan hilang."
                                        style="background: none; border: none; color: #d32f2f; font-weight: 700; font-size: 0.85rem; cursor: pointer; text-decoration: underline;"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 2.3rem 0.9rem; text-align: center; color: #8ca582; font-weight: 700;">
                                    <span style="font-size: 2rem; display: block; margin-bottom: 0.4rem;">☁️</span>
                                    Anda belum memiliki bucket. Silakan buat bucket baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>