@component('layouts.app')
<div class="cp-page">
    <div class="cp-banner" style="margin-bottom:1rem;">
        <div style="position:relative;z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">Administration</p>
            <h2>Kelola Plans (Paket Layanan)</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">Kelola paket layanan Database. Compute instance menggunakan resource custom (vCPU + RAM dipilih user).</p>
        </div>
    </div>

    <div class="cp-card" style="margin-bottom:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 class="cp-section-title" style="margin:0;">Semua Plans</h3>
            <button onclick="showCreateForm()" class="cp-btn" style="width:auto;padding:8px 18px;font-size:0.82rem;border-radius:0.7rem;">+ Tambah Plan</button>
        </div>
        <div class="cp-table-wrap">
            <table class="cp-table" id="plans-table">
                <thead>
                    <tr><th>Tipe</th><th>Nama</th><th>vCPU</th><th>RAM</th><th>Storage</th><th>Harga/jam</th><th style="text-align:right;">Aksi</th></tr>
                </thead>
                <tbody id="plans-body"><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--cp-ink-muted);">Memuat...</td></tr></tbody>
            </table>
        </div>
    </div>

    {{-- Create/Edit Form --}}
    <div class="cp-card" id="plan-form" style="display:none;">
        <h3 class="cp-section-title" style="margin-top:0;" id="form-title">Tambah Plan Baru</h3>
        <input type="hidden" id="f-id">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);display:block;margin-bottom:4px;">Tipe Layanan</label>
                <select id="f-type" style="width:100%;padding:8px;border:1px solid var(--cp-soft-border);border-radius:0.5rem;font-size:0.82rem;">
                    <option value="DATABASE">DATABASE</option><option value="STORAGE">STORAGE</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);display:block;margin-bottom:4px;">Nama</label>
                <input id="f-name" type="text" placeholder="db-micro" style="width:100%;padding:8px;border:1px solid var(--cp-soft-border);border-radius:0.5rem;font-size:0.82rem;">
            </div>
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);display:block;margin-bottom:4px;">Harga/jam (Rp)</label>
                <input id="f-price" type="number" min="0" style="width:100%;padding:8px;border:1px solid var(--cp-soft-border);border-radius:0.5rem;font-size:0.82rem;">
            </div>
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);display:block;margin-bottom:4px;">vCPU</label>
                <input id="f-vcpu" type="number" min="0" style="width:100%;padding:8px;border:1px solid var(--cp-soft-border);border-radius:0.5rem;font-size:0.82rem;">
            </div>
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);display:block;margin-bottom:4px;">RAM (MB)</label>
                <input id="f-ram" type="number" min="0" style="width:100%;padding:8px;border:1px solid var(--cp-soft-border);border-radius:0.5rem;font-size:0.82rem;">
            </div>
            <div>
                <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);display:block;margin-bottom:4px;">Storage (GB)</label>
                <input id="f-storage" type="number" min="0" style="width:100%;padding:8px;border:1px solid var(--cp-soft-border);border-radius:0.5rem;font-size:0.82rem;">
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="savePlan()" class="cp-btn" style="width:auto;padding:8px 18px;font-size:0.82rem;border-radius:0.7rem;">Simpan</button>
            <button onclick="hideForm()" style="padding:8px 18px;border-radius:0.7rem;border:1px solid var(--cp-soft-border);background:#fff;color:var(--cp-ink);font-size:0.82rem;font-weight:700;cursor:pointer;">Batal</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:navigated', () => {
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const h = {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF};

async function loadPlans() {
    const res = await fetch('/admin/api/plans', {credentials:'same-origin'});
    const plans = await res.json();
    const body = document.getElementById('plans-body');
    if (!plans.length) { body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--cp-ink-muted);">Belum ada plan.</td></tr>'; return; }
    body.innerHTML = plans.map(p => `<tr>
        <td><span style="font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--cp-soft);color:var(--cp-ink-muted);">${p.service_type}</span></td>
        <td style="font-weight:800;color:var(--cp-ink);">${p.name}</td>
        <td>${p.vcpu ?? '—'}</td><td>${p.ram ? (p.ram>=1024?p.ram/1024+' GB':p.ram+' MB') : '—'}</td><td>${p.storage ? p.storage+' GB' : '—'}</td>
        <td style="font-weight:700;">Rp ${parseInt(p.price).toLocaleString('id-ID')}</td>
        <td style="text-align:right;">
            <button onclick='editPlan(${JSON.stringify(p).replace(/'/g,"&#39;")})' style="padding:4px 10px;border-radius:0.5rem;border:1px solid #b8d4f0;background:#e8f2fb;color:#2b5fa0;font-size:0.72rem;font-weight:700;cursor:pointer;">Edit</button>
            <button onclick="deletePlan('${p.id}')" style="padding:4px 10px;border-radius:0.5rem;border:1px solid #f5c6c6;background:#fde8e8;color:#9b2c2c;font-size:0.72rem;font-weight:700;cursor:pointer;">Hapus</button>
        </td>
    </tr>`).join('');
}

function showCreateForm() {
    document.getElementById('form-title').innerText = 'Tambah Plan Baru';
    document.getElementById('f-id').value = '';
    ['f-name','f-vcpu','f-ram','f-storage','f-price'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f-type').value = 'DATABASE';
    document.getElementById('plan-form').style.display = 'block';
    document.getElementById('plan-form').scrollIntoView({behavior:'smooth'});
}

function editPlan(p) {
    document.getElementById('form-title').innerText = 'Edit Plan: ' + p.name;
    document.getElementById('f-id').value = p.id;
    document.getElementById('f-type').value = p.service_type;
    document.getElementById('f-name').value = p.name;
    document.getElementById('f-vcpu').value = p.vcpu || '';
    document.getElementById('f-ram').value = p.ram || '';
    document.getElementById('f-storage').value = p.storage || '';
    document.getElementById('f-price').value = parseInt(p.price);
    document.getElementById('plan-form').style.display = 'block';
    document.getElementById('plan-form').scrollIntoView({behavior:'smooth'});
}

function hideForm() { document.getElementById('plan-form').style.display = 'none'; }

async function savePlan() {
    const id = document.getElementById('f-id').value;
    const data = {
        service_type: document.getElementById('f-type').value,
        name: document.getElementById('f-name').value,
        vcpu: parseInt(document.getElementById('f-vcpu').value) || null,
        ram: parseInt(document.getElementById('f-ram').value) || null,
        storage: parseInt(document.getElementById('f-storage').value) || null,
        price: parseFloat(document.getElementById('f-price').value) || 0,
    };
    const url = id ? '/admin/api/plans/' + id : '/admin/api/plans';
    const method = id ? 'PUT' : 'POST';
    const res = await fetch(url, { method, headers:h, body:JSON.stringify(data), credentials:'same-origin' });
    const result = await res.json();
    if (result.error) { alert(result.error); return; }
    hideForm(); loadPlans();
}

async function deletePlan(id) {
    if (!confirm('Hapus plan ini?')) return;
    const res = await fetch('/admin/api/plans/' + id, { method:'DELETE', headers:h, credentials:'same-origin' });
    const result = await res.json();
    if (result.error) { alert(result.error); return; }
    loadPlans();
}

loadPlans();
});
</script>
@endcomponent
