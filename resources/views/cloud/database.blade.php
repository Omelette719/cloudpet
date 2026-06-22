@component('layouts.app')
<div class="cp-page">

    <div class="cp-banner" style="margin-bottom:1rem;">
        <div style="position:relative;z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">Cloud Database</p>
            <h2>Managed Database</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">PostgreSQL, MySQL, MariaDB — buat, kelola, dan query langsung dari browser.</p>
        </div>
    </div>

    <div style="display:grid;gap:1rem;">

        {{-- CREATE DATABASE --}}
        <div class="cp-card">
            <h3 class="cp-section-title" style="margin-top:0;">Buat Database Baru</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start;">
                <div>
                    {{-- Engine selector --}}
                    <div style="margin-bottom:14px;">
                        <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Engine</label>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;" id="engine-selector"></div>
                    </div>

                    {{-- Plan selector --}}
                    <div style="margin-bottom:14px;">
                        <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Plan</label>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;" id="db-plan-selector"></div>
                    </div>

                    {{-- Summary + create --}}
                    <div style="background:var(--cp-soft);border:1px solid var(--cp-soft-border);border-radius:0.9rem;padding:14px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px;">
                            <div style="text-align:center;">
                                <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;">vCPU</div>
                                <div style="font-size:1.1rem;font-weight:800;color:var(--cp-ink);" id="db-sum-cpu">—</div>
                            </div>
                            <div style="text-align:center;border-left:1px solid var(--cp-soft-border);border-right:1px solid var(--cp-soft-border);">
                                <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;">RAM</div>
                                <div style="font-size:1.1rem;font-weight:800;color:var(--cp-ink);" id="db-sum-ram">—</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:0.62rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;">Harga</div>
                                <div style="font-size:1.1rem;font-weight:800;color:var(--cp-primary-strong);" id="db-sum-price">—</div>
                            </div>
                        </div>
                        <button id="db-create-btn" class="cp-btn" style="width:100%;padding:12px;font-size:0.92rem;border-radius:0.85rem;" disabled>
                            Buat Database
                        </button>
                    </div>
                </div>

                {{-- Terminal / Guide --}}
                <div style="background:var(--cp-soft);border:1px solid var(--cp-soft-border);border-radius:0.95rem;padding:16px;min-height:300px;">
                    <div id="db-guide-panel">
                        <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--cp-ink-muted);margin-bottom:12px;">Panduan Database</div>
                        @foreach([
                            ['step'=>'1','text'=>'Pilih engine database (PostgreSQL, MySQL, atau MariaDB).'],
                            ['step'=>'2','text'=>'Pilih plan sesuai kebutuhan resource.'],
                            ['step'=>'3','text'=>'Klik <strong>"Buat Database"</strong> — proses provisioning ditampilkan di panel ini.'],
                            ['step'=>'4','text'=>'Setelah <span style="background:#eaf4dd;color:#3b5136;font-size:0.68rem;font-weight:700;padding:2px 7px;border-radius:999px;">RUNNING</span>, gunakan <strong>Detail Koneksi</strong> atau <strong>Kelola Data</strong> langsung dari browser.'],
                        ] as $s)
                        <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;">
                            <span style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--cp-primary-start),var(--cp-primary-end));color:#fff;font-size:0.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $s['step'] }}</span>
                            <span style="font-size:0.83rem;color:var(--cp-ink-muted);line-height:1.5;padding-top:2px;">{!! $s['text'] !!}</span>
                        </div>
                        @endforeach
                    </div>
                    <div id="db-provision-panel" style="display:none;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--cp-ink-muted);">Proses Pembuatan Database</div>
                            <span id="db-provision-chip" style="font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#fef3c7;color:#92400e;">PROVISIONING</span>
                        </div>
                        <pre id="db-provision-terminal" style="background:#1b1f17;color:#cdebb0;font-family:'Cascadia Code','Fira Code',monospace;font-size:0.74rem;line-height:1.55;border-radius:0.7rem;padding:12px 14px;margin:0;height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
                        <div id="db-provision-result" style="margin-top:10px;display:none;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DATABASE LIST --}}
        <div class="cp-card">
            <h3 class="cp-section-title" style="margin:0 0 14px;">Database Anda</h3>
            <div id="db-list-wrap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:0.9rem;"></div>
        </div>

        {{-- DATA MANAGER (hidden by default) --}}
        <div class="cp-card" id="db-manager-section" style="display:none;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h3 class="cp-section-title" style="margin:0;" id="db-manager-title">Kelola Data</h3>
                <button onclick="closeManager()" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:var(--cp-ink-muted);">✕</button>
            </div>
            <div style="display:grid;grid-template-columns:180px 1fr;gap:1rem;min-height:300px;">
                {{-- Table list --}}
                <div style="border-right:1px solid var(--cp-soft-border);padding-right:1rem;">
                    <div style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Tabel</div>
                    <div id="db-table-list" style="display:grid;gap:4px;"></div>
                </div>
                {{-- Table content --}}
                <div>
                    <div id="db-table-content" style="overflow-x:auto;max-height:400px;overflow-y:auto;">
                        <div style="text-align:center;color:var(--cp-ink-muted);padding:3rem;font-size:0.85rem;">Pilih tabel di sebelah kiri.</div>
                    </div>
                </div>
            </div>
            {{-- SQL query box --}}
            <div style="margin-top:1rem;border-top:1px solid var(--cp-soft-border);padding-top:1rem;">
                <div style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">SQL Query</div>
                <div style="display:flex;gap:8px;">
                    <textarea id="db-sql-input" rows="2" placeholder="SELECT * FROM ..." style="flex:1;padding:8px 12px;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-family:monospace;font-size:0.82rem;resize:vertical;color:var(--cp-ink);"></textarea>
                    <button onclick="runQuery()" class="cp-btn" style="width:auto;padding:8px 18px;font-size:0.82rem;border-radius:0.7rem;align-self:flex-end;">Jalankan</button>
                </div>
                <div id="db-sql-result" style="margin-top:8px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- CONNECTION MODAL --}}
