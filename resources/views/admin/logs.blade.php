@component('layouts.app')
<div class="cp-page">
    <div class="cp-banner" style="margin-bottom:1rem;">
        <div style="position:relative;z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">Monitoring</p>
            <h2>Logs & Monitoring</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">Activity logs, resource state changes, dan system error logs.</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex;gap:6px;margin-bottom:1rem;">
        <button onclick="switchTab('activity')" id="tab-activity" class="cp-btn" style="width:auto;padding:8px 16px;font-size:0.82rem;border-radius:0.7rem;">Activity Logs</button>
        <button onclick="switchTab('resource')" id="tab-resource" style="padding:8px 16px;border-radius:0.7rem;border:1px solid var(--cp-soft-border);background:#fff;font-size:0.82rem;font-weight:700;color:var(--cp-ink-muted);cursor:pointer;">Resource State</button>
        <button onclick="switchTab('errors')" id="tab-errors" style="padding:8px 16px;border-radius:0.7rem;border:1px solid var(--cp-soft-border);background:#fff;font-size:0.82rem;font-weight:700;color:var(--cp-ink-muted);cursor:pointer;">System Errors</button>
    </div>

    {{-- Search --}}
    <div class="cp-card" style="margin-bottom:1rem;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <input id="log-search" type="text" placeholder="Cari..." oninput="debounceLog()" style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.82rem;color:var(--cp-ink);">
            <select id="log-filter" onchange="loadCurrentTab()" style="padding:8px 12px;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.82rem;display:none;">
                <option value="all">Semua Level</option><option value="ERROR">ERROR</option><option value="WARNING">WARNING</option><option value="INFO">INFO</option>
            </select>
            <select id="log-resource-type" onchange="loadCurrentTab()" style="padding:8px 12px;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.82rem;display:none;">
                <option value="">Semua Resource</option><option value="compute_instance">Compute</option><option value="block_volume">Volume</option><option value="managed_database">Database</option>
            </select>
        </div>
    </div>

    {{-- Content --}}
    <div class="cp-card">
        <div id="log-content" style="display:grid;gap:6px;max-height:500px;overflow-y:auto;">
            <div style="text-align:center;color:var(--cp-ink-muted);padding:2rem;">Memuat...</div>
        </div>
        <div id="log-pagination" style="display:flex;gap:6px;justify-content:center;margin-top:12px;"></div>
    </div>
</div>

<script>
document.addEventListener('livewire:navigated', () => {
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let currentLogTab = 'activity', logPage = 1, logDebounce = null;

const tabActiveStyle = 'width:auto;padding:8px 16px;font-size:0.82rem;border-radius:0.7rem;';
const tabInactiveStyle = 'padding:8px 16px;border-radius:0.7rem;border:1px solid var(--cp-soft-border);background:#fff;font-size:0.82rem;font-weight:700;color:var(--cp-ink-muted);cursor:pointer;';

function debounceLog() { clearTimeout(logDebounce); logDebounce = setTimeout(() => { logPage=1; loadCurrentTab(); }, 400); }

function switchTab(tab) {
    currentLogTab = tab; logPage = 1;
    document.getElementById('tab-activity').className = tab==='activity' ? 'cp-btn' : '';
    document.getElementById('tab-activity').style.cssText = tab==='activity' ? tabActiveStyle : tabInactiveStyle;
    document.getElementById('tab-resource').style.cssText = tab==='resource' ? tabActiveStyle : tabInactiveStyle;
    document.getElementById('tab-resource').className = tab==='resource' ? 'cp-btn' : '';
    document.getElementById('tab-errors').style.cssText = tab==='errors' ? tabActiveStyle : tabInactiveStyle;
    document.getElementById('tab-errors').className = tab==='errors' ? 'cp-btn' : '';

    document.getElementById('log-filter').style.display = tab==='errors' ? 'block' : 'none';
    document.getElementById('log-resource-type').style.display = tab==='resource' ? 'block' : 'none';
    loadCurrentTab();
}

function loadCurrentTab() {
    if (currentLogTab === 'activity') loadActivity();
    else if (currentLogTab === 'resource') loadResourceState();
    else loadErrors();
}

async function loadActivity(page) {
    if (page) logPage = page;
    const search = document.getElementById('log-search').value;
    const res = await fetch(`/admin/api/logs/activity?page=${logPage}&search=${encodeURIComponent(search)}`, {credentials:'same-origin'});
    const data = await res.json();
    const wrap = document.getElementById('log-content');

    if (!data.data || !data.data.length) { wrap.innerHTML = '<div style="text-align:center;color:var(--cp-ink-muted);padding:2rem;">Tidak ada activity log.</div>'; renderPagination(data); return; }

    wrap.innerHTML = data.data.map(l => {
        const date = new Date(l.created_at).toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});
        return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--cp-soft);border-radius:0.6rem;">
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);">${l.action || '—'}</div>
                <div style="font-size:0.68rem;color:var(--cp-ink-muted);">${l.user?.name || 'System'} · ${l.resource_type || ''} · ${l.ip_address || ''}</div>
            </div>
            <div style="font-size:0.68rem;color:var(--cp-ink-muted);white-space:nowrap;">${date}</div>
        </div>`;
    }).join('');
    renderPagination(data);
}

async function loadResourceState(page) {
    if (page) logPage = page;
    const search = document.getElementById('log-search').value;
    const resourceType = document.getElementById('log-resource-type').value;
    const res = await fetch(`/admin/api/logs/resource-state?page=${logPage}&search=${encodeURIComponent(search)}&resource_type=${resourceType}`, {credentials:'same-origin'});
    const data = await res.json();
    const wrap = document.getElementById('log-content');

    if (!data.data || !data.data.length) { wrap.innerHTML = '<div style="text-align:center;color:var(--cp-ink-muted);padding:2rem;">Tidak ada resource state log.</div>'; renderPagination(data); return; }

    wrap.innerHTML = data.data.map(l => {
        const date = new Date(l.created_at).toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});
        return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--cp-soft);border-radius:0.6rem;">
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--cp-ink);">
                    <span style="font-size:0.68rem;padding:2px 6px;border-radius:999px;background:#fef3c7;color:#92400e;margin-right:4px;">${l.old_state||'—'}</span>
                    →
                    <span style="font-size:0.68rem;padding:2px 6px;border-radius:999px;background:#eaf4dd;color:#3b5136;margin-left:4px;">${l.new_state||'—'}</span>
                </div>
                <div style="font-size:0.68rem;color:var(--cp-ink-muted);margin-top:2px;">${l.resource_type} · ${l.message || ''}</div>
            </div>
            <div style="font-size:0.68rem;color:var(--cp-ink-muted);white-space:nowrap;">${date}</div>
        </div>`;
    }).join('');
    renderPagination(data);
}

