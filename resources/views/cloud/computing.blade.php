@component('layouts.app')
<div class="cp-page">

    {{-- BANNER --}}
    <div class="cp-banner" style="margin-bottom:1rem;">
        <div style="position:relative; z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">Cloud Computing</p>
            <h2>Kelola Instance Virtual</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">
                Sewa VM dengan resource yang bisa dikustomisasi. SSH langsung setelah instance berjalan.
            </p>
        </div>
    </div>

    <div style="display:grid;gap:1rem;">

        {{-- BUAT INSTANCE --}}
        <div class="cp-card">
            <h3 class="cp-section-title" style="margin-top:0;">Buat Instance Baru</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start;">

                {{-- Kiri: Plan & OS --}}
                <div>
                    {{-- Pilih Plan --}}
                    <div style="margin-bottom:14px;">
                        <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Plan</label>
                        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px;" id="plan-selector">
                            @foreach(['nano','micro','small','medium','large'] as $p)
                            <button class="plan-btn" data-plan="{{ $p }}"
                                style="padding:10px 6px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);background:#fff;cursor:pointer;text-align:center;transition:all 0.15s;">
                                <div style="font-size:0.8rem;font-weight:800;color:var(--cp-ink);text-transform:capitalize;">{{ $p }}</div>
                                <div style="font-size:0.65rem;color:var(--cp-ink-muted);margin-top:2px;" id="plan-spec-{{ $p }}"></div>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pilih OS --}}
                    <div style="margin-bottom:14px;">
                        <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Sistem Operasi</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;" id="os-selector">
                            @foreach([
                                ['key'=>'ubuntu-22.04','label'=>'Ubuntu 22.04','icon'=>'🐧'],
                                ['key'=>'ubuntu-20.04','label'=>'Ubuntu 20.04','icon'=>'🐧'],
                                ['key'=>'debian-12',   'label'=>'Debian 12',   'icon'=>'🌀'],
                                ['key'=>'alpine',      'label'=>'Alpine Linux', 'icon'=>'⛰️'],
                            ] as $os)
                            <button class="os-btn" data-os="{{ $os['key'] }}"
                                style="padding:9px 12px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);background:#fff;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all 0.15s;">
                                <span style="font-size:1.1rem;">{{ $os['icon'] }}</span>
                                <span style="font-size:0.8rem;font-weight:700;color:var(--cp-ink);">{{ $os['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Ringkasan & Buat --}}
                    <div style="background:var(--cp-soft);border:1px solid var(--cp-soft-border);border-radius:0.9rem;padding:14px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px;" id="spec-summary">
                            <div style="text-align:center;">
                                <div style="font-size:0.65rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;">vCPU</div>
                                <div style="font-size:1.1rem;font-weight:800;color:var(--cp-ink);" id="sum-cpu">—</div>
                            </div>
                            <div style="text-align:center;border-left:1px solid var(--cp-soft-border);border-right:1px solid var(--cp-soft-border);">
                                <div style="font-size:0.65rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;">RAM</div>
                                <div style="font-size:1.1rem;font-weight:800;color:var(--cp-ink);" id="sum-ram">—</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:0.65rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;">Disk</div>
                                <div style="font-size:1.1rem;font-weight:800;color:var(--cp-ink);" id="sum-disk">—</div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-size:0.68rem;color:var(--cp-ink-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Harga</div>
                                <div style="font-size:1.2rem;font-weight:800;color:var(--cp-primary-strong);" id="sum-price">—</div>
                            </div>
                            <button id="cp-create-btn" class="cp-btn" style="width:auto;padding:12px 24px;font-size:0.92rem;border-radius:0.85rem;" disabled>
                                Buat Instance
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Kanan: info cara pakai / terminal proses pembuatan --}}
                <div style="background:var(--cp-soft);border:1px solid var(--cp-soft-border);border-radius:0.95rem;padding:16px;position:relative;min-height:260px;">

                    {{-- Mode default: panduan SSH --}}
                    <div id="ssh-guide-panel">
                        <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--cp-ink-muted);margin-bottom:12px;">Cara Koneksi SSH</div>
                        <ol style="list-style:none;padding:0;margin:0;display:grid;gap:10px;">
                            @foreach([
                                ['step'=>'1','text'=>'Buat instance dan tunggu status menjadi RUNNING.'],
                                ['step'=>'2','text'=>'Klik tombol "Detail" pada card instance untuk melihat kredensial SSH.'],
                                ['step'=>'3','text'=>'Buka terminal dan jalankan perintah SSH yang tersedia.'],
                                ['step'=>'4','text'=>'Masukkan password yang tertera. Selesai — VM siap dipakai.'],
                            ] as $s)
                            <li style="display:flex;gap:10px;align-items:flex-start;">
                                <span style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--cp-primary-start),var(--cp-primary-end));color:#fff;font-size:0.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $s['step'] }}</span>
                                <span style="font-size:0.83rem;color:var(--cp-ink-muted);line-height:1.5;padding-top:2px;">{{ $s['text'] }}</span>
                            </li>
                            @endforeach
                        </ol>
                        <div class="cp-tip" style="margin-top:14px;">
                            <span>💡</span>
                            <span style="font-size:0.8rem;color:var(--cp-ink-muted);">Resource (CPU, RAM) di-enforce oleh Docker — kamu tidak bisa melebihi limit plan yang dipilih.</span>
                        </div>
                    </div>

                    {{-- Mode aktif: terminal proses pembuatan instance --}}
                    <div id="provision-panel" style="display:none;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--cp-ink-muted);">Proses Pembuatan Instance</div>
                            <span id="provision-status-chip" style="font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#fef3c7;color:#92400e;">PROVISIONING</span>
                        </div>
                        <pre id="provision-terminal" style="background:#1b1f17;color:#cdebb0;font-family:monospace;font-size:0.74rem;line-height:1.55;border-radius:0.7rem;padding:12px 14px;margin:0;height:190px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
                        <div id="provision-result" style="margin-top:10px;display:none;"></div>
                    </div>

                </div>
            </div>
        </div>

        {{-- INSTANCE LIST --}}
        <div class="cp-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h3 class="cp-section-title" style="margin:0;">Instance Anda</h3>
                <div style="display:flex;gap:6px;">
                    <button id="tab-active"
                        style="padding:6px 14px;border-radius:999px;font-size:0.8rem;font-weight:700;cursor:pointer;border:1px solid transparent;background:linear-gradient(90deg,var(--cp-primary-start),var(--cp-primary-end));color:#fff;">
                        Active
                    </button>
                    <button id="tab-archived"
                        style="padding:6px 14px;border-radius:999px;font-size:0.8rem;font-weight:700;cursor:pointer;border:1px solid var(--cp-soft-border);background:#fff;color:var(--cp-ink-muted);">
                        Archived
                    </button>
                </div>
            </div>
            <div id="cp-instances-wrap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:0.9rem;"></div>
        </div>

    </div>{{-- /grid --}}
