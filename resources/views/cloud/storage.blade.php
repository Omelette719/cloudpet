@component('layouts.app')
    <div class="cp-page">

        <div class="cp-banner" style="margin-bottom:1rem;">
            <div style="position:relative;z-index:1;">
                <p
                    style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">
                    CloudPet Storage</p>
                <h2>Kelola Storage Anda</h2>
                <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">Pilih paket storage sesuai kebutuhan.
                    Semua instance berbagi kuota storage yang sama.</p>
            </div>
        </div>

        {{-- Usage bar --}}
        <div class="cp-card" style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;">
                <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);">Penggunaan Storage Saat Ini</div>
                <div style="font-size:0.88rem;font-weight:800;color:var(--cp-ink);" id="usage-label">— / — GB</div>
            </div>
            <div style="height:10px;background:#e5e7eb;border-radius:999px;">
                <div id="usage-bar"
                    style="height:10px;background:linear-gradient(90deg,var(--cp-primary-start),var(--cp-primary-end));border-radius:999px;width:0%;transition:width 0.5s;">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:6px;">
                <div style="font-size:0.72rem;color:var(--cp-ink-muted);">Paket aktif: <span id="current-plan-label"
                        style="font-weight:700;color:var(--cp-ink);">—</span></div>
                <div style="font-size:0.72rem;color:var(--cp-ink-muted);" id="expires-label"></div>
            </div>
            <div id="storage-full-alert"
                style="display:none;margin-top:10px;padding:10px 14px;background:#fde8e8;border:1px solid #f5c6c6;border-radius:0.75rem;">
                <div style="font-size:0.85rem;font-weight:700;color:#9b2c2c;">⚠️ Storage penuh — semua instance dihentikan
                    otomatis.</div>
                <div style="font-size:0.78rem;color:#c53030;margin-top:2px;">Upgrade paket atau hapus instance untuk
                    membebaskan ruang.</div>
            </div>
        </div>

        {{-- Paket storage --}}
        <h3 class="cp-section-title">Pilih Paket Storage</h3>
        <div class="cp-grid-4" style="margin-top:0;">
            @foreach ([['key' => 'free', 'icon' => '🌱', 'label' => 'Gratis', 'quota' => '30 GB', 'price' => 'Gratis', 'color' => '#6b7280', 'features' => ['30 GB storage', 'Semua tipe instance', 'Support komunitas']], ['key' => 'starter', 'icon' => '🚀', 'label' => 'Starter', 'quota' => '100 GB', 'price' => 'Rp 15.000/bln', 'color' => '#2563eb', 'features' => ['100 GB storage', 'Semua tipe instance', 'Email support']], ['key' => 'pro', 'icon' => '⚡', 'label' => 'Pro', 'quota' => '512 GB', 'price' => 'Rp 50.000/bln', 'color' => '#7c3aed', 'features' => ['512 GB storage', 'Semua tipe instance', 'Priority support']], ['key' => 'business', 'icon' => '🏢', 'label' => 'Business', 'quota' => '2 TB', 'price' => 'Rp 150.000/bln', 'color' => '#b45309', 'features' => ['2 TB storage', 'Semua tipe instance', 'Dedicated support']]] as $plan)
                <div class="cp-card" id="plan-card-{{ $plan['key'] }}"
                    style="display:flex;flex-direction:column;gap:10px;border:2px solid var(--cp-soft-border);transition:border-color 0.2s;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:1.5rem;">{{ $plan['icon'] }}</span>
                        <div>
                            <div style="font-weight:800;font-size:0.95rem;color:var(--cp-ink);">{{ $plan['label'] }}</div>
                            <div style="font-size:0.72rem;color:var(--cp-ink-muted);">{{ $plan['quota'] }}</div>
                        </div>
                    </div>
                    <div style="font-size:1.1rem;font-weight:800;color:{{ $plan['color'] }};">{{ $plan['price'] }}</div>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:4px;">
                        @foreach ($plan['features'] as $f)
                            <li
                                style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--cp-ink-muted);">
                                <span style="color:var(--cp-primary-strong);font-weight:700;">✓</span> {{ $f }}
                            </li>
                        @endforeach
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
                @foreach (['Storage dihitung dari total persistent disk semua instance yang dimiliki.', 'Paket berbayar dipotong dari saldo akun per bulan.', 'Saat storage penuh (100%), semua instance dihentikan otomatis.', 'Downgrade ke paket gratis hanya bisa dilakukan jika penggunaan di bawah 30 GB.', 'Storage dihitung ulang setiap 15 menit secara otomatis.'] as $note)
                    <li style="display:flex;gap:8px;align-items:flex-start;font-size:0.84rem;color:var(--cp-ink-muted);">
                        <span
                            style="width:5px;height:5px;border-radius:50%;background:var(--cp-primary-end);margin-top:7px;flex-shrink:0;display:block;"></span>
                        {{ $note }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast-container"
        style="position:fixed;right:20px;bottom:20px;z-index:10000;display:flex;flex-direction:column;gap:8px;pointer-events:none;">
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let currentPlan = 'free';

        function showToast(msg, ok = true) {
            const c = document.getElementById('toast-container');
            const t = document.createElement('div');
            t.style.cssText =
                `pointer-events:auto;background:${ok ? 'linear-gradient(90deg,#3b5136,#4a6344)' : '#c53030'};color:#fff;padding:10px 16px;border-radius:0.85rem;font-weight:700;font-size:0.875rem;box-shadow:0 6px 18px rgba(0,0,0,0.2);`;
            t.innerText = msg;
            c.appendChild(t);
            setTimeout(() => {
                t.style.transition = 'opacity 300ms';
                t.style.opacity = '0';
                setTimeout(() => t.remove(), 350);
            }, 3500);
        }

        async function loadSummary() {
            const res = await fetch('/cloud/api/billing/summary', {
                credentials: 'same-origin'
            });
            if (!res.ok) return;
            const d = await res.json();

            currentPlan = d.storage_plan || 'free';

            const usedGb = parseFloat(d.storage_used_gb);
            const quotaGb = parseInt(d.storage_quota_gb);
            const pct = Math.min(d.storage_used_pct, 100);

            document.getElementById('usage-label').innerText = usedGb.toFixed(2) + ' / ' + quotaGb + ' GB (' + pct +
                '%)';
            document.getElementById('usage-bar').style.width = pct + '%';
            document.getElementById('usage-bar').style.background = pct > 90 ? '#dc2626' : pct > 70 ? '#d97706' : '';
            document.getElementById('current-plan-label').innerText = d.storage_plan_label;
            document.getElementById('expires-label').innerText = d.storage_plan_expires_at ? 'Berlaku hingga ' + d
                .storage_plan_expires_at : '';
            document.getElementById('storage-full-alert').style.display = pct >= 100 ? 'block' : 'none';

            // Highlight paket aktif
            document.querySelectorAll('[id^="plan-card-"]').forEach(el => {
                el.style.borderColor = 'var(--cp-soft-border)';
                el.style.background = '#fff';
            });
            const activeCard = document.getElementById('plan-card-' + currentPlan);
            if (activeCard) {
                activeCard.style.borderColor = 'var(--cp-primary-start)';
                activeCard.style.background = 'var(--cp-soft)';
            }

            // Update tombol
            document.querySelectorAll('[id^="btn-"]').forEach(btn => {
                const key = btn.id.replace('btn-', '');
                btn.innerText = key === currentPlan ? '✓ Paket Aktif' : 'Pilih Paket';
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        plan
                    }),
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (data.success) {
                    showToast('✓ Berhasil berlangganan paket ' + plan + '!');
                    loadSummary();
                } else {
                    showToast('✗ ' + (data.error || 'Gagal'), false);
                    btn.disabled = false;
                    btn.innerText = 'Pilih Paket';
                }
            } catch (e) {
                showToast('✗ Terjadi kesalahan.', false);
                btn.disabled = false;
                btn.innerText = 'Pilih Paket';
            }
        }

        loadSummary();
    </script>
@endcomponent