<div id="db-conn-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:9999;">
    <div style="position:absolute;inset:0;background:rgba(34,48,31,0.55);" onclick="document.getElementById('db-conn-modal').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:1.25rem;padding:24px;width:520px;max-width:94%;box-shadow:0 16px 44px rgba(34,48,31,0.22);border:1px solid var(--cp-soft-border);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-weight:800;font-size:1rem;color:var(--cp-ink);" id="db-conn-title">Detail Koneksi</div>
            <button onclick="document.getElementById('db-conn-modal').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--cp-ink-muted);">✕</button>
        </div>
        <div id="db-conn-body" style="display:grid;gap:12px;"></div>
    </div>
</div>

{{-- CONFIRM MODAL --}}
<div id="db-confirm-modal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:9999;">
    <div style="position:absolute;inset:0;background:rgba(34,48,31,0.55);"></div>
    <div style="position:relative;background:#fff;border-radius:1.25rem;padding:24px;width:380px;max-width:92%;box-shadow:0 16px 44px rgba(34,48,31,0.22);border:1px solid var(--cp-soft-border);">
        <div id="db-confirm-msg" style="font-size:0.95rem;font-weight:800;color:var(--cp-ink);margin-bottom:8px;"></div>
        <div id="db-confirm-sub" style="font-size:0.85rem;color:var(--cp-ink-muted);margin-bottom:20px;"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button id="db-confirm-cancel" style="padding:9px 18px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);background:#fff;color:var(--cp-ink);font-size:0.875rem;font-weight:700;cursor:pointer;">Batal</button>
            <button id="db-confirm-ok" style="padding:9px 18px;border-radius:0.75rem;border:0;background:#c53030;color:#fff;font-size:0.875rem;font-weight:700;cursor:pointer;">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<div id="db-toast-container" style="position:fixed;right:20px;bottom:20px;z-index:10000;display:flex;flex-direction:column;gap:8px;pointer-events:none;"></div>