</div>{{-- /cp-page --}}

{{-- MODAL SSH DETAIL --}}
<div id="ssh-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:9999;">
    <div style="position:absolute;inset:0;background:rgba(34,48,31,0.55);" id="ssh-modal-bg"></div>
    <div style="position:relative;background:#fff;border-radius:1.25rem;padding:24px;width:480px;max-width:94%;box-shadow:0 16px 44px rgba(34,48,31,0.22);border:1px solid var(--cp-soft-border);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-weight:800;font-size:1rem;color:var(--cp-ink);" id="ssh-modal-title">Koneksi SSH</div>
            <button id="ssh-modal-close" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--cp-ink-muted);">✕</button>
        </div>

        <div id="ssh-modal-body" style="display:grid;gap:12px;">
            {{-- diisi oleh JS --}}
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI --}}
<div id="cp-confirm-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:9999;">
    <div style="position:absolute;inset:0;background:rgba(34,48,31,0.55);"></div>
    <div style="position:relative;background:#fff;border-radius:1.25rem;padding:24px;width:380px;max-width:92%;box-shadow:0 16px 44px rgba(34,48,31,0.22);border:1px solid var(--cp-soft-border);">
        <div id="cp-confirm-message" style="font-size:0.95rem;font-weight:800;color:var(--cp-ink);margin-bottom:8px;"></div>
        <div id="cp-confirm-sub" style="font-size:0.85rem;color:var(--cp-ink-muted);margin-bottom:20px;">Tindakan ini tidak dapat dibatalkan setelah dikonfirmasi.</div>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button id="cp-confirm-cancel" style="padding:9px 18px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);background:#fff;color:var(--cp-ink);font-size:0.875rem;font-weight:700;cursor:pointer;">Batal</button>
            <button id="cp-confirm-ok" style="padding:9px 18px;border-radius:0.75rem;border:0;background:#c53030;color:#fff;font-size:0.875rem;font-weight:700;cursor:pointer;">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

