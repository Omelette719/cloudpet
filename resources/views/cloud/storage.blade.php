@component('layouts.app')
    <div class="cp-page">

        <div class="cp-banner" style="margin-bottom:1rem;">
            <div style="position:relative;z-index:1;">
                <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">
                    CloudPet</p>
                <h2>Membership</h2>
                <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">Pilih paket membership sesuai kebutuhan. Tentukan akses compute plan, kapasitas block storage, dan jumlah bucket.</p>
            </div>
        </div>

        {{-- Current membership info --}}
        <div class="cp-card" style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;">Membership Aktif</div>
                    <div style="font-size:1.2rem;font-weight:800;color:var(--cp-ink);" id="current-plan-label">—</div>
                    <div style="font-size:0.72rem;color:var(--cp-ink-muted);margin-top:2px;" id="expires-label"></div>
                </div>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <div style="text-align:center;padding:8px 16px;background:var(--cp-soft);border-radius:0.7rem;">
                        <div style="font-size:0.6rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;">Block Storage</div>
                        <div style="font-size:0.95rem;font-weight:800;color:var(--cp-ink);" id="volume-usage">—</div>
                    </div>
                    <div style="text-align:center;padding:8px 16px;background:var(--cp-soft);border-radius:0.7rem;">
                        <div style="font-size:0.6rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;">Bucket</div>
                        <div style="font-size:0.95rem;font-weight:800;color:var(--cp-ink);" id="bucket-usage">—</div>
                    </div>
                    <div style="text-align:center;padding:8px 16px;background:var(--cp-soft);border-radius:0.7rem;">
                        <div style="font-size:0.6rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.04em;">Database</div>
                        <div style="font-size:0.95rem;font-weight:800;color:var(--cp-ink);" id="db-usage">—</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Plan cards --}}
        <h3 class="cp-section-title">Pilih Paket</h3>
        <div class="cp-grid-4" style="margin-top:0;">
            @foreach ([
                ['key' => 'free',     'icon' => '🌱', 'label' => 'Gratis',   'price' => 'Gratis',          'color' => '#6b7280', 'compute' => 'Max 1 vCPU, 2 GB RAM',    'volume' => '30 GB',    'buckets' => '1 bucket',   'db' => '1 database (Micro)'],
                ['key' => 'starter',  'icon' => '🚀', 'label' => 'Starter',  'price' => 'Rp 15.000/bln',   'color' => '#2563eb', 'compute' => 'Max 2 vCPU, 4 GB RAM',    'volume' => '100 GB',   'buckets' => '3 bucket',   'db' => '3 database (Micro, Small)'],
                ['key' => 'pro',      'icon' => '⚡', 'label' => 'Pro',       'price' => 'Rp 50.000/bln',   'color' => '#7c3aed', 'compute' => 'Max 4 vCPU, 8 GB RAM',    'volume' => '512 GB',   'buckets' => '10 bucket',  'db' => '5 database (Semua plan)'],
                ['key' => 'business', 'icon' => '🏢', 'label' => 'Business', 'price' => 'Rp 150.000/bln',  'color' => '#b45309', 'compute' => 'Max 8 vCPU, 16 GB RAM',   'volume' => '2 TB',     'buckets' => '50 bucket',  'db' => '20 database (Semua plan)'],
            ] as $plan)
                <div class="cp-card" id="plan-card-{{ $plan['key'] }}"
                    style="display:flex;flex-direction:column;gap:10px;border:2px solid var(--cp-soft-border);transition:border-color 0.2s;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:1.5rem;">{{ $plan['icon'] }}</span>
                        <div>
                            <div style="font-weight:800;font-size:0.95rem;color:var(--cp-ink);">{{ $plan['label'] }}</div>
                        </div>
                    </div>
                    <div style="font-size:1.1rem;font-weight:800;color:{{ $plan['color'] }};">{{ $plan['price'] }}</div>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:6px;">
                        <li style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--cp-ink-muted);">
                            <span style="color:var(--cp-primary-strong);font-weight:700;">☁️</span> {{ $plan['compute'] }}
                        </li>
                        <li style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--cp-ink-muted);">
                            <span style="color:var(--cp-primary-strong);font-weight:700;">💽</span> {{ $plan['volume'] }} block storage
                        </li>
                        <li style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--cp-ink-muted);">
                            <span style="color:var(--cp-primary-strong);font-weight:700;">📦</span> {{ $plan['buckets'] }}
                        </li>
                        <li style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--cp-ink-muted);">
                            <span style="color:var(--cp-primary-strong);font-weight:700;">🗄️</span> {{ $plan['db'] }}
                        </li>
                    </ul>
                    <button onclick="subscribePlan('{{ $plan['key'] }}')" id="btn-{{ $plan['key'] }}"
                        style="margin-top:auto;padding:9px;border-radius:0.75rem;border:0;background:{{ $plan['color'] }};color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;">
                        Pilih Paket
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Catatan --}}
        <div class="cp-card" style="margin-top:1rem;">
            <h3 class="cp-section-title" style="margin-top:0;">Catatan</h3>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:6px;">
                @foreach ([
                    'Membership menentukan compute plan yang bisa digunakan, batas total block storage, dan jumlah bucket.',
                    'Paket berbayar dipotong dari saldo akun per bulan.',
                    'Compute Instance ditagih per jam sesuai plan yang dipilih (Rp 500 - Rp 8.000/jam).',
                    'Block Storage ditagih Rp 15/GB per jam, terlepas dari status volume.',
                    'Downgrade ke paket gratis hanya bisa dilakukan jika penggunaan di bawah batas paket gratis.',
                ] as $note)
                    <li style="display:flex;gap:8px;align-items:flex-start;font-size:0.84rem;color:var(--cp-ink-muted);">
                        <span style="width:5px;height:5px;border-radius:50%;background:var(--cp-primary-end);margin-top:7px;flex-shrink:0;display:block;"></span>
                        {{ $note }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div id="toast-container" style="position:fixed;right:20px;bottom:20px;z-index:10000;display:flex;flex-direction:column;gap:8px;pointer-events:none;"></div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let currentPlan = 'free';

        function showToast(msg, ok = true) {
            const c = document.getElementById('toast-container');
            const t = document.createElement('div');
            t.style.cssText = `pointer-events:auto;background:${ok ? 'linear-gradient(90deg,#3b5136,#4a6344)' : '#c53030'};color:#fff;padding:10px 16px;border-radius:0.85rem;font-weight:700;font-size:0.875rem;box-shadow:0 6px 18px rgba(0,0,0,0.2);`;
            t.innerText = msg;
            c.appendChild(t);
            setTimeout(() => { t.style.transition = 'opacity 300ms'; t.style.opacity = '0'; setTimeout(() => t.remove(), 350); }, 3500);
        }

        async function loadSummary() {
            const res = await fetch('/cloud/api/billing/summary', { credentials: 'same-origin' });
            if (!res.ok) return;
            const d = await res.json();

            currentPlan = d.membership_plan || 'free';

            document.getElementById('current-plan-label').innerText = d.membership_label;
            document.getElementById('expires-label').innerText = d.membership_expires_at ? 'Berlaku hingga ' + d.membership_expires_at : '';
            document.getElementById('volume-usage').innerText = d.volume_used_gb + ' / ' + d.volume_limit_gb + ' GB';
            document.getElementById('bucket-usage').innerText = d.bucket_count + ' / ' + d.max_buckets;
            document.getElementById('db-usage').innerText = (d.database_count||0) + ' / ' + (d.max_databases||1);

            // Highlight active plan
            document.querySelectorAll('[id^="plan-card-"]').forEach(el => {
                el.style.borderColor = 'var(--cp-soft-border)';
                el.style.background = '#fff';
            });
            const activeCard = document.getElementById('plan-card-' + currentPlan);
            if (activeCard) {
                activeCard.style.borderColor = 'var(--cp-primary-start)';
                activeCard.style.background = 'var(--cp-soft)';
            }

            // Update buttons
            document.querySelectorAll('[id^="btn-"]').forEach(btn => {
                const key = btn.id.replace('btn-', '');
                btn.innerText = key === currentPlan ? 'Paket Aktif' : 'Pilih Paket';
                btn.disabled = key === currentPlan;
                btn.style.opacity = key === currentPlan ? '0.6' : '1';
            });
        }

        async function subscribePlan(plan) {
            const btn = document.getElementById('btn-' + plan);
            btn.disabled = true;
            btn.innerText = 'Memproses...';

            try {
                const res = await fetch('/cloud/api/billing/storage-subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ plan }),
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Berhasil berlangganan paket ' + (data.membership_label || plan) + '!');
                    loadSummary();
                } else {
                    showToast(data.error || 'Gagal', false);
                    btn.disabled = false;
                    btn.innerText = 'Pilih Paket';
                }
            } catch (e) {
                showToast('Terjadi kesalahan.', false);
                btn.disabled = false;
                btn.innerText = 'Pilih Paket';
            }
        }

        loadSummary();
    </script>
@endcomponent