<script>
(function () {
    const _timers = [], _intervals = [];
    let _destroyed = false;
    function sti(fn, ms) { const id = setInterval(fn, ms); _intervals.push(id); return id; }
    function sci(id) { clearInterval(id); }
    function sto(fn, ms) { const id = setTimeout(fn, ms); _timers.push(id); return id; }
    document.addEventListener('livewire:navigating', function cleanup() {
        _destroyed = true; _intervals.forEach(clearInterval); _timers.forEach(clearTimeout);
        document.removeEventListener('livewire:navigating', cleanup);
    });

    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const API = {
        plans:   () => fetch('/cloud/api/plans/database').then(r => r.json()),
        list:    () => fetch('/cloud/api/databases', { credentials:'same-origin' }).then(r => r.ok ? r.json() : []).catch(() => []),
        create:  (plan_id, engine) => fetch('/cloud/api/databases', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify({plan_id, engine}), credentials:'same-origin' }).then(r => r.json()),
        action:  (id, action) => fetch(`/cloud/api/databases/${id}/action`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify({action}), credentials:'same-origin' }).then(r => r.json()),
        log:     (id) => fetch(`/cloud/api/databases/${id}/log`, { credentials:'same-origin' }).then(r => r.json()),
        conn:    (id) => fetch(`/cloud/api/databases/${id}/connection`, { credentials:'same-origin' }).then(r => r.json()),
        del:     (id) => fetch(`/cloud/api/databases/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF}, credentials:'same-origin' }).then(r => r.json()),
        tables:  (id) => fetch(`/cloud/api/databases/${id}/tables`, { credentials:'same-origin' }).then(r => r.json()),
        rows:    (id, t) => fetch(`/cloud/api/databases/${id}/tables/${t}`, { credentials:'same-origin' }).then(r => r.json()),
        query:   (id, sql) => fetch(`/cloud/api/databases/${id}/query`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify({sql}), credentials:'same-origin' }).then(r => r.json()),
    };

    const ENGINE_ICONS = { 'postgres-15':'🐘','postgres-14':'🐘','mysql-8':'🐬','mysql-5.7':'🐬','mariadb-10':'🦭' };
    const STATUS_STYLE = {
        RUNNING:'background:#eaf4dd;color:#3b5136;', STOPPED:'background:#f0f5ea;color:#61765d;',
        TERMINATED:'background:#fde8e8;color:#9b2c2c;', PROVISIONING:'background:#fef3c7;color:#92400e;',
        ERROR:'background:#fde8e8;color:#7a1111;',
    };

    let dbPlans = [], engines = [], selectedEngine = null, selectedPlanId = null, managerDbId = null;

    // ── Load plans + engines ──────────────────────────────────────────────
    API.plans().then(data => {
        dbPlans = data.plans || [];
        engines = data.engines ? Object.values(data.engines) : [];
        renderEngineSelector();
        renderPlanSelector();
    });

    function renderEngineSelector() {
        const wrap = document.getElementById('engine-selector');
        wrap.innerHTML = engines.map(e => `
            <button class="eng-btn" data-engine="${e.key}"
                style="padding:10px 6px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);background:#fff;cursor:pointer;text-align:center;transition:all 0.15s;">
                <div style="font-size:1.2rem;margin-bottom:2px;">${ENGINE_ICONS[e.key]||'🗄️'}</div>
                <div style="font-size:0.72rem;font-weight:800;color:var(--cp-ink);">${e.label}</div>
            </button>
        `).join('');
        wrap.querySelectorAll('.eng-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedEngine = btn.dataset.engine;
                wrap.querySelectorAll('.eng-btn').forEach(b => { b.style.background='#fff'; b.style.borderColor='var(--cp-soft-border)'; b.querySelectorAll('div').forEach(d=>d.style.color=''); });
                btn.style.background='linear-gradient(135deg,var(--cp-primary-start),var(--cp-primary-end))'; btn.style.borderColor='var(--cp-primary-end)';
                btn.querySelectorAll('div').forEach(d=>d.style.color='#fff');
                updateSummary();
            });
        });
    }

    function renderPlanSelector() {
        const wrap = document.getElementById('db-plan-selector');
        wrap.innerHTML = dbPlans.map(p => `
            <button class="dbplan-btn" data-plan-id="${p.id}"
                style="padding:10px 6px;border-radius:0.75rem;border:1px solid var(--cp-soft-border);background:#fff;cursor:pointer;text-align:center;transition:all 0.15s;">
                <div style="font-size:0.8rem;font-weight:800;color:var(--cp-ink);text-transform:capitalize;">${p.name.replace('db-','')}</div>
                <div style="font-size:0.62rem;color:var(--cp-ink-muted);margin-top:2px;">${p.vcpu}C · ${p.ram>=1024?p.ram/1024+'GB':p.ram+'MB'}</div>
            </button>
        `).join('');
        wrap.querySelectorAll('.dbplan-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedPlanId = btn.dataset.planId;
                wrap.querySelectorAll('.dbplan-btn').forEach(b => { b.style.background='#fff'; b.style.borderColor='var(--cp-soft-border)'; b.querySelectorAll('div').forEach(d=>d.style.color=''); });
                btn.style.background='linear-gradient(135deg,var(--cp-primary-start),var(--cp-primary-end))'; btn.style.borderColor='var(--cp-primary-end)';
                btn.querySelectorAll('div').forEach(d=>d.style.color='#fff');
                updateSummary();
            });
        });
    }

    function updateSummary() {
        if (!selectedEngine || !selectedPlanId) return;
        const p = dbPlans.find(x => x.id === selectedPlanId);
        if (!p) return;
        document.getElementById('db-sum-cpu').innerText = p.vcpu + ' vCPU';
        document.getElementById('db-sum-ram').innerText = p.ram >= 1024 ? (p.ram/1024)+' GB' : p.ram+' MB';
        document.getElementById('db-sum-price').innerText = 'Rp ' + parseInt(p.price).toLocaleString('id-ID') + '/jam';
        document.getElementById('db-create-btn').disabled = false;
    }

    // ── Create database ───────────────────────────────────────────────────
    document.getElementById('db-create-btn').addEventListener('click', async () => {
        if (!selectedEngine || !selectedPlanId) return;
        const btn = document.getElementById('db-create-btn');
        btn.disabled = true; btn.innerText = 'Membuat...';

        const result = await API.create(selectedPlanId, selectedEngine);
        btn.innerText = 'Buat Database'; btn.disabled = false;

        if (result.error) { showToast(result.error); return; }
        showToast('Database sedang diprovisioning...');
        refresh();
        watchProvisioning(result.id);
    });

    // ── Provisioning terminal ─────────────────────────────────────────────
    function watchProvisioning(dbId) {
        const guide = document.getElementById('db-guide-panel');
        const panel = document.getElementById('db-provision-panel');
        const term  = document.getElementById('db-provision-terminal');
        const chip  = document.getElementById('db-provision-chip');
        const res   = document.getElementById('db-provision-result');

        guide.style.display = 'none'; panel.style.display = 'block';
        term.innerText = ''; res.style.display = 'none';
        chip.innerText = 'PROVISIONING'; chip.style.background = '#fef3c7'; chip.style.color = '#92400e';

        const poll = sti(async () => {
            if (_destroyed) { sci(poll); return; }
            let data; try { data = await API.log(dbId); } catch(e) { return; }
            term.innerText = data.log || ''; term.scrollTop = term.scrollHeight;
            if (data.status === 'RUNNING') {
                sci(poll); chip.innerText = 'RUNNING'; chip.style.background = '#eaf4dd'; chip.style.color = '#3b5136';
                showToast('Database berhasil dibuat!'); refresh();
                sto(() => { panel.style.display = 'none'; guide.style.display = 'block'; }, 4000);
            } else if (data.status === 'ERROR') {
                sci(poll); chip.innerText = 'ERROR'; chip.style.background = '#fde8e8'; chip.style.color = '#9b2c2c';
                res.style.display = 'block';
                res.innerHTML = '<div style="background:#fde8e8;border:1px solid #f5c6c6;border-radius:0.75rem;padding:10px 14px;font-size:0.82rem;font-weight:700;color:#9b2c2c;">Provisioning gagal.</div>';
                showToast('Pembuatan database gagal.'); refresh();
            }
        }, 1500);
    }

    // ── Render databases ──────────────────────────────────────────────────
    function renderItems(items) {
        const wrap = document.getElementById('db-list-wrap');
        if (!items || !items.length) {
            wrap.innerHTML = '<div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--cp-ink-muted);font-size:0.88rem;">Belum ada database.</div>';
            return;
        }
        wrap.innerHTML = items.map(db => {
            const meta = db.metadata || {};
            const st = STATUS_STYLE[db.status] || STATUS_STYLE.STOPPED;
            const icon = ENGINE_ICONS[db.engine] || '🗄️';
            const label = meta.engine_label || db.engine;
            return `
            <div style="background:#fff;border:1px solid var(--cp-soft-border);border-radius:1rem;padding:14px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:1.3rem;">${icon}</span>
                        <div>
                            <div style="font-weight:800;font-size:0.95rem;color:var(--cp-ink);">${db.db_name}</div>
                            <div style="font-size:0.72rem;color:var(--cp-ink-muted);font-weight:600;">${label} · ${meta.plan_label||''}</div>
                        </div>
                    </div>
                    <span style="${st}font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:999px;">${db.status}</span>
                </div>
                ${db.status==='RUNNING' ? `
                <div style="background:#1b2618;border-radius:0.65rem;padding:8px 10px;">
                    <div style="font-size:0.7rem;font-weight:700;color:#5a7a55;margin-bottom:2px;">ENDPOINT</div>
                    <div style="font-family:monospace;font-size:0.75rem;color:#cdebb0;">${db.host||'—'}:${db.port||'—'}</div>
                </div>` : ''}
                <div style="display:flex;gap:6px;flex-wrap:wrap;padding-top:8px;border-top:1px solid var(--cp-soft-border);">
                    ${db.status==='RUNNING' ? `
                        <button onclick="showConn('${db.id}')" style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #b8d4f0;background:#e8f2fb;color:#2b5fa0;">Detail Koneksi</button>
                        <button onclick="openManager('${db.id}','${db.db_name}')" style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #c6e0a8;background:#eaf4dd;color:#3b5136;">Kelola Data</button>
                        <button onclick="dbAction('${db.id}','stop')" style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid var(--cp-soft-border);background:#f0f5ea;color:#61765d;">Stop</button>
                    ` : ''}
                    ${db.status==='STOPPED' ? `
                        <button onclick="dbAction('${db.id}','start')" style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #c6e0a8;background:#eaf4dd;color:#3b5136;">Start</button>
                    ` : ''}
                    ${db.status!=='TERMINATED' ? `
                        <button onclick="confirmAction('Terminate ${db.db_name}?','Database container dihapus.',()=>dbAction('${db.id}','terminate'))" style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #f5c6c6;background:#fde8e8;color:#9b2c2c;">Terminate</button>
                    ` : `
                        <button onclick="confirmAction('Hapus permanen?','Tidak bisa dibatalkan.',()=>dbDelete('${db.id}'))" style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #f5c6c6;background:#fde8e8;color:#9b2c2c;">Hapus</button>
                    `}
                </div>
                ${db.status==='RUNNING' && meta.price_per_hour ? `<div style="font-size:0.72rem;color:var(--cp-ink-muted);text-align:right;">Rp ${parseInt(meta.price_per_hour).toLocaleString('id-ID')}/jam</div>` : ''}
            </div>`;
        }).join('');
    }

    // ── Actions ───────────────────────────────────────────────────────────
    window.dbAction = async (id, action) => {
        await API.action(id, action); showToast('Database di-' + action); refresh();
    };
    window.dbDelete = async (id) => {
        await API.del(id); showToast('Database dihapus'); refresh();
    };

    // ── Connection modal ──────────────────────────────────────────────────
    window.showConn = async (id) => {
        const info = await API.conn(id);
        const body = document.getElementById('db-conn-body');
        const row = (label, val, mono=false) => `
            <div style="display:flex;flex-direction:column;gap:3px;">
                <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--cp-ink-muted);">${label}</div>
                <div style="font-size:0.85rem;${mono?'font-family:monospace;background:var(--cp-soft);padding:6px 10px;border-radius:0.5rem;':''}color:var(--cp-ink);font-weight:600;word-break:break-all;">${val}</div>
            </div>`;
        body.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                ${row('Host', info.host)} ${row('Port', info.port)}
                ${row('Database', info.database)} ${row('Username', info.username)}
                ${row('Password', info.password)}
            </div>
            ${row('CLI', info.cli, true)}
            <div>
                <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--cp-ink-muted);margin-bottom:4px;">Laravel .env</div>
                <pre style="background:var(--cp-soft);padding:10px;border-radius:0.5rem;font-size:0.78rem;color:var(--cp-ink);margin:0;white-space:pre-wrap;">${info.laravel_env}</pre>
            </div>`;
        document.getElementById('db-conn-modal').style.display = 'flex';
    };

    // ── Data manager ──────────────────────────────────────────────────────
    window.openManager = async (id, name) => {
        managerDbId = id;
        document.getElementById('db-manager-title').innerText = 'Kelola Data — ' + name;
        document.getElementById('db-manager-section').style.display = 'block';
        document.getElementById('db-table-content').innerHTML = '<div style="text-align:center;color:var(--cp-ink-muted);padding:2rem;">Memuat tabel...</div>';
        document.getElementById('db-manager-section').scrollIntoView({behavior:'smooth'});

        const data = await API.tables(id);
        const list = document.getElementById('db-table-list');
        if (data.error) { list.innerHTML = `<div style="color:#9b2c2c;font-size:0.78rem;">${data.error}</div>`; return; }
        if (!data.tables || !data.tables.length) { list.innerHTML = '<div style="color:var(--cp-ink-muted);font-size:0.78rem;">Belum ada tabel.</div>'; return; }
        list.innerHTML = data.tables.map(t => `
            <button onclick="loadTable('${t}')" style="padding:6px 10px;border-radius:0.5rem;border:1px solid var(--cp-soft-border);background:#fff;text-align:left;font-size:0.78rem;font-weight:700;color:var(--cp-ink);cursor:pointer;transition:all 0.1s;"
                onmouseenter="this.style.background='var(--cp-soft)'" onmouseleave="this.style.background='#fff'">${t}</button>
        `).join('');
        document.getElementById('db-table-content').innerHTML = '<div style="text-align:center;color:var(--cp-ink-muted);padding:3rem;font-size:0.85rem;">Pilih tabel di sebelah kiri.</div>';
    };

    window.closeManager = () => {
        document.getElementById('db-manager-section').style.display = 'none';
        managerDbId = null;
    };

    window.loadTable = async (table) => {
        const wrap = document.getElementById('db-table-content');
        wrap.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--cp-ink-muted);">Memuat...</div>';
        const data = await API.rows(managerDbId, table);
        if (data.error) { wrap.innerHTML = `<div style="color:#9b2c2c;padding:1rem;">${data.error}</div>`; return; }
        if (!data.rows || !data.rows.length) {
            const cols = data.columns ? data.columns.map(c => c.column_name || c).join(', ') : '';
            wrap.innerHTML = `<div style="padding:1rem;color:var(--cp-ink-muted);font-size:0.82rem;">Tabel kosong. Kolom: ${cols || '—'}</div>`;
            return;
        }
        const keys = Object.keys(data.rows[0]);
        wrap.innerHTML = `
            <div style="font-size:0.72rem;color:var(--cp-ink-muted);margin-bottom:6px;">${data.total} baris total</div>
            <table class="cp-table" style="font-size:0.78rem;">
                <thead><tr>${keys.map(k=>`<th style="white-space:nowrap;">${k}</th>`).join('')}</tr></thead>
                <tbody>${data.rows.map(r=>`<tr>${keys.map(k=>`<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r[k]??'<span style="color:#aaa;">NULL</span>'}</td>`).join('')}</tr>`).join('')}</tbody>
            </table>`;
    };

    window.runQuery = async () => {
        const sql = document.getElementById('db-sql-input').value.trim();
        if (!sql || !managerDbId) return;
        const wrap = document.getElementById('db-sql-result');
        wrap.innerHTML = '<div style="color:var(--cp-ink-muted);font-size:0.78rem;">Menjalankan...</div>';
        const data = await API.query(managerDbId, sql);
        if (data.error) { wrap.innerHTML = `<div style="color:#9b2c2c;font-size:0.82rem;font-weight:700;padding:8px;background:#fde8e8;border-radius:0.5rem;">${data.error}</div>`; return; }
        if (data.rows && data.rows.length) {
            const keys = data.columns || Object.keys(data.rows[0]);
            wrap.innerHTML = `<div style="font-size:0.72rem;color:var(--cp-ink-muted);margin-bottom:4px;">${data.rows.length} baris</div>
                <div style="overflow-x:auto;max-height:250px;overflow-y:auto;">
                <table class="cp-table" style="font-size:0.78rem;">
                    <thead><tr>${keys.map(k=>`<th>${k}</th>`).join('')}</tr></thead>
                    <tbody>${data.rows.map(r=>`<tr>${keys.map(k=>`<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r[k]??'NULL'}</td>`).join('')}</tr>`).join('')}</tbody>
                </table></div>`;
        } else {
            wrap.innerHTML = `<div style="color:#3b5136;font-size:0.82rem;font-weight:700;padding:8px;background:#eaf4dd;border-radius:0.5rem;">${data.message || data.affected + ' baris terpengaruh.'}</div>`;
        }
    };

    // ── Confirm ───────────────────────────────────────────────────────────
    window.confirmAction = (title, sub, onOk) => {
        const modal = document.getElementById('db-confirm-modal');
        document.getElementById('db-confirm-msg').innerText = title;
        document.getElementById('db-confirm-sub').innerText = sub || 'Tindakan tidak bisa dibatalkan.';
        modal.style.display = 'flex';
        const ok = document.getElementById('db-confirm-ok');
        const cancel = document.getElementById('db-confirm-cancel');
        function cleanup() { modal.style.display='none'; ok.removeEventListener('click',okH); cancel.removeEventListener('click',cancelH); }
        function okH() { cleanup(); onOk(); }
        function cancelH() { cleanup(); }
        ok.addEventListener('click', okH); cancel.addEventListener('click', cancelH);
    };

    // ── Toast ─────────────────────────────────────────────────────────────
    function showToast(msg) {
        const c = document.getElementById('db-toast-container');
        if (!c) return;
        const t = document.createElement('div');
        t.style.cssText = 'pointer-events:auto;background:linear-gradient(90deg,#3b5136,#4a6344);color:#eef6e8;padding:10px 16px;border-radius:0.85rem;font-weight:700;font-size:0.875rem;box-shadow:0 6px 18px rgba(34,48,31,0.22);';
        t.innerText = msg; c.appendChild(t);
        sto(() => { t.style.transition='opacity 300ms'; t.style.opacity='0'; sto(()=>t.remove(),350); }, 3500);
    }

    // ── Init ──────────────────────────────────────────────────────────────
    async function refresh() {
        if (_destroyed) return;
        const items = await API.list();
        if (_destroyed) return;
        renderItems(items);
    }
    refresh();
    sti(() => { if (!_destroyed) refresh(); }, 8000);
})();
</script>

@endcomponent
