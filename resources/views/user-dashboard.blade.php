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

    {{-- Stats --}}
    <div>
        <h3 class="cp-section-title">📊 Ringkasan Akun</h3>
        <div class="cp-grid-4">
            @foreach([
                ['icon'=>'☁️','label'=>'Layanan Aktif',  'value'=>'0'],
                ['icon'=>'💾','label'=>'Storage Terpakai','value'=>'0 GB'],
                ['icon'=>'📦','label'=>'Bucket Aktif',   'value'=>'0'],
                ['icon'=>'🔑','label'=>'Access Key',     'value'=>'-'],
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
                    ['emoji'=>'🚀','title'=>'Storage Segera Hadir!','desc'=>'Sewa bucket terisolasi di MiniStack.'],
                    ['emoji'=>'🔑','title'=>'Access Key & Secret Key','desc'=>'Ditampilkan aman di dashboard setelah dibuat.'],
                    ['emoji'=>'☁️','title'=>'Simulasi AWS via MiniStack','desc'=>'Platform ini mensimulasikan layanan IaaS seperti AWS.'],
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

</div>