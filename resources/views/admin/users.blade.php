@component('layouts.app')
<div class="cp-page">
    <div class="cp-banner" style="margin-bottom:1rem;">
        <div style="position:relative;z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">Administration</p>
            <h2>Users & Billing</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">Kelola pengguna, saldo, status akun, dan riwayat transaksi.</p>
        </div>
    </div>

    {{-- Search + Filter --}}
    <div class="cp-card" style="margin-bottom:1rem;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <input id="u-search" type="text" placeholder="Cari nama atau email..." oninput="debounceLoad()" style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.82rem;color:var(--cp-ink);">
            <select id="u-role" onchange="loadUsers()" style="padding:8px 12px;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.82rem;">
                <option value="all">Semua Role</option><option value="user">User</option><option value="admin">Admin</option>
            </select>
        </div>
    </div>

    {{-- User table --}}
    <div class="cp-card" style="margin-bottom:1rem;">
        <div class="cp-table-wrap">
            <table class="cp-table">
                <thead><tr><th>User</th><th>Status</th><th>Membership</th><th>Saldo</th><th>Resource</th><th style="text-align:right;">Aksi</th></tr></thead>
                <tbody id="users-body"><tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--cp-ink-muted);">Memuat...</td></tr></tbody>
            </table>
        </div>
        <div id="users-pagination" style="display:flex;gap:6px;justify-content:center;margin-top:12px;"></div>
    </div>

    {{-- User Detail Panel (hidden) --}}
    <div class="cp-card" id="user-detail" style="display:none;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 class="cp-section-title" style="margin:0;" id="detail-title">Detail User</h3>
            <button onclick="document.getElementById('user-detail').style.display='none'" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:var(--cp-ink-muted);">✕</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            {{-- Left: Info + Actions --}}
            <div>
                <div id="detail-info" style="display:grid;gap:8px;margin-bottom:14px;"></div>
                <div style="border-top:1px solid var(--cp-soft-border);padding-top:14px;">
                    <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);margin-bottom:8px;">Admin Actions</div>
                    <div style="display:grid;gap:8px;">
                        <div style="display:flex;gap:8px;align-items:center;">
                            <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);width:100px;">Adjust Saldo</label>
                            <input id="adj-amount" type="number" placeholder="e.g. 50000 atau -10000" style="flex:1;padding:6px 8px;border:1px solid var(--cp-soft-border);border-radius:0.4rem;font-size:0.8rem;">
                            <button onclick="adjustBalance()" style="padding:6px 12px;border-radius:0.5rem;border:1px solid #c6e0a8;background:#eaf4dd;color:#3b5136;font-size:0.72rem;font-weight:700;cursor:pointer;">Apply</button>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);width:100px;">Status</label>
                            <button onclick="setStatus('ACTIVE')" style="padding:4px 12px;border-radius:0.5rem;border:1px solid #c6e0a8;background:#eaf4dd;color:#3b5136;font-size:0.72rem;font-weight:700;cursor:pointer;">ACTIVE</button>
                            <button onclick="setStatus('SUSPENDED')" style="padding:4px 12px;border-radius:0.5rem;border:1px solid #f5c6c6;background:#fde8e8;color:#9b2c2c;font-size:0.72rem;font-weight:700;cursor:pointer;">SUSPEND</button>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <label style="font-size:0.72rem;font-weight:700;color:var(--cp-ink-muted);width:100px;">Membership</label>
                            <select id="adj-plan" style="padding:6px;border:1px solid var(--cp-soft-border);border-radius:0.4rem;font-size:0.8rem;">
                                <option value="free">Free</option><option value="starter">Starter</option><option value="pro">Pro</option><option value="business">Business</option>
                            </select>
                            <button onclick="setPlan()" style="padding:4px 12px;border-radius:0.5rem;border:1px solid #b8d4f0;background:#e8f2fb;color:#2b5fa0;font-size:0.72rem;font-weight:700;cursor:pointer;">Set</button>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Right: Transactions --}}
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);margin-bottom:8px;">Riwayat Transaksi</div>
                <div id="detail-tx" style="display:grid;gap:4px;max-height:300px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:navigated', () => {
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const h = {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF};
let currentPage = 1, selectedUserId = null, debounceTimer = null;

function debounceLoad() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { currentPage=1; loadUsers(); }, 400); }

