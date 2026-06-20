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
                <div style="display:flex; gap:0.8rem; flex-wrap:wrap;">
                    <button class="cp-plan-btn cp-btn" data-plan="micro">Micro<br><small>1 vCPU • 512MB • Rp0/h</small></button>
                    <button class="cp-plan-btn cp-btn" data-plan="small">Small<br><small>1 vCPU • 1GB • Rp500/h</small></button>
                    <button class="cp-plan-btn cp-btn" data-plan="medium">Medium<br><small>2 vCPU • 2GB • Rp900/h</small></button>
                    <button class="cp-plan-btn cp-btn" data-plan="large">Large<br><small>4 vCPU • 4GB • Rp1700/h</small></button>
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

    <script>
        (function(){
            const API = {
                list: async () => fetch('{{ route('cloud.api.instances') }}', { credentials: 'same-origin' }).then(r=>r.ok? r.json(): [] ).catch(()=>[]),
                create: async (plan) => fetch('{{ route('cloud.api.instances.store') }}', { method:'POST', headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: JSON.stringify({plan}), credentials: 'same-origin' }).then(r=>r.json()),
                action: async (id, action) => fetch(`/cloud/api/instances/${id}/action`, { method:'POST', headers: { 'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: JSON.stringify({action}), credentials: 'same-origin' }).then(r=>r.json()),
            };

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
                    node.querySelector('.cp-action-term').addEventListener('click', ()=>{ if(confirm('Terminate instance?')){ API.action(it.id,'terminate').then(()=>refresh()); } });
                    wrap.appendChild(node);
                });
            }

            async function refresh(){
                const items = await API.list(); renderItems(items);
            }

            document.querySelectorAll('.cp-plan-btn').forEach(b=>{ b.addEventListener('click', async ()=>{ const plan = b.dataset.plan; await API.create(plan); await refresh(); }); });

            // initial load
            refresh();
        })();
    </script>

@endcomponent
