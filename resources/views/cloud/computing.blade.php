@component('layouts.app')
    <div>
        <div class="cp-banner">
            <div style="position: relative; z-index:1;">
                <p>Cloud Computing</p>
                <h2>Kelola Instance Virtual</h2>
                <p>Pilih plan, buat instance, dan kelola siklus hidup VM Anda. (Demo frontend-only)</p>
            </div>
        </div>

        <div style="margin:1rem 0; display:grid; gap:1rem;">
            <div class="cp-card">
                <h3 class="cp-section-title">Pilih Plan</h3>
                <div style="display:grid; gap:0.6rem; grid-template-columns: 1fr 320px; align-items:start;">
                    <div>
                        <label style="display:block; font-weight:700; color:#7d9a77; margin-bottom:6px;">Runtime</label>
                        <select id="cp-runtime" style="width:100%; padding:8px; border-radius:8px; background:#05120b; color:#eaf6ea; border:1px solid rgba(255,255,255,0.04);">
                            <option value="micro">Micro (VM)</option>
                            <option value="small">Small (VM)</option>
                            <option value="medium">Medium (VM)</option>
                            <option value="large">Large (VM)</option>
                            <option value="jupyter">Jupyter (Notebook)</option>
                            <option value="code-server">IDE (code-server)</option>
                        </select>

                        <div style="display:flex; gap:0.6rem; margin-top:8px;">
                            <div style="flex:1;">
                                <label style="display:block; font-weight:700; color:#7d9a77; margin-bottom:6px;">vRAM (GB)</label>
                                <select id="cp-vram" style="width:100%; padding:8px; border-radius:8px; background:#05120b; color:#eaf6ea; border:1px solid rgba(255,255,255,0.04);">
                                    <option value="0.5">0.5</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="4">4</option>
                                    <option value="8">8</option>
                                </select>
                            </div>
                            <div style="width:120px;">
                                <label style="display:block; font-weight:700; color:#7d9a77; margin-bottom:6px;">vCPU</label>
                                <select id="cp-cpu" style="width:100%; padding:8px; border-radius:8px; background:#05120b; color:#eaf6ea; border:1px solid rgba(255,255,255,0.04);">
                                    <option value="10">1</option>
                                    <option value="50">1</option>
                                    <option value="100">2</option>
                                    <option value="200">4</option>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; gap:0.6rem; margin-top:8px; align-items:center;">
                            <button id="cp-create-btn" class="cp-btn" style="background:#8db96a;padding:10px 12px;font-size:0.95rem;">Buat Instance</button>
                            <div style="margin-left:auto; text-align:right;">
                                <div style="font-size:0.78rem; color:#b9d7a8; font-weight:700;">Estimasi Harga</div>
                                <div id="cp-price" style="font-weight:900; color:#fff; font-size:1.05rem;">Rp0/h</div>
                            </div>
                        </div>
                    </div>

                    <div style="background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); padding:10px; border-radius:8px;">
                        <div style="font-weight:800; color:#f2f7ef; margin-bottom:6px;">Presets</div>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <button class="cp-preset cp-btn" data-vram="0.5" data-cpu="10" data-runtime="micro">Micro — 0.5GB / 1 vCPU</button>
                            <button class="cp-preset cp-btn" data-vram="1" data-cpu="50" data-runtime="small">Small — 1GB / 1 vCPU</button>
                            <button class="cp-preset cp-btn" data-vram="2" data-cpu="100" data-runtime="medium">Medium — 2GB / 2 vCPU</button>
                            <button class="cp-preset cp-btn" data-vram="4" data-cpu="200" data-runtime="large">Large — 4GB / 4 vCPU</button>
                            <button class="cp-preset cp-btn" data-vram="1" data-cpu="100" data-runtime="jupyter">Jupyter — Notebook</button>
                            <button class="cp-preset cp-btn" data-vram="1" data-cpu="100" data-runtime="code-server">IDE — code-server</button>
                        </div>
                    </div>
                </div>
                <div style="margin-top:0.8rem; display:flex; gap:0.8rem; align-items:center;">
                    <label style="display:flex; gap:0.5rem; align-items:center; font-weight:700; color:#7d9a77;">
                        <input id="cp-ssh" type="checkbox" /> Enable SSH
                    </label>
                    <label style="display:flex; gap:0.5rem; align-items:center; font-weight:700; color:#7d9a77;">
                        <input id="cp-persistent" type="checkbox" /> Persistent Disk
                    </label>
                </div>
            </div>

            <div class="cp-card">
                <h3 class="cp-section-title">Instance Anda</h3>
                <div id="cp-instances-wrap" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:0.9rem;">
                    <!-- instances rendered here -->
                </div>
            </div>

            <div class="cp-card">
                <h3 class="cp-section-title">Pricing & Notes</h3>
                <p style="color:#6f8b69; font-weight:700;">Harga merupakan simulasi. Faktur aktual tidak akan dibuat di demo ini.</p>
                <ul>
                    <li>Biaya dihitung per jam (estimasi).</li>
                    <li>Operasi provisioning bersifat asinkron pada implementasi nyata.</li>
                    <li>Untuk produksi, integrasikan dengan Ministack API dan queue worker.</li>
                </ul>
            </div>
        </div>

    <template id="cp-instance-template">
        <div class="cp-auth-card" style="padding:12px; border-radius:12px; background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02)); box-shadow:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
                <div>
                    <div style="font-weight:800; font-size:1rem; color:#f2f7ef;">__NAME__</div>
                    <div style="font-size:0.78rem; color:#b9d7a8; font-weight:700;">__PLAN__</div>
                </div>
                <div style="text-align:right">
                    <div class="cp-chip" data-status="__STATUS__">__STATUS__</div>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem; margin-top:0.8rem;">
                <button class="cp-action-start cp-btn" style="background:#8db96a;padding:8px 10px;font-size:0.85rem;">Start</button>
                <button class="cp-action-stop cp-btn" style="background:#c9c9c9;color:#333;padding:8px 10px;font-size:0.85rem;">Stop</button>
                <button class="cp-action-term cp-btn" style="background:#f97373;padding:8px 10px;font-size:0.85rem;">Terminate</button>
            </div>
        </div>
    </template>

    <!-- Confirmation modal -->
    <div id="cp-confirm-modal" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; z-index:9999;">
        <div style="position:absolute; inset:0; background:rgba(2,6,23,0.6);"></div>
        <div style="position:relative; background:#062017; color:#f1f9f3; padding:20px; border-radius:12px; width:400px; max-width:92%; box-shadow:0 10px 40px rgba(2,6,23,0.7);">
            <div id="cp-confirm-message" style="font-weight:800; margin-bottom:14px; font-size:1rem; color:#ffffff;">Konfirmasi</div>
            <div style="font-size:0.95rem; color:#cfe7d1; margin-bottom:14px;">Apakah Anda yakin ingin menghapus instance ini? Tindakan ini tidak dapat dibatalkan.</div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button id="cp-confirm-cancel" class="cp-btn" style="background:#d1d5db;color:#0b1220;padding:9px 14px;border-radius:8px;">Batal</button>
                <button id="cp-confirm-ok" class="cp-btn" style="background:#dc2626;padding:9px 14px;color:#ffffff;border-radius:8px;">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const API = {
                list: async () => fetch('{{ route('cloud.api.instances') }}', { credentials: 'same-origin' }).then(r=>r.ok? r.json(): [] ).catch(()=>[]),
                create: async (plan, opts = {}) => fetch('{{ route('cloud.api.instances.store') }}', { method:'POST', headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: JSON.stringify(Object.assign({plan}, opts)), credentials: 'same-origin' }).then(r=>r.json()),
                action: async (id, action) => fetch(`/cloud/api/instances/${id}/action`, { method:'POST', headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: JSON.stringify({action}), credentials: 'same-origin' }).then(r=>r.json()),
            };

            // modal helper
            function showConfirm(message, onConfirm) {
                const modal = document.getElementById('cp-confirm-modal');
                const msg = document.getElementById('cp-confirm-message');
                const ok = document.getElementById('cp-confirm-ok');
                const cancel = document.getElementById('cp-confirm-cancel');
                msg.innerText = message;
                modal.style.display = 'flex';
                function cleanup() {
                    modal.style.display = 'none';
                    ok.removeEventListener('click', okHandler);
                    cancel.removeEventListener('click', cancelHandler);
                }
                function okHandler() { cleanup(); onConfirm(); }
                function cancelHandler() { cleanup(); }
                ok.addEventListener('click', okHandler);
                cancel.addEventListener('click', cancelHandler);
            }

            function renderItems(items){
                const wrap = document.getElementById('cp-instances-wrap');
                wrap.innerHTML='';
                if(!items || items.length===0){ wrap.innerHTML = '<div style="grid-column:1/-1;color:#7d9a77;font-weight:700;padding:1rem;">Belum ada instance. Pilih plan dan buat instance.</div>'; return; }
                const tpl = document.getElementById('cp-instance-template').innerHTML;
                items.forEach(it=>{
                    const html = tpl.replace(/__NAME__/g,it.name).replace('__PLAN__',it.plan).replace(/__STATUS__/g,it.status);
                    const el = document.createElement('div'); el.innerHTML = html; const node = el.firstElementChild;
                    node.querySelector('.cp-action-start').addEventListener('click', ()=>{ API.action(it.id,'start').then(()=>refresh()); });
                    node.querySelector('.cp-action-stop').addEventListener('click', ()=>{ API.action(it.id,'stop').then(()=>refresh()); });
                    node.querySelector('.cp-action-term').addEventListener('click', ()=>{ showConfirm('Yakin ingin menghapus instance ini?', ()=>{ API.action(it.id,'terminate').then(()=>refresh()); }); });
                    // show SSH and volume info if present
                    if (it.metadata && it.metadata.ssh_host_port) {
                        const ssh = document.createElement('div');
                        ssh.style.marginTop = '8px';
                        ssh.style.fontSize = '0.78rem';
                        ssh.style.color = '#cfe7c9';
                        ssh.innerText = `SSH: ssh root@localhost -p ${it.metadata.ssh_host_port}`;
                        node.appendChild(ssh);
                    }
                    if (it.metadata && it.metadata.volumePath) {
                        const vol = document.createElement('div');
                        vol.style.marginTop = '6px';
                        vol.style.fontSize = '0.78rem';
                        vol.style.color = '#b9d7a8';
                        vol.innerText = `Disk: ${it.metadata.volumePath}`;
                        node.appendChild(vol);
                    }
                    // show requested configuration
                    if (it.metadata && it.metadata.requested) {
                        const req = it.metadata.requested;
                        const cfg = document.createElement('div');
                        cfg.style.marginTop = '8px';
                        cfg.style.fontSize = '0.78rem';
                        cfg.style.color = '#cfe7c9';
                        const parts = [];
                        if (req.runtime) parts.push(req.runtime);
                        if (req.vram_gb) parts.push(req.vram_gb + 'GB RAM');
                        if (req.cpu) parts.push(req.cpu + ' CPU');
                        if (req.size) parts.push(req.size);
                        if (req.price) parts.push('Rp' + req.price + '/h');
                        cfg.innerText = parts.join(' • ');
                        node.appendChild(cfg);
                    }
                    if (it.metadata && it.metadata.jupyter_host_port && it.metadata.jupyter_token) {
                        const link = document.createElement('a');
                        link.style.display = 'inline-block';
                        link.style.marginTop = '8px';
                        link.style.marginRight = '8px';
                        link.style.fontSize = '0.9rem';
                        link.style.color = '#9fe6b0';
                        link.style.textDecoration = 'underline';
                        link.setAttribute('target','_blank');
                        link.href = `http://localhost:${it.metadata.jupyter_host_port}/?token=${it.metadata.jupyter_token}`;
                        link.innerText = `Buka Jupyter (:${it.metadata.jupyter_host_port})`;
                        node.appendChild(link);

                        // Auto-open once when instance first becomes RUNNING in this browser session
                        try {
                            const key = 'cloudpet_opened_' + it.id;
                            if (it.status === 'RUNNING' && !sessionStorage.getItem(key)) {
                                window.open(link.href, '_blank');
                                sessionStorage.setItem(key, '1');
                            }
                        } catch (e) { /* ignore sessionStorage errors */ }
                    }
                    if (it.metadata && it.metadata.codeserver_host_port && it.metadata.codeserver_password) {
                        const link2 = document.createElement('a');
                        link2.style.display = 'inline-block';
                        link2.style.marginTop = '6px';
                        link2.style.marginRight = '8px';
                        link2.style.fontSize = '0.9rem';
                        link2.style.color = '#9fe6b0';
                        link2.setAttribute('target','_blank');
                        link2.href = `http://localhost:${it.metadata.codeserver_host_port}`;
                        link2.innerText = `Buka IDE (:${it.metadata.codeserver_host_port})`;
                        node.appendChild(link2);

                        const info = document.createElement('div');
                        info.style.display = 'inline-block';
                        info.style.marginTop = '6px';
                        info.style.fontSize = '0.78rem';
                        info.style.color = '#b9d7a8';
                        info.innerText = `(password: ${it.metadata.codeserver_password})`;
                        node.appendChild(info);

                        // Auto-open once when instance first becomes RUNNING in this browser session
                        try {
                            const key2 = 'cloudpet_opened_' + it.id;
                            if (it.status === 'RUNNING' && !sessionStorage.getItem(key2)) {
                                window.open(link2.href, '_blank');
                                sessionStorage.setItem(key2, '1');
                            }
                        } catch (e) { /* ignore sessionStorage errors */ }
                    }
                    wrap.appendChild(node);
                });
            }

            async function refresh(){
                const items = await API.list(); renderItems(items);
            }

            // Pricing model (simple): cpuUnit * rate + vramGB * rate
            const CPU_RATE = 4; // Rp per cpu unit
            const VRAM_RATE = 250; // Rp per GB

            function formatRp(n){ return 'Rp' + n.toLocaleString('id-ID') + '/h'; }

            function calculatePrice(cpu, vramGb){
                const price = Math.round((cpu * CPU_RATE) + (vramGb * VRAM_RATE));
                return price;
            }

            function updatePriceDisplay(){
                const cpu = parseInt(document.getElementById('cp-cpu').value || '0',10);
                const vram = parseFloat(document.getElementById('cp-vram').value || '0');
                const price = calculatePrice(cpu, vram);
                document.getElementById('cp-price').innerText = formatRp(price);
                // store on element for create
                document.getElementById('cp-price').dataset.value = price;
            }

            // Preset buttons behavior
            document.querySelectorAll('.cp-preset').forEach(b=>{
                b.addEventListener('click', ()=>{
                    const vram = b.dataset.vram;
                    const cpu = b.dataset.cpu;
                    const runtime = b.dataset.runtime;
                    document.getElementById('cp-vram').value = vram;
                    document.getElementById('cp-cpu').value = cpu;
                    document.getElementById('cp-runtime').value = runtime;
                    updatePriceDisplay();
                });
            });

            document.getElementById('cp-vram').addEventListener('change', updatePriceDisplay);
            document.getElementById('cp-cpu').addEventListener('change', updatePriceDisplay);
            document.getElementById('cp-runtime').addEventListener('change', updatePriceDisplay);

            document.getElementById('cp-create-btn').addEventListener('click', async ()=>{
                const runtime = document.getElementById('cp-runtime').value;
                const vram = parseFloat(document.getElementById('cp-vram').value);
                const cpu = parseInt(document.getElementById('cp-cpu').value,10);
                const price = parseFloat(document.getElementById('cp-price').dataset.value || 0);
                const ssh = document.getElementById('cp-ssh').checked;
                const persistent = document.getElementById('cp-persistent').checked;
                // use runtime as the 'plan' identifier for now
                await API.create(runtime, { runtime, vram, cpu, price, ssh, persistent });
                await refresh();
            });

            // initial setup
            updatePriceDisplay();
            // initial load
            refresh();
            // poll status every 5 seconds
            setInterval(refresh, 5000);
        })();
    </script>

@endcomponent