async function loadErrors(page) {
    if (page) logPage = page;
    const search = document.getElementById('log-search').value;
    const level = document.getElementById('log-filter').value;
    const res = await fetch(`/admin/api/logs/errors?page=${logPage}&search=${encodeURIComponent(search)}&level=${level}`, {credentials:'same-origin'});
    const data = await res.json();
    const wrap = document.getElementById('log-content');

    if (!data.lines || !data.lines.length) { wrap.innerHTML = '<div style="text-align:center;color:var(--cp-ink-muted);padding:2rem;">Tidak ada error log.</div>'; document.getElementById('log-pagination').innerHTML=''; return; }

    const levelColors = { ERROR:'background:#fde8e8;color:#9b2c2c;', WARNING:'background:#fef3c7;color:#92400e;', INFO:'background:#eaf4dd;color:#3b5136;', DEBUG:'background:#f3f4f6;color:#6b7280;', CRITICAL:'background:#7f1d1d;color:#fff;' };

    wrap.innerHTML = data.lines.map(l => `
        <div style="padding:8px 12px;background:var(--cp-soft);border-radius:0.6rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <span style="font-size:0.65rem;font-weight:700;padding:2px 6px;border-radius:999px;${levelColors[l.level]||levelColors.DEBUG}">${l.level||'LOG'}</span>
                <span style="font-size:0.68rem;color:var(--cp-ink-muted);">${l.date}</span>
            </div>
            <pre style="font-size:0.72rem;color:var(--cp-ink);margin:0;white-space:pre-wrap;word-break:break-all;max-height:120px;overflow-y:auto;">${l.message}</pre>
        </div>
    `).join('');

    // Custom pagination for error logs
    const pg = document.getElementById('log-pagination');
    if (data.last_page > 1) {
        pg.innerHTML = Array.from({length: Math.min(data.last_page, 10)}, (_, i) => i+1).map(p =>
            `<button onclick="loadErrors(${p})" style="padding:4px 10px;border-radius:0.5rem;border:1px solid ${p===data.page?'var(--cp-primary-start)':'var(--cp-soft-border)'};background:${p===data.page?'var(--cp-soft)':'#fff'};font-size:0.78rem;font-weight:700;cursor:pointer;">${p}</button>`
        ).join('');
    } else { pg.innerHTML = ''; }
}

function renderPagination(data) {
    const pg = document.getElementById('log-pagination');
    if (!data.last_page || data.last_page <= 1) { pg.innerHTML = ''; return; }
    const loader = currentLogTab === 'activity' ? 'loadActivity' : 'loadResourceState';
    pg.innerHTML = Array.from({length: Math.min(data.last_page, 10)}, (_, i) => i+1).map(p =>
        `<button onclick="${loader}(${p})" style="padding:4px 10px;border-radius:0.5rem;border:1px solid ${p===data.current_page?'var(--cp-primary-start)':'var(--cp-soft-border)'};background:${p===data.current_page?'var(--cp-soft)':'#fff'};font-size:0.78rem;font-weight:700;cursor:pointer;">${p}</button>`
    ).join('');
}

loadActivity();
});
</script>
@endcomponent