async function loadUsers(page) {
    if (page) currentPage = page;
    const search = document.getElementById('u-search').value;
    const role = document.getElementById('u-role').value;
    const res = await fetch(`/admin/api/users?page=${currentPage}&search=${encodeURIComponent(search)}&role=${role}`, {credentials:'same-origin'});
    const data = await res.json();
    const body = document.getElementById('users-body');

    if (!data.data || !data.data.length) { body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--cp-ink-muted);">Tidak ada user.</td></tr>'; return; }

    body.innerHTML = data.data.map(u => {
        const st = u.account_status === 'ACTIVE' ? 'background:#eaf4dd;color:#3b5136;' : 'background:#fde8e8;color:#9b2c2c;';
        return `<tr>
            <td><div style="font-weight:800;color:var(--cp-ink);">${u.animal_avatar||''} ${u.name}</div><div style="font-size:0.72rem;color:var(--cp-ink-muted);">${u.email}</div></td>
            <td><span style="${st}font-size:0.68rem;font-weight:700;padding:2px 8px;border-radius:999px;">${u.account_status||'ACTIVE'}</span></td>
            <td style="font-size:0.78rem;font-weight:700;">${u.storage_plan||'free'}</td>
            <td style="font-weight:700;">Rp ${parseFloat(u.balance||0).toLocaleString('id-ID')}</td>
            <td style="font-size:0.72rem;color:var(--cp-ink-muted);">—</td>
            <td style="text-align:right;"><button onclick="showDetail('${u.id}')" style="padding:4px 12px;border-radius:0.5rem;border:1px solid #b8d4f0;background:#e8f2fb;color:#2b5fa0;font-size:0.72rem;font-weight:700;cursor:pointer;">Detail</button></td>
        </tr>`;
    }).join('');

    // Pagination
    const pg = document.getElementById('users-pagination');
    if (data.last_page > 1) {
        pg.innerHTML = Array.from({length: data.last_page}, (_, i) => i+1).map(p =>
            `<button onclick="loadUsers(${p})" style="padding:4px 10px;border-radius:0.5rem;border:1px solid ${p===data.current_page?'var(--cp-primary-start)':'var(--cp-soft-border)'};background:${p===data.current_page?'var(--cp-soft)':'#fff'};font-size:0.78rem;font-weight:700;cursor:pointer;color:var(--cp-ink);">${p}</button>`
        ).join('');
    } else { pg.innerHTML = ''; }
}

async function showDetail(id) {
    selectedUserId = id;
    const res = await fetch('/admin/api/users/' + id, {credentials:'same-origin'});
    const d = await res.json();
    const u = d.user;

    document.getElementById('detail-title').innerText = u.animal_avatar + ' ' + u.name;
    document.getElementById('adj-plan').value = u.storage_plan || 'free';

    document.getElementById('detail-info').innerHTML = [
        ['Email', u.email], ['Role', u.role], ['Status', u.account_status],
        ['Saldo', 'Rp ' + parseFloat(u.balance).toLocaleString('id-ID')],
        ['Membership', u.storage_plan || 'free'],
        ['Instance', d.instances], ['Database', d.databases], ['Volume', d.volumes], ['Bucket', d.buckets],
    ].map(([l,v]) => `<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--cp-soft-border);"><span style="font-size:0.78rem;color:var(--cp-ink-muted);font-weight:700;">${l}</span><span style="font-size:0.78rem;font-weight:800;color:var(--cp-ink);">${v}</span></div>`).join('');

    document.getElementById('detail-tx').innerHTML = (d.transactions||[]).map(tx => {
        const isDebit = parseFloat(tx.amount) < 0;
        const date = new Date(tx.created_at).toLocaleString('id-ID', {day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
        return `<div style="display:flex;justify-content:space-between;padding:6px 8px;background:var(--cp-soft);border-radius:0.5rem;font-size:0.75rem;">
            <div><div style="font-weight:700;color:var(--cp-ink);">${tx.description||tx.transaction_type}</div><div style="color:var(--cp-ink-muted);">${date}</div></div>
            <span style="font-weight:800;color:${isDebit?'#9b2c2c':'#3b5136'};">${isDebit?'−':'+'} Rp ${Math.abs(parseFloat(tx.amount)).toLocaleString('id-ID')}</span>
        </div>`;
    }).join('') || '<div style="text-align:center;color:var(--cp-ink-muted);padding:1rem;font-size:0.78rem;">Belum ada transaksi.</div>';

    document.getElementById('user-detail').style.display = 'block';
    document.getElementById('user-detail').scrollIntoView({behavior:'smooth'});
}

async function adjustBalance() {
    const amount = parseFloat(document.getElementById('adj-amount').value);
    if (!amount) return;
    await fetch('/admin/api/users/' + selectedUserId, { method:'PUT', headers:h, body:JSON.stringify({balance_adjust:amount}), credentials:'same-origin' });
    document.getElementById('adj-amount').value = '';
    showDetail(selectedUserId); loadUsers();
}

async function setStatus(status) {
    await fetch('/admin/api/users/' + selectedUserId, { method:'PUT', headers:h, body:JSON.stringify({account_status:status}), credentials:'same-origin' });
    showDetail(selectedUserId); loadUsers();
}

async function setPlan() {
    const plan = document.getElementById('adj-plan').value;
    await fetch('/admin/api/users/' + selectedUserId, { method:'PUT', headers:h, body:JSON.stringify({storage_plan:plan}), credentials:'same-origin' });
    showDetail(selectedUserId); loadUsers();
}

loadUsers();
});
</script>
@endcomponent
