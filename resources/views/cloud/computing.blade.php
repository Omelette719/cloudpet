@component('layouts.app')
    <div class="cp-page">

        {{-- BANNER --}}
        <div class="cp-banner" style="margin-bottom:1rem;">
            <div style="position:relative; z-index:1;">
                <p style="font-size:0.75rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; opacity:0.8; margin-bottom:4px;">Cloud Computing</p>
                <h2>Kelola Instance Virtual</h2>
                <p style="color:rgba(255,255,255,0.72); font-weight:400; margin-top:4px;">
                    Pilih plan, buat instance, dan kelola siklus hidup VM Anda.
                    <span style="opacity:0.6;">(Demo frontend-only)</span>
                </p>
            </div>
        </div>

        <div style="display:grid; gap:1rem;">

            {{-- BUAT INSTANCE --}}
            <div class="cp-card">
                <h3 class="cp-section-title" style="margin-top:0;">Buat Instance Baru</h3>

                <div style="display:grid; grid-template-columns:1fr 300px; gap:1rem; align-items:start;">

                    {{-- Kiri: form --}}
                    <div>
                        <div class="cp-input-group" style="margin-bottom:14px;">
                            <label class="cp-label" style="color:var(--cp-ink); font-size:0.78rem;">Runtime</label>
                            <select id="cp-runtime" class="cp-table-control" style="width:100%; padding:10px 12px; font-size:0.9rem; border-radius:0.75rem;">
                                <option value="micro">Micro (VM)</option>
                                <option value="small">Small (VM)</option>
                                <option value="medium">Medium (VM)</option>
                                <option value="large">Large (VM)</option>
                                <option value="jupyter">Jupyter (Notebook)</option>
                                <option value="code-server">IDE (code-server)</option>
                            </select>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 140px; gap:0.6rem; margin-bottom:14px;">
                            <div class="cp-input-group" style="margin-bottom:0;">
                                <label class="cp-label" style="color:var(--cp-ink); font-size:0.78rem;">vRAM</label>
                                <select id="cp-vram" class="cp-table-control" style="width:100%; padding:10px 12px; font-size:0.9rem; border-radius:0.75rem;">
                                    <option value="0.5">0.5 GB</option>
                                    <option value="1">1 GB</option>
                                    <option value="2">2 GB</option>
                                    <option value="4">4 GB</option>
                                    <option value="8">8 GB</option>
                                </select>
                            </div>
                            <div class="cp-input-group" style="margin-bottom:0;">
                                <label class="cp-label" style="color:var(--cp-ink); font-size:0.78rem;">vCPU</label>
                                <select id="cp-cpu" class="cp-table-control" style="width:100%; padding:10px 12px; font-size:0.9rem; border-radius:0.75rem;">
                                    <option value="10">1 vCPU</option>
                                    <option value="50">1 vCPU+</option>
                                    <option value="100">2 vCPU</option>
                                    <option value="200">4 vCPU</option>
                                </select>
                            </div>
                        </div>

                        {{-- Harga + Tombol --}}
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; background:var(--cp-soft); border:1px solid var(--cp-soft-border); border-radius:0.9rem; padding:12px 16px; margin-bottom:14px;">
                            <div>
                                <div style="font-size:0.72rem; font-weight:700; letter-spacing:0.05em; text-transform:uppercase; color:var(--cp-ink-muted);">Estimasi Harga</div>
                                <div id="cp-price" style="font-size:1.3rem; font-weight:800; color:var(--cp-primary-strong); margin-top:2px;" data-value="0">Rp 0/jam</div>
                            </div>
                            <button id="cp-create-btn" class="cp-btn" style="width:auto; padding:12px 22px; font-size:0.92rem; border-radius:0.85rem;">
                                + Buat Instance
                            </button>
                        </div>

                        {{-- Toggle options --}}
                        <div style="display:flex; flex-wrap:wrap; gap:1.2rem; padding-top:10px; border-top:1px solid var(--cp-soft-border);">
                            <label style="display:flex; align-items:center; gap:7px; font-size:0.84rem; font-weight:600; color:var(--cp-ink-muted); cursor:pointer;">
                                <input id="cp-ssh" type="checkbox" style="width:15px; height:15px; accent-color:var(--cp-primary-strong);">
                                Enable SSH
                            </label>
                            <label style="display:flex; align-items:center; gap:7px; font-size:0.84rem; font-weight:600; color:var(--cp-ink-muted); cursor:pointer;">
                                <input id="cp-persistent" type="checkbox" style="width:15px; height:15px; accent-color:var(--cp-primary-strong);">
                                Persistent Disk
                            </label>
                            <label style="display:flex; align-items:center; gap:7px; font-size:0.84rem; font-weight:600; color:var(--cp-ink-muted); cursor:pointer;">
                                <input id="cp-auto-open" type="checkbox" style="width:15px; height:15px; accent-color:var(--cp-primary-strong);">
                                Auto-open saat Running
                            </label>
                        </div>
                    </div>

                    {{-- Kanan: presets --}}
                    <div style="background:var(--cp-soft); border:1px solid var(--cp-soft-border); border-radius:0.95rem; padding:14px;">
                        <div style="font-size:0.72rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--cp-ink-muted); margin-bottom:10px;">Presets</div>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            @foreach([
                                ['vram'=>'0.5','cpu'=>'10','runtime'=>'micro',       'label'=>'Micro',   'spec'=>'0.5 GB · 1 vCPU'],
                                ['vram'=>'1',  'cpu'=>'50','runtime'=>'small',       'label'=>'Small',   'spec'=>'1 GB · 1 vCPU'],
                                ['vram'=>'2',  'cpu'=>'100','runtime'=>'medium',     'label'=>'Medium',  'spec'=>'2 GB · 2 vCPU'],
                                ['vram'=>'4',  'cpu'=>'200','runtime'=>'large',      'label'=>'Large',   'spec'=>'4 GB · 4 vCPU'],
                                ['vram'=>'1',  'cpu'=>'100','runtime'=>'jupyter',    'label'=>'Jupyter', 'spec'=>'Notebook'],
                                ['vram'=>'1',  'cpu'=>'100','runtime'=>'code-server','label'=>'IDE',     'spec'=>'code-server'],
                            ] as $preset)
                            <button class="cp-preset"
                                data-vram="{{ $preset['vram'] }}"
                                data-cpu="{{ $preset['cpu'] }}"
                                data-runtime="{{ $preset['runtime'] }}"
                                style="display:flex; justify-content:space-between; align-items:center; width:100%; padding:9px 12px; background:#fff; border:1px solid var(--cp-soft-border); border-radius:0.75rem; font-size:0.84rem; font-weight:700; color:var(--cp-ink); cursor:pointer; text-align:left; transition:background 0.15s, border-color 0.15s;">
                                <span>{{ $preset['label'] }}</span>
                                <span style="font-size:0.72rem; font-weight:600; color:var(--cp-ink-muted); background:var(--cp-soft); border:1px solid var(--cp-soft-border); border-radius:999px; padding:2px 8px;">{{ $preset['spec'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- INSTANCE LIST --}}
            <div class="cp-card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <h3 class="cp-section-title" style="margin:0;">Instance Anda</h3>
                    <div style="display:flex; gap:6px;">
                        <button id="tab-active"
                            style="padding:6px 14px; border-radius:999px; font-size:0.8rem; font-weight:700; cursor:pointer; border:1px solid transparent; background:linear-gradient(90deg,var(--cp-primary-start),var(--cp-primary-end)); color:#fff;">
                            Active
                        </button>
                        <button id="tab-archived"
                            style="padding:6px 14px; border-radius:999px; font-size:0.8rem; font-weight:700; cursor:pointer; border:1px solid var(--cp-soft-border); background:#fff; color:var(--cp-ink-muted);">
                            Archived
                        </button>
                    </div>
                </div>

                <div id="cp-instances-wrap" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:0.85rem;">
                    {{-- dirender oleh JS --}}
                </div>
            </div>

            {{-- CATATAN --}}
            <div class="cp-card">
                <h3 class="cp-section-title" style="margin-top:0;">Catatan & Harga</h3>
                <div class="cp-tip" style="margin-bottom:10px;">
                    <span style="font-size:1rem;">💡</span>
                    <span style="font-size:0.84rem; color:var(--cp-ink-muted);">Harga merupakan simulasi. Faktur aktual tidak akan dibuat di demo ini.</span>
                </div>
                <ul style="list-style:none; display:grid; gap:6px; padding:0; margin:0;">
                    @foreach([
                        'Biaya dihitung per jam berdasarkan vCPU dan vRAM yang dipilih.',
                        'Operasi provisioning bersifat asinkron pada implementasi nyata.',
                        'Untuk produksi, integrasikan dengan Ministack API dan queue worker.',
                    ] as $note)
                    <li style="display:flex; align-items:flex-start; gap:8px; font-size:0.84rem; color:var(--cp-ink-muted);">
                        <span style="width:5px; height:5px; border-radius:50%; background:var(--cp-primary-end); margin-top:7px; flex-shrink:0; display:block;"></span>
                        {{ $note }}
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>{{-- /grid --}}
    </div>{{-- /cp-page --}}

    {{-- TEMPLATE INSTANCE CARD --}}
    <template id="cp-instance-template">
        <div style="background:#fff; border:1px solid var(--cp-soft-border); border-radius:1rem; padding:14px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                <div>
                    <div style="font-weight:800; font-size:0.95rem; color:var(--cp-ink);">__NAME__</div>
                    <div style="font-size:0.76rem; color:var(--cp-ink-muted); margin-top:2px; font-weight:600;">__PLAN__</div>
                </div>
                <div class="cp-chip" data-status="__STATUS__">__STATUS__</div>
            </div>
            <div class="cp-instance-meta" style="font-size:0.78rem; color:var(--cp-ink-muted); margin:10px 0 0; line-height:1.6; padding-top:8px; border-top:1px solid var(--cp-soft-border);"></div>
            <div class="cp-instance-actions" style="display:flex; gap:6px; flex-wrap:wrap; margin-top:10px;"></div>
        </div>
    </template>

    {{-- MODAL KONFIRMASI --}}
    <div id="cp-confirm-modal" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; z-index:9999;">
        <div style="position:absolute; inset:0; background:rgba(34,48,31,0.55);"></div>
        <div style="position:relative; background:#fff; border-radius:1.25rem; padding:24px; width:380px; max-width:92%; box-shadow:0 16px 44px rgba(34,48,31,0.22); border:1px solid var(--cp-soft-border);">
            <div id="cp-confirm-message" style="font-size:0.95rem; font-weight:800; color:var(--cp-ink); margin-bottom:8px;"></div>
            <div style="font-size:0.85rem; color:var(--cp-ink-muted); margin-bottom:20px;">Tindakan ini tidak dapat dibatalkan setelah dikonfirmasi.</div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button id="cp-confirm-cancel"
                    style="padding:9px 18px; border-radius:0.75rem; border:1px solid var(--cp-soft-border); background:#fff; color:var(--cp-ink); font-size:0.875rem; font-weight:700; cursor:pointer;">
                    Batal
                </button>
                <button id="cp-confirm-ok"
                    style="padding:9px 18px; border-radius:0.75rem; border:0; background:#c53030; color:#fff; font-size:0.875rem; font-weight:700; cursor:pointer;">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    {{-- TOAST --}}
    <div id="cp-toast-container" style="position:fixed; right:20px; bottom:20px; z-index:10000; display:flex; flex-direction:column; gap:8px; pointer-events:none;"></div>

    <script>
    (function(){
        const API = {
            list: async (archived = false) => {
                const url = '{{ route('cloud.api.instances') }}' + (archived ? '?archived=1' : '');
                return fetch(url, { credentials: 'same-origin' }).then(r => r.ok ? r.json() : []).catch(() => []);
            },
            create: async (plan, opts = {}) => fetch('{{ route('cloud.api.instances.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: JSON.stringify(Object.assign({ plan }, opts)),
                credentials: 'same-origin'
            }).then(r => r.json()),
            action: async (id, action) => fetch(`/cloud/api/instances/${id}/action`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: JSON.stringify({ action }),
                credentials: 'same-origin'
            }).then(r => r.json()),
        };

        let currentTab = 'active';

        // --- Tab ---
        const tabActive   = document.getElementById('tab-active');
        const tabArchived = document.getElementById('tab-archived');

        const styleActive   = 'padding:6px 14px; border-radius:999px; font-size:0.8rem; font-weight:700; cursor:pointer; border:1px solid transparent; background:linear-gradient(90deg,var(--cp-primary-start),var(--cp-primary-end)); color:#fff;';
        const styleInactive = 'padding:6px 14px; border-radius:999px; font-size:0.8rem; font-weight:700; cursor:pointer; border:1px solid var(--cp-soft-border); background:#fff; color:var(--cp-ink-muted);';

        function setTab(t) {
            currentTab = t;
            tabActive.style.cssText   = t === 'active'   ? styleActive : styleInactive;
            tabArchived.style.cssText = t === 'archived' ? styleActive : styleInactive;
            refresh();
        }
        tabActive.addEventListener('click',   () => setTab('active'));
        tabArchived.addEventListener('click', () => setTab('archived'));

        // --- Modal ---
        function showConfirm(message, onConfirm) {
            const modal  = document.getElementById('cp-confirm-modal');
            const msg    = document.getElementById('cp-confirm-message');
            const ok     = document.getElementById('cp-confirm-ok');
            const cancel = document.getElementById('cp-confirm-cancel');
            msg.innerText = message;
            modal.style.display = 'flex';
            function cleanup() { modal.style.display = 'none'; ok.removeEventListener('click', okH); cancel.removeEventListener('click', cancelH); }
            function okH()     { cleanup(); onConfirm(); }
            function cancelH() { cleanup(); }
            ok.addEventListener('click', okH);
            cancel.addEventListener('click', cancelH);
        }

        // --- Toast ---
        function showToast(message) {
            const c = document.getElementById('cp-toast-container');
            const t = document.createElement('div');
            t.style.cssText = 'pointer-events:auto; background:linear-gradient(90deg,#3b5136,#4a6344); color:#eef6e8; padding:10px 16px; border-radius:0.85rem; font-weight:700; font-size:0.875rem; box-shadow:0 6px 18px rgba(34,48,31,0.22);';
            t.innerText = message;
            c.appendChild(t);
            setTimeout(() => { t.style.transition = 'opacity 300ms'; t.style.opacity = '0'; setTimeout(() => t.remove(), 350); }, 3500);
        }

        // --- Auto-open ---
        const AUTO_OPEN_KEY = 'cloudpet_auto_open';
        function isAutoOpenEnabled() { try { const v = localStorage.getItem(AUTO_OPEN_KEY); return v === null ? true : v === '1'; } catch(e) { return true; } }
        try {
            const cb = document.getElementById('cp-auto-open');
            if (cb) { cb.checked = isAutoOpenEnabled(); cb.addEventListener('change', () => { try { localStorage.setItem(AUTO_OPEN_KEY, cb.checked ? '1' : '0'); } catch(e){} }); }
        } catch(e){}

        // --- Status chip styling ---
        const STATUS_STYLE = {
            RUNNING:    'background:#eaf4dd; color:#3b5136;',
            STOPPED:    'background:#f0f5ea; color:#719068;',
            TERMINATED: 'background:#fde8e8; color:#9b2c2c;',
            PENDING:    'background:#fef3c7; color:#92400e;',
        };

        // --- Render ---
        function renderItems(items) {
            const wrap = document.getElementById('cp-instances-wrap');
            wrap.innerHTML = '';
            if (!items || items.length === 0) {
                wrap.innerHTML = '<div style="grid-column:1/-1; padding:2rem; text-align:center; color:var(--cp-ink-muted); font-size:0.88rem;">Belum ada instance.</div>';
                return;
            }
            const tpl = document.getElementById('cp-instance-template').innerHTML;
            items.forEach(it => {
                const html = tpl.replace(/__NAME__/g, it.name).replace(/__PLAN__/g, it.plan).replace(/__STATUS__/g, it.status);
                const el = document.createElement('div'); el.innerHTML = html;
                const node = el.firstElementChild;

                // chip style
                const chip = node.querySelector('.cp-chip');
                const chipStyle = STATUS_STYLE[(it.status || '').toUpperCase()] || STATUS_STYLE.STOPPED;
                chip.style.cssText = chipStyle + 'font-size:0.7rem; font-weight:700; padding:3px 9px; border-radius:999px;';

                // meta
                const metaParts = [];
                if (it.metadata?.requested) {
                    const r = it.metadata.requested;
                    if (r.runtime)  metaParts.push(r.runtime);
                    if (r.vram_gb)  metaParts.push(r.vram_gb + ' GB');
                    if (r.cpu)      metaParts.push(r.cpu + ' CPU');
                }
                if (it.metadata?.volumePath)     metaParts.push('Disk: ' + it.metadata.volumePath);
                if (it.metadata?.ssh_host_port)  metaParts.push('SSH :' + it.metadata.ssh_host_port);
                node.querySelector('.cp-instance-meta').innerText = metaParts.join(' · ');

                // actions
                const actEl = node.querySelector('.cp-instance-actions');
                const btnBase = 'padding:6px 12px; border-radius:0.65rem; font-size:0.78rem; font-weight:700; cursor:pointer; border:1px solid;';

                if (currentTab === 'active') {
                    const btnStart = document.createElement('button');
                    btnStart.innerText = 'Start';
                    btnStart.style.cssText = btnBase + 'background:#eaf4dd; color:#3b5136; border-color:#c6e0a8;';
                    btnStart.addEventListener('click', () => API.action(it.id, 'start').then(() => refresh()));

                    const btnStop = document.createElement('button');
                    btnStop.innerText = 'Stop';
                    btnStop.style.cssText = btnBase + 'background:#f0f5ea; color:#61765d; border-color:var(--cp-soft-border);';
                    btnStop.addEventListener('click', () => API.action(it.id, 'stop').then(() => refresh()));

                    const btnTerm = document.createElement('button');
                    btnTerm.innerText = 'Terminate';
                    btnTerm.style.cssText = btnBase + 'background:#fde8e8; color:#9b2c2c; border-color:#f5c6c6;';
                    btnTerm.addEventListener('click', () => showConfirm('Terminate instance ' + it.name + '?', () => API.action(it.id, 'terminate').then(() => refresh())));

                    actEl.append(btnStart, btnStop, btnTerm);

                    if ((it.status || '').toUpperCase() === 'TERMINATED') {
                        const arch = document.createElement('button');
                        arch.innerText = 'Archive';
                        arch.style.cssText = btnBase + 'background:#e8f2fb; color:#2b5fa0; border-color:#b8d4f0;';
                        arch.addEventListener('click', () => showConfirm('Archive instance ini?', () => API.action(it.id, 'archive').then(res => { showToast(res?.deleted ? 'Instance diarsipkan' : 'Gagal mengarsipkan'); refresh(); })));
                        actEl.appendChild(arch);
                    }
                } else {
                    const btnRestore = document.createElement('button');
                    btnRestore.innerText = 'Restore';
                    btnRestore.style.cssText = btnBase + 'background:#eaf4dd; color:#3b5136; border-color:#c6e0a8;';
                    btnRestore.addEventListener('click', () => showConfirm('Pulihkan instance ini?', () => API.action(it.id, 'restore').then(res => { showToast(res?.restored ? 'Instance dipulihkan' : 'Gagal memulihkan'); refresh(); })));

                    const btnPurge = document.createElement('button');
                    btnPurge.innerText = 'Hapus Permanen';
                    btnPurge.style.cssText = btnBase + 'background:#fde8e8; color:#9b2c2c; border-color:#f5c6c6;';
                    btnPurge.addEventListener('click', () => showConfirm('Hapus permanen? Tidak dapat dibatalkan.', () => API.action(it.id, 'purge').then(res => { showToast(res?.deleted ? 'Dihapus permanen' : 'Gagal menghapus'); refresh(); })));

                    actEl.append(btnRestore, btnPurge);
                }

                // links Jupyter / code-server
                if (it.metadata?.jupyter_host_port && it.metadata?.jupyter_token) {
                    const a = document.createElement('a');
                    a.href = `http://localhost:${it.metadata.jupyter_host_port}/?token=${it.metadata.jupyter_token}`;
                    a.target = '_blank';
                    a.style.cssText = 'display:block; margin-top:8px; font-size:0.8rem; color:var(--cp-primary-strong); font-weight:700; text-decoration:underline;';
                    a.innerText = `Buka Jupyter (:${it.metadata.jupyter_host_port})`;
                    node.appendChild(a);
                }
                if (it.metadata?.codeserver_host_port) {
                    const a = document.createElement('a');
                    a.href = `http://localhost:${it.metadata.codeserver_host_port}`;
                    a.target = '_blank';
                    a.style.cssText = 'display:block; margin-top:4px; font-size:0.8rem; color:var(--cp-primary-strong); font-weight:700; text-decoration:underline;';
                    a.innerText = `Buka IDE (:${it.metadata.codeserver_host_port})`;
                    node.appendChild(a);
                    if (it.metadata.codeserver_password) {
                        const info = document.createElement('div');
                        info.style.cssText = 'font-size:0.72rem; color:var(--cp-ink-muted); margin-top:2px;';
                        info.innerText = `Password: ${it.metadata.codeserver_password}`;
                        node.appendChild(info);
                    }
                }

                wrap.appendChild(node);
            });
        }

        async function refresh() {
            const items = await API.list(currentTab === 'archived');
            renderItems(items);
        }

        // --- Pricing ---
        const CPU_RATE = 4, VRAM_RATE = 250;
        function calcPrice(cpu, vram) { return Math.round(cpu * CPU_RATE + vram * VRAM_RATE); }

        function updatePrice() {
            const cpu  = parseInt(document.getElementById('cp-cpu').value  || '0', 10);
            const vram = parseFloat(document.getElementById('cp-vram').value || '0');
            const p    = calcPrice(cpu, vram);
            const el   = document.getElementById('cp-price');
            el.innerText    = 'Rp ' + p.toLocaleString('id-ID') + '/jam';
            el.dataset.value = p;
        }

        // --- Preset buttons ---
        document.querySelectorAll('.cp-preset').forEach(b => {
            b.addEventListener('mouseenter', () => { b.style.background = 'var(--cp-soft)'; b.style.borderColor = 'var(--cp-primary-start)'; });
            b.addEventListener('mouseleave', () => { b.style.background = '#fff'; b.style.borderColor = 'var(--cp-soft-border)'; });
            b.addEventListener('click', () => {
                document.getElementById('cp-vram').value    = b.dataset.vram;
                document.getElementById('cp-cpu').value     = b.dataset.cpu;
                document.getElementById('cp-runtime').value = b.dataset.runtime;
                updatePrice();
            });
        });

        document.getElementById('cp-vram').addEventListener('change', updatePrice);
        document.getElementById('cp-cpu').addEventListener('change', updatePrice);
        document.getElementById('cp-runtime').addEventListener('change', updatePrice);

        document.getElementById('cp-create-btn').addEventListener('click', async () => {
            const runtime    = document.getElementById('cp-runtime').value;
            const vram       = parseFloat(document.getElementById('cp-vram').value);
            const cpu        = parseInt(document.getElementById('cp-cpu').value, 10);
            const price      = parseFloat(document.getElementById('cp-price').dataset.value || 0);
            const ssh        = document.getElementById('cp-ssh').checked;
            const persistent = document.getElementById('cp-persistent').checked;
            await API.create(runtime, { runtime, vram, cpu, price, ssh, persistent });
            await refresh();
        });

        updatePrice();
        refresh();
        setInterval(refresh, 5000);
    })();
    </script>

@endcomponent