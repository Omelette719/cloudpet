<div>
    {{-- Welcome banner --}}
    <div class="cp-banner">
        <div style="position:absolute;right:1rem;top:-0.8rem;font-size:4.8rem;opacity:0.22;">{{ $user->animal_avatar }}</div>
        <div style="position:absolute;right:6rem;bottom:0.1rem;font-size:2.5rem;opacity:0.15;"></div>
        <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
            <div>
                <p>Selamat datang kembali!</p>
                <h2>{{ $user->name }} {{ $user->animal_avatar }}</h2>
                <p>Member sejak {{ $user->created_at->translatedFormat('F Y') }}</p>
            </div>
            <span class="cp-badge" style="background:rgba(255,255,255,0.2);color:#fff;">{{ $user->membershipLabel() }}</span>
        </div>
    </div>

    <div style="margin-top:1rem;">
        <h3 class="cp-section-title">Ringkasan Akun</h3>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:1rem;">

            {{-- Compute --}}
            <div class="cp-stat">
                <span style="font-size:1.8rem;"></span>
                <div>
                    <p class="cp-stat-label">Layanan Aktif</p>
                    <p class="cp-stat-value" id="stat-instances">—</p>
                </div>
            </div>

            {{-- Block Storage --}}
            <div class="cp-stat">
                <span style="font-size:1.8rem;"></span>
                <div style="width:100%;">
                    <p class="cp-stat-label">Block Storage</p>
                    <p class="cp-stat-value">{{ $user->volumeUsedGb() }} / {{ $user->volumeLimitGb() }} GB</p>
                    @php $volPct = $user->volumeUsedPercent(); @endphp
                    <div style="height:4px;background:#e5e7eb;border-radius:999px;margin-top:6px;">
                        <div style="height:4px;background:{{ $volPct > 90 ? '#dc2626' : 'linear-gradient(90deg,var(--cp-primary-start),var(--cp-primary-end))' }};border-radius:999px;width:{{ min($volPct, 100) }}%;transition:width .5s;"></div>
                    </div>
                </div>
            </div>

            {{-- Bucket --}}
            <div class="cp-stat">
                <span style="font-size:1.8rem;"></span>
                <div>
                    <p class="cp-stat-label">Bucket</p>
                    <p class="cp-stat-value">{{ $bucketCount }} / {{ $user->maxBuckets() }}</p>
                </div>
            </div>

            {{-- Database --}}
            <div class="cp-stat">
                <span style="font-size:1.8rem;"></span>
                <div>
                    <p class="cp-stat-label">Database</p>
                    <p class="cp-stat-value">{{ $user->managedDatabases()->whereNotIn('status', ['TERMINATED'])->count() }} / {{ $user->maxDatabases() }}</p>
                </div>
            </div>

            {{-- Saldo --}}
            <div class="cp-stat" style="cursor:pointer;" onclick="document.getElementById('billing-section').scrollIntoView({behavior:'smooth'})">
                <span style="font-size:1.8rem;"></span>
                <div>
                    <p class="cp-stat-label">Saldo</p>
                    <p class="cp-stat-value" id="stat-balance">—</p>
                </div>
            </div>

        </div>
    </div>

    {{-- Membership card --}}
    <div style="margin-top:1rem;">
        <h3 class="cp-section-title">Membership</h3>
        <div class="cp-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;border-radius:0.85rem;background:var(--cp-soft);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                        @php
                            $tierIcons = ['free' => '🌱', 'starter' => '🚀', 'pro' => '⚡', 'business' => '🏢'];
                            $currentTier = $user->storage_plan ?? 'free';
                        @endphp
                        {{ $tierIcons[$currentTier] ?? '🌱' }}
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:1.05rem;color:var(--cp-ink);">{{ $user->membershipLabel() }}</div>
                        <div style="font-size:0.75rem;color:var(--cp-ink-muted);font-weight:600;margin-top:2px;">
                            @if($user->storage_plan_expires_at)
                                Berlaku hingga {{ $user->storage_plan_expires_at->translatedFormat('d F Y') }}
                            @else
                                Paket gratis aktif
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('cloud.storage') }}" wire:navigate class="cp-btn" style="width:auto;padding:9px 18px;font-size:0.88rem;border-radius:0.75rem;text-decoration:none;text-align:center;">
                    Kelola Membership
                </a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:14px;padding-top:14px;border-top:1px solid var(--cp-soft-border);">
                <div style="text-align:center;padding:10px;background:var(--cp-soft);border-radius:0.7rem;">
                    <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Compute Max</div>
                    <div style="font-size:0.82rem;font-weight:800;color:var(--cp-ink);">{{ $user->maxVcpu() }} vCPU · {{ $user->maxRamMb() / 1024 }} GB</div>
                </div>
                <div style="text-align:center;padding:10px;background:var(--cp-soft);border-radius:0.7rem;">
                    <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Block Storage</div>
                    <div style="font-size:0.82rem;font-weight:800;color:var(--cp-ink);">{{ $user->volumeUsedGb() }} / {{ $user->volumeLimitGb() }} GB</div>
                </div>
                <div style="text-align:center;padding:10px;background:var(--cp-soft);border-radius:0.7rem;">
                    <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Bucket</div>
                    <div style="font-size:0.82rem;font-weight:800;color:var(--cp-ink);">{{ $bucketCount }} / {{ $user->maxBuckets() }}</div>
                </div>
                <div style="text-align:center;padding:10px;background:var(--cp-soft);border-radius:0.7rem;">
                    <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Database</div>
                    <div style="font-size:0.82rem;font-weight:800;color:var(--cp-ink);">{{ $user->managedDatabases()->whereNotIn('status', ['TERMINATED'])->count() }} / {{ $user->maxDatabases() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Billing section --}}
    <div id="billing-section" style="margin-top:1rem;">
        <h3 class="cp-section-title">Saldo & Billing</h3>
        <div class="cp-grid-2">

            {{-- Saldo & top-up --}}
            <div class="cp-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;">Saldo Tersedia</div>
                        <div style="font-size:1.8rem;font-weight:800;color:var(--cp-ink);" id="balance-display">Rp —</div>
                        <div id="account-status-badge" style="margin-top:4px;"></div>
                    </div>
                </div>

                <div style="border-top:1px solid var(--cp-soft-border);padding-top:14px;">
                    <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);margin-bottom:8px;">Top-up Saldo</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;" id="topup-presets">
                        @foreach ([10000, 25000, 50000, 100000, 250000, 500000] as $amt)
                            <button onclick="setTopupAmount({{ $amt }})" style="padding:6px 12px;border-radius:0.65rem;border:1px solid var(--cp-soft-border);background:var(--cp-soft);font-size:0.78rem;font-weight:700;color:var(--cp-ink);cursor:pointer;">
                                Rp {{ number_format($amt, 0, ',', '.') }}
                            </button>
                        @endforeach
                    </div>
                    <div style="display:flex;gap:8px;">
                        <input id="topup-amount" type="number" min="1000" placeholder="Jumlah (Rp)" style="flex:1;padding:9px 12px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);font-size:0.88rem;color:var(--cp-ink);">
                        <button onclick="doTopUp()" id="topup-btn" class="cp-btn" style="width:auto;padding:9px 18px;font-size:0.88rem;border-radius:0.75rem;">Top-up</button>
                    </div>
                    <div id="topup-msg" style="margin-top:8px;font-size:0.78rem;display:none;"></div>
                </div>

                <div style="margin-top:14px;padding:10px;background:var(--cp-soft);border-radius:0.75rem;">
                    <div style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);margin-bottom:4px;">Estimasi biaya aktif</div>
                    <div style="font-size:0.85rem;font-weight:700;color:var(--cp-ink);" id="cost-estimate">—</div>
                </div>
            </div>

            {{-- Riwayat transaksi --}}
            <div class="cp-card">
                <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);margin-bottom:12px;">Riwayat Transaksi</div>
                <div id="tx-history" style="display:grid;gap:6px;max-height:260px;overflow-y:auto;">
                    <div style="text-align:center;color:var(--cp-ink-muted);font-size:0.82rem;padding:1rem;">Memuat...</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Info akun --}}
    <div class="cp-grid-2" style="margin-top:1rem;">
        <div>
            <h3 class="cp-section-title">Info Akun</h3>
            <div class="cp-card" style="padding:0;overflow:hidden;">
                @foreach ([['label' => 'Avatar', 'value' => $user->animal_avatar . ' ' . $user->name], ['label' => 'Email', 'value' => $user->email], ['label' => 'Bergabung', 'value' => $user->created_at->translatedFormat('d F Y')]] as $row)
                    <div style="display:flex;align-items:center;gap:0.6rem;padding:0.85rem 1rem;border-bottom:1px solid #eef5e7;">
                        <span style="width:6rem;flex-shrink:0;font-size:0.68rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#89a081;">{{ $row['label'] }}</span>
                        <span style="font-size:0.84rem;font-weight:700;color:#455b41;">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div>
            <h3 class="cp-section-title">Info & Tips</h3>
            <div class="cp-card" style="display:grid;gap:0.65rem;">
                @foreach ([
                    ['emoji' => '💰', 'title' => 'Pay as you go', 'desc' => 'Saldo dipotong per jam sesuai instance Compute yang berjalan. Stop instance untuk berhenti ditagih.'],
                    ['emoji' => '💽', 'title' => 'Block Storage', 'desc' => 'Volume ditagihkan Rp 15/GB per jam, bahkan saat Compute Instance dimatikan. Hapus jika tidak digunakan.'],
                    ['emoji' => '⚡', 'title' => 'Auto-stop', 'desc' => 'Instance dihentikan otomatis saat saldo habis. Top-up untuk menghidupkan kembali.'],
                    ['emoji' => '🎫', 'title' => 'Membership', 'desc' => 'Upgrade membership untuk menggunakan plan compute lebih besar dan menambah kapasitas block storage & bucket.'],
                ] as $tip)
                    <div class="cp-tip">
                        <span style="font-size:1.4rem;">{{ $tip['emoji'] }}</span>
                        <div>
                            <p style="font-size:0.84rem;font-weight:800;color:#496443;">{{ $tip['title'] }}</p>
                            <p style="font-size:0.74rem;color:#768971;font-weight:600;margin-top:0.2rem;">{{ $tip['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:navigated', () => {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const volumeHourlyCost = {{ \App\Models\BlockVolume::where('user_id', auth()->id())->whereIn('status', ['AVAILABLE', 'ATTACHED'])->sum('size_gb') * 15 }};

    async function loadSummary() {
        const res = await fetch('/cloud/api/billing/summary', { credentials: 'same-origin' });
        if (!res.ok) return;
        const d = await res.json();

        document.getElementById('balance-display').innerText = 'Rp ' + parseFloat(d.balance).toLocaleString('id-ID');
        document.getElementById('stat-balance').innerText = 'Rp ' + parseFloat(d.balance).toLocaleString('id-ID');

        const statusBadge = document.getElementById('account-status-badge');
        if (d.account_status === 'SUSPENDED') {
            statusBadge.innerHTML = '<span style="font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#fde8e8;color:#9b2c2c;">Akun Suspended — Top-up untuk mengaktifkan</span>';
        } else {
            statusBadge.innerHTML = '<span style="font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#eaf4dd;color:#3b5136;">Aktif</span>';
        }
    }

    async function loadInstances() {
        const res = await fetch('/cloud/api/instances', { credentials: 'same-origin' });
        if (!res.ok) return;
        const items = await res.json();
        const running = items.filter(i => i.status === 'RUNNING');

        document.getElementById('stat-instances').innerText = running.length + ' instance';

        const computeCost = running.reduce((s, i) => s + parseFloat(i.metadata?.price_per_hour || 0), 0);
        const totalCost = computeCost + volumeHourlyCost;

        let parts = [];
        if (running.length > 0) parts.push(running.length + ' instance');
        if (volumeHourlyCost > 0) parts.push('block storage');

        document.getElementById('cost-estimate').innerText = totalCost > 0 ?
            'Rp ' + totalCost.toLocaleString('id-ID') + '/jam (' + parts.join(' + ') + ')' :
            'Tidak ada resource berbayar yang aktif';
    }

    async function loadHistory() {
        const res = await fetch('/cloud/api/billing/history', { credentials: 'same-origin' });
        if (!res.ok) return;
        const txs = await res.json();
        const wrap = document.getElementById('tx-history');
        if (!txs.length) {
            wrap.innerHTML = '<div style="text-align:center;color:var(--cp-ink-muted);font-size:0.82rem;padding:1rem;">Belum ada transaksi.</div>';
            return;
        }
        wrap.innerHTML = txs.map(tx => {
            const isDebit = parseFloat(tx.amount) < 0;
            const color = isDebit ? '#9b2c2c' : '#3b5136';
            const bg = isDebit ? '#fde8e8' : '#eaf4dd';
            const sign = isDebit ? '−' : '+';
            const date = new Date(tx.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
            return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:var(--cp-soft);border-radius:0.65rem;gap:8px;">
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);">${tx.description || tx.transaction_type}</div>
                <div style="font-size:0.68rem;color:var(--cp-ink-muted);">${date}</div>
            </div>
            <span style="font-size:0.82rem;font-weight:800;padding:2px 8px;border-radius:999px;background:${bg};color:${color};white-space:nowrap;">
                ${sign} Rp ${Math.abs(parseFloat(tx.amount)).toLocaleString('id-ID')}
            </span>
        </div>`;
        }).join('');
    }

    window.setTopupAmount = function(amt) { document.getElementById('topup-amount').value = amt; }

    window.doTopUp = async function() {
        const amount = parseFloat(document.getElementById('topup-amount').value);
        const msg = document.getElementById('topup-msg');
        const btn = document.getElementById('topup-btn');
        if (!amount || amount < 1000) {
            msg.style.display = 'block'; msg.style.color = '#9b2c2c'; msg.innerText = 'Minimal top-up Rp 1.000'; return;
        }
        btn.disabled = true; btn.innerText = 'Memproses...';
        try {
            const res = await fetch('/cloud/api/billing/topup', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ amount }), credentials: 'same-origin'
            });
            const data = await res.json();
            if (data.success) {
                msg.style.color = '#3b5136';
                msg.innerText = 'Top-up berhasil! Saldo: Rp ' + parseFloat(data.balance).toLocaleString('id-ID');
                document.getElementById('topup-amount').value = '';
                loadSummary(); loadHistory();
            } else {
                msg.style.color = '#9b2c2c'; msg.innerText = (data.error || 'Gagal');
            }
        } catch (e) {
            msg.style.color = '#9b2c2c'; msg.innerText = 'Terjadi kesalahan.';
        }
        msg.style.display = 'block'; btn.disabled = false; btn.innerText = 'Top-up';
    }

    loadSummary();
    loadInstances();
    loadHistory();
    setInterval(loadSummary, 30000);
    setInterval(loadInstances, 10000);
});
</script>