{{-- TOAST --}}
<div id="cp-toast-container" style="position:fixed;right:20px;bottom:20px;z-index:10000;display:flex;flex-direction:column;gap:8px;pointer-events:none;"></div>

<script>
(function () {
    // ── Config ──────────────────────────────────────────────────────────────
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const API = {
        plans:  () => fetch('/cloud/api/plans').then(r => r.json()),
        list:   (archived) => fetch('/cloud/api/instances' + (archived ? '?archived=1' : ''), { credentials: 'same-origin' }).then(r => r.ok ? r.json() : []).catch(() => []),
        create: (plan, os) => fetch('{{ route('cloud.api.instances.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ plan, os }), credentials: 'same-origin' }).then(r => r.json()),
        action: (id, action) => fetch(`/cloud/api/instances/${id}/action`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ action }), credentials: 'same-origin' }).then(r => r.json()),
        stats:  (id) => fetch(`/cloud/api/instances/${id}/stats`, { credentials: 'same-origin' }).then(r => r.json()),
        log:    (id) => fetch(`/cloud/api/instances/${id}/log`, { credentials: 'same-origin' }).then(r => r.json()),
    };

    const STATUS_CHIP = {
        RUNNING:      'background:#eaf4dd;color:#3b5136;',
        STOPPED:      'background:#f0f5ea;color:#61765d;',
        TERMINATED:   'background:#fde8e8;color:#9b2c2c;',
        PROVISIONING: 'background:#fef3c7;color:#92400e;',
        FAILED:       'background:#fde8e8;color:#7a1111;',
    };

    // ── State ───────────────────────────────────────────────────────────────
    let plans        = {};
    let selectedPlan = null;
    let selectedOs   = 'ubuntu-22.04';
    let currentTab   = 'active';

    // ── Load plans dari API ──────────────────────────────────────────────────
    API.plans().then(data => {
        plans = data.plans || {};

        // Isi spec tiap plan button
        Object.entries(plans).forEach(([key, p]) => {
            const el = document.getElementById('plan-spec-' + key);
            if (el) el.innerText = p.cpu + ' CPU · ' + (p.memory >= 1024 ? (p.memory/1024)+'GB' : p.memory+'MB');
        });
    });

    // ── Plan selector ────────────────────────────────────────────────────────
    document.querySelectorAll('.plan-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            selectedPlan = btn.dataset.plan;
            document.querySelectorAll('.plan-btn').forEach(b => {
                b.style.background   = '#fff';
                b.style.borderColor  = 'var(--cp-soft-border)';
                b.style.color        = 'var(--cp-ink)';
            });
            btn.style.background  = 'linear-gradient(135deg,var(--cp-primary-start),var(--cp-primary-end))';
            btn.style.borderColor = 'var(--cp-primary-end)';
            btn.style.color       = '#fff';
            btn.querySelectorAll('div').forEach(d => d.style.color = '#fff');
            updateSummary();
        });
    });

    // ── OS selector ─────────────────────────────────────────────────────────
    document.querySelectorAll('.os-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            selectedOs = btn.dataset.os;
            document.querySelectorAll('.os-btn').forEach(b => {
                b.style.background  = '#fff';
                b.style.borderColor = 'var(--cp-soft-border)';
            });
            btn.style.background  = 'var(--cp-soft)';
            btn.style.borderColor = 'var(--cp-primary-start)';
        });
    });

    // Default OS selected style
    document.querySelector('.os-btn[data-os="ubuntu-22.04"]').click();

    function updateSummary() {
        if (!selectedPlan || !plans[selectedPlan]) return;
        const p = plans[selectedPlan];
        document.getElementById('sum-cpu').innerText  = p.cpu + ' vCPU';
        document.getElementById('sum-ram').innerText  = p.memory >= 1024 ? (p.memory/1024) + ' GB' : p.memory + ' MB';
        document.getElementById('sum-disk').innerText = p.disk + ' GB';
        document.getElementById('sum-price').innerText = 'Rp ' + p.price.toLocaleString('id-ID') + '/jam';
        document.getElementById('cp-create-btn').disabled = false;
    }

    // ── Buat instance ────────────────────────────────────────────────────────
    document.getElementById('cp-create-btn').addEventListener('click', async () => {
        if (!selectedPlan) return;
        const btn = document.getElementById('cp-create-btn');
        btn.disabled   = true;
        btn.innerText  = 'Membuat...';

        let instance;
        try {
            instance = await API.create(selectedPlan, selectedOs);
        } catch (e) {
            instance = null;
        }

        btn.innerText  = 'Buat Instance';
        btn.disabled   = false;

        if (!instance || !instance.id) {
            showToast('Gagal membuat instance. Coba lagi.');
            return;
        }

        showToast('Instance sedang diprovisioning...');
        await refresh();
        watchProvisioning(instance.id);
    });

    // ── Terminal proses pembuatan instance ───────────────────────────────────
    function watchProvisioning(instanceId) {
        const guidePanel  = document.getElementById('ssh-guide-panel');
        const provPanel   = document.getElementById('provision-panel');
        const terminalEl  = document.getElementById('provision-terminal');
        const chipEl       = document.getElementById('provision-status-chip');
        const resultEl     = document.getElementById('provision-result');

        guidePanel.style.display = 'none';
        provPanel.style.display  = 'block';
        terminalEl.innerText      = '';
        resultEl.style.display    = 'none';
        chipEl.innerText          = 'PROVISIONING';
        chipEl.style.cssText      = 'font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#fef3c7;color:#92400e;';

        const poll = setInterval(async () => {
            let data;
            try {
                data = await API.log(instanceId);
            } catch (e) {
                return; // coba lagi di tick berikutnya
            }

            terminalEl.innerText = data.log || '';
            terminalEl.scrollTop = terminalEl.scrollHeight;

            if (data.status === 'RUNNING') {
                clearInterval(poll);
                chipEl.innerText     = 'RUNNING';
                chipEl.style.cssText = 'font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#eaf4dd;color:#3b5136;';
                showToast('Instance berhasil dibuat & berjalan 🎉');
                refresh();
                setTimeout(() => {
                    provPanel.style.display  = 'none';
                    guidePanel.style.display = 'block';
                }, 2000);
            } else if (data.status === 'FAILED') {
                clearInterval(poll);
                chipEl.innerText     = 'FAILED';
                chipEl.style.cssText = 'font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#fde8e8;color:#9b2c2c;';
                resultEl.style.display = 'block';
                resultEl.innerHTML = `
                    <div class="cp-tip" style="background:#fde8e8;border-color:#f5c6c6;">
                        <span>⚠️</span>
                        <span style="font-size:0.8rem;color:#9b2c2c;">Pembuatan instance gagal. Cek log di atas untuk detailnya, lalu coba lagi atau hubungi admin.</span>
                    </div>`;
                showToast('Pembuatan instance gagal ❌');
                refresh();
            }
        }, 1200);
    }

    // ── Tabs ─────────────────────────────────────────────────────────────────
    const sActive   = 'padding:6px 14px;border-radius:999px;font-size:0.8rem;font-weight:700;cursor:pointer;border:1px solid transparent;background:linear-gradient(90deg,var(--cp-primary-start),var(--cp-primary-end));color:#fff;';
    const sInactive = 'padding:6px 14px;border-radius:999px;font-size:0.8rem;font-weight:700;cursor:pointer;border:1px solid var(--cp-soft-border);background:#fff;color:var(--cp-ink-muted);';
    const tabActive   = document.getElementById('tab-active');
    const tabArchived = document.getElementById('tab-archived');
    function setTab(t) {
        currentTab = t;
        tabActive.style.cssText   = t === 'active'   ? sActive : sInactive;
        tabArchived.style.cssText = t === 'archived' ? sActive : sInactive;
        refresh();
    }
    tabActive.addEventListener('click',   () => setTab('active'));
    tabArchived.addEventListener('click', () => setTab('archived'));

    // ── Render instances ─────────────────────────────────────────────────────
    function renderItems(items) {
        const wrap = document.getElementById('cp-instances-wrap');
        wrap.innerHTML = '';

        if (!items || items.length === 0) {
            wrap.innerHTML = '<div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--cp-ink-muted);font-size:0.88rem;">Belum ada instance.</div>';
            return;
        }

        items.forEach(it => {
            const meta      = it.metadata || {};
            const resources = meta.resources || {};
            const status    = (it.status || '').toUpperCase();
            const chipStyle = STATUS_CHIP[status] || STATUS_CHIP.STOPPED;

            const card = document.createElement('div');
            card.style.cssText = 'background:#fff;border:1px solid var(--cp-soft-border);border-radius:1rem;padding:14px;display:flex;flex-direction:column;gap:10px;';

            // Header: nama + status
            card.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="font-weight:800;font-size:0.95rem;color:var(--cp-ink);">${it.name}</div>
                        <div style="font-size:0.72rem;color:var(--cp-ink-muted);margin-top:2px;font-weight:600;">
                            ${meta.plan_label || it.plan || ''} · ${meta.os_label || it.os || ''}
                        </div>
                    </div>
                    <span style="${chipStyle}font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:999px;">${it.status}</span>
                </div>

                <!-- Resource chips -->
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    ${resources.cpu    ? `<span style="font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--cp-soft);color:var(--cp-ink-muted);">⚡ ${resources.cpu} vCPU</span>` : ''}
                    ${resources.memory ? `<span style="font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--cp-soft);color:var(--cp-ink-muted);">🧠 ${resources.memory >= 1024 ? resources.memory/1024+'GB' : resources.memory+'MB'}</span>` : ''}
                    ${resources.disk   ? `<span style="font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--cp-soft);color:var(--cp-ink-muted);">💾 ${resources.disk}GB</span>` : ''}
                </div>

                <!-- SSH quick info (hanya kalau RUNNING) -->
                ${status === 'RUNNING' && meta.ssh_port ? `
                <div style="background:var(--cp-soft);border-radius:0.65rem;padding:8px 10px;font-size:0.78rem;color:var(--cp-ink-muted);">
                    <div style="font-family:monospace;font-size:0.75rem;color:var(--cp-ink);word-break:break-all;">
                        ssh root@&lt;server-ip&gt; -p ${meta.ssh_port}
                    </div>
                </div>` : ''}

                <!-- Actions -->
                <div style="display:flex;gap:6px;flex-wrap:wrap;padding-top:8px;border-top:1px solid var(--cp-soft-border);" id="actions-${it.id}"></div>
            `;

            // Render action buttons
            const actEl = card.querySelector(`#actions-${it.id}`);
            const btn   = (label, style, onclick) => {
                const b = document.createElement('button');
                b.innerText        = label;
                b.style.cssText    = `padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid;${style}`;
                b.addEventListener('click', onclick);
                return b;
            };

            if (currentTab === 'active') {
                // Detail SSH
                if (meta.ssh_port) {
                    actEl.appendChild(btn('🔑 Detail SSH', 'background:#e8f2fb;color:#2b5fa0;border-color:#b8d4f0;', () => showSshModal(it)));
                }

                if (status === 'RUNNING') {
                    actEl.appendChild(btn('Stop', 'background:#f0f5ea;color:#61765d;border-color:var(--cp-soft-border);', () => {
                        showConfirm('Stop instance ' + it.name + '?', 'Instance akan dihentikan. Bisa di-start lagi nanti.', () => API.action(it.id, 'stop').then(refresh));
                    }));
                    actEl.appendChild(btn('Restart', 'background:#fef3c7;color:#92400e;border-color:#fde68a;', () => {
                        API.action(it.id, 'restart').then(() => { showToast('Instance di-restart'); refresh(); });
                    }));
                }

                if (status === 'STOPPED') {
                    actEl.appendChild(btn('▶ Start', 'background:#eaf4dd;color:#3b5136;border-color:#c6e0a8;', () => {
                        API.action(it.id, 'start').then(() => { showToast('Instance dihidupkan'); refresh(); });
                    }));
                }

                if (status !== 'TERMINATED') {
                    actEl.appendChild(btn('Terminate', 'background:#fde8e8;color:#9b2c2c;border-color:#f5c6c6;', () => {
                        showConfirm('Terminate ' + it.name + '?', 'Container akan dihentikan dan dihapus. Data persistent disk tetap tersimpan.', () => API.action(it.id, 'terminate').then(refresh));
                    }));
                }

                if (status === 'TERMINATED') {
                    actEl.appendChild(btn('Archive', 'background:#f3f4f6;color:#6b7280;border-color:#e5e7eb;', () => {
                        showConfirm('Archive instance ini?', 'Instance akan disembunyikan dari daftar aktif.', () => {
                            API.action(it.id, 'archive').then(res => { showToast(res?.deleted ? 'Diarsipkan' : 'Gagal'); refresh(); });
                        });
                    }));
                }
            } else {
                actEl.appendChild(btn('Restore', 'background:#eaf4dd;color:#3b5136;border-color:#c6e0a8;', () => {
                    showConfirm('Pulihkan instance ini?', '', () => API.action(it.id, 'restore').then(res => { showToast(res?.restored ? 'Dipulihkan' : 'Gagal'); refresh(); }));
                }));
                actEl.appendChild(btn('Hapus Permanen', 'background:#fde8e8;color:#9b2c2c;border-color:#f5c6c6;', () => {
                    showConfirm('Hapus permanen ' + it.name + '?', 'Data akan hilang selamanya.', () => {
                        API.action(it.id, 'purge').then(res => { showToast(res?.deleted ? 'Dihapus permanen' : 'Gagal'); refresh(); });
                    });
                }));
            }

            // Harga berjalan (kalau RUNNING)
            if (status === 'RUNNING' && meta.price_per_hour) {
                const priceEl = document.createElement('div');
                priceEl.style.cssText = 'font-size:0.72rem;color:var(--cp-ink-muted);text-align:right;margin-top:2px;';
                priceEl.innerText = 'Rp ' + parseInt(meta.price_per_hour).toLocaleString('id-ID') + '/jam';
                card.appendChild(priceEl);
            }

            wrap.appendChild(card);
        });
    }

    async function refresh() {
        const items = await API.list(currentTab === 'archived');
        renderItems(items);
    }

    // ── Modal SSH Detail ─────────────────────────────────────────────────────
    function showSshModal(instance) {
        const meta = instance.metadata || {};
        const modal = document.getElementById('ssh-modal');
        const body  = document.getElementById('ssh-modal-body');
        document.getElementById('ssh-modal-title').innerText = 'Koneksi SSH — ' + instance.name;

        const row = (label, value, mono = false) => `
            <div style="display:flex;flex-direction:column;gap:3px;">
                <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--cp-ink-muted);">${label}</div>
                <div style="font-size:0.85rem;${mono ? 'font-family:monospace;background:var(--cp-soft);padding:6px 10px;border-radius:0.5rem;' : ''}color:var(--cp-ink);font-weight:600;">${value}</div>
            </div>
        `;

        const serverIp = window.location.hostname;
        const sshCmd   = `ssh ${meta.ssh_user || 'root'}@${serverIp} -p ${meta.ssh_port}`;

        body.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                ${row('Host', serverIp)}
                ${row('Port SSH', meta.ssh_port || '—')}
                ${row('Username', meta.ssh_user || 'root')}
                ${row('Password', meta.ssh_password || '—')}
            </div>
            ${row('Perintah SSH', sshCmd, true)}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:4px;">
                ${meta.resources?.cpu    ? row('vCPU', meta.resources.cpu) : ''}
                ${meta.resources?.memory ? row('RAM',  meta.resources.memory >= 1024 ? (meta.resources.memory/1024)+'GB' : meta.resources.memory+'MB') : ''}
                ${meta.resources?.disk   ? row('Disk', meta.resources.disk+'GB') : ''}
            </div>
            <div class="cp-tip">
                <span>⚠️</span>
                <span style="font-size:0.8rem;color:var(--cp-ink-muted);">Simpan password ini. Untuk keamanan, ganti password setelah pertama login via <code>passwd</code>.</span>
            </div>
        `;

        modal.style.display = 'flex';
    }

    document.getElementById('ssh-modal-close').addEventListener('click', () => {
        document.getElementById('ssh-modal').style.display = 'none';
    });
    document.getElementById('ssh-modal-bg').addEventListener('click', () => {
        document.getElementById('ssh-modal').style.display = 'none';
    });

    // ── Modal Konfirmasi ─────────────────────────────────────────────────────
    function showConfirm(title, sub, onOk) {
        const modal  = document.getElementById('cp-confirm-modal');
        document.getElementById('cp-confirm-message').innerText = title;
        document.getElementById('cp-confirm-sub').innerText     = sub || 'Tindakan ini tidak dapat dibatalkan.';
        modal.style.display = 'flex';
        const ok     = document.getElementById('cp-confirm-ok');
        const cancel = document.getElementById('cp-confirm-cancel');
        function cleanup() { modal.style.display = 'none'; ok.removeEventListener('click', okH); cancel.removeEventListener('click', cancelH); }
        function okH()     { cleanup(); onOk(); }
        function cancelH() { cleanup(); }
        ok.addEventListener('click', okH);
        cancel.addEventListener('click', cancelH);
    }

    // ── Toast ────────────────────────────────────────────────────────────────
    function showToast(msg) {
        const c = document.getElementById('cp-toast-container');
        const t = document.createElement('div');
        t.style.cssText = 'pointer-events:auto;background:linear-gradient(90deg,#3b5136,#4a6344);color:#eef6e8;padding:10px 16px;border-radius:0.85rem;font-weight:700;font-size:0.875rem;box-shadow:0 6px 18px rgba(34,48,31,0.22);';
        t.innerText = msg;
        c.appendChild(t);
        setTimeout(() => { t.style.transition = 'opacity 300ms'; t.style.opacity = '0'; setTimeout(() => t.remove(), 350); }, 3500);
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    refresh();
    setInterval(refresh, 5000);
})();
</script>

@endcomponent