<div>
    <div class="cp-banner">
        <div style="position:absolute;right:1rem;top:-0.5rem;font-size:4rem;opacity:0.2;">{{ auth()->user()->animal_avatar }}</div>
        <div style="position:relative;z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">Admin Panel</p>
            <h2>CloudPet Control Center</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">Pantau seluruh platform dari satu tempat.</p>
        </div>
    </div>

    {{-- Server Monitor --}}
    <div style="margin-top:1rem;">
        <h3 class="cp-section-title">Server Monitor</h3>
        <div class="cp-card" style="background:#1b1f17;border-color:#2a3a2a;color:#cdebb0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div>
                    <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#5a7a55;" id="srv-host">Loading...</div>
                    <div style="font-size:0.62rem;color:#4a6344;" id="srv-os">—</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:0.65rem;color:#5a7a55;" id="srv-uptime">—</span>
                    <span style="width:8px;height:8px;border-radius:50%;background:#4ade80;display:inline-block;animation:pulse 2s infinite;"></span>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
                {{-- CPU --}}
                <div style="background:#222a1e;border-radius:0.7rem;padding:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <div style="font-size:0.65rem;font-weight:700;color:#5a7a55;text-transform:uppercase;letter-spacing:0.05em;">CPU</div>
                        <div style="font-size:1.1rem;font-weight:800;color:#cdebb0;" id="srv-cpu-pct">—</div>
                    </div>
                    <div style="height:6px;background:#1a2116;border-radius:999px;">
                        <div id="srv-cpu-bar" style="height:6px;border-radius:999px;background:linear-gradient(90deg,#4ade80,#22c55e);width:0%;transition:width 0.8s;"></div>
                    </div>
                    <div style="font-size:0.6rem;color:#4a6344;margin-top:6px;" id="srv-cpu-name">—</div>
                </div>

                {{-- RAM --}}
                <div style="background:#222a1e;border-radius:0.7rem;padding:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <div style="font-size:0.65rem;font-weight:700;color:#5a7a55;text-transform:uppercase;letter-spacing:0.05em;">Memory</div>
                        <div style="font-size:1.1rem;font-weight:800;color:#cdebb0;" id="srv-ram-pct">—</div>
                    </div>
                    <div style="height:6px;background:#1a2116;border-radius:999px;">
                        <div id="srv-ram-bar" style="height:6px;border-radius:999px;background:linear-gradient(90deg,#a78bfa,#7c3aed);width:0%;transition:width 0.8s;"></div>
                    </div>
                    <div style="font-size:0.6rem;color:#4a6344;margin-top:6px;" id="srv-ram-detail">—</div>
                </div>

                {{-- Docker --}}
                <div style="background:#222a1e;border-radius:0.7rem;padding:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <div style="font-size:0.65rem;font-weight:700;color:#5a7a55;text-transform:uppercase;letter-spacing:0.05em;">Docker</div>
                        <div style="font-size:1.1rem;font-weight:800;color:#cdebb0;" id="srv-docker-count">—</div>
                    </div>
                    <div style="font-size:0.6rem;color:#4a6344;margin-top:2px;" id="srv-docker-list">—</div>
                </div>
            </div>

            {{-- Disks --}}
            <div id="srv-disks" style="display:grid;gap:8px;"></div>
        </div>
    </div>

    {{-- Platform Overview --}}
    <div style="margin-top:1rem;">
        <h3 class="cp-section-title">Platform Overview</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));gap:0.8rem;">
            @foreach([
                ['id'=>'s-users',     'icon'=>'👥', 'label'=>'Users'],
                ['id'=>'s-instances', 'icon'=>'☁️', 'label'=>'Instance Running'],
                ['id'=>'s-databases', 'icon'=>'🗄️', 'label'=>'Database Running'],
                ['id'=>'s-volumes',   'icon'=>'💽', 'label'=>'Volumes'],
                ['id'=>'s-buckets',   'icon'=>'📦', 'label'=>'Buckets'],
                ['id'=>'s-revenue',   'icon'=>'💰', 'label'=>'Revenue Bulan Ini'],
            ] as $s)
            <div class="cp-stat" style="padding:12px;">
                <span style="font-size:1.4rem;">{{ $s['icon'] }}</span>
                <div>
                    <p class="cp-stat-label">{{ $s['label'] }}</p>
                    <p class="cp-stat-value" id="{{ $s['id'] }}">—</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Quick links --}}
    <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
        <a href="{{ route('admin.plans') }}" wire:navigate class="cp-card" style="text-decoration:none;display:flex;align-items:center;gap:12px;cursor:pointer;transition:transform 0.15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
            <div style="width:42px;height:42px;border-radius:0.75rem;background:var(--cp-soft);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">🧩</div>
            <div>
                <div style="font-weight:800;color:var(--cp-ink);font-size:0.9rem;">Kelola Plans</div>
                <div style="font-size:0.72rem;color:var(--cp-ink-muted);">CRUD paket layanan</div>
            </div>
        </a>
        <a href="{{ route('admin.users') }}" wire:navigate class="cp-card" style="text-decoration:none;display:flex;align-items:center;gap:12px;cursor:pointer;transition:transform 0.15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
            <div style="width:42px;height:42px;border-radius:0.75rem;background:var(--cp-soft);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">👥</div>
            <div>
                <div style="font-weight:800;color:var(--cp-ink);font-size:0.9rem;">Users & Billing</div>
                <div style="font-size:0.72rem;color:var(--cp-ink-muted);">Kelola pengguna & transaksi</div>
            </div>
        </a>
        <a href="{{ route('admin.logs') }}" wire:navigate class="cp-card" style="text-decoration:none;display:flex;align-items:center;gap:12px;cursor:pointer;transition:transform 0.15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
            <div style="width:42px;height:42px;border-radius:0.75rem;background:var(--cp-soft);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">🛡️</div>
            <div>
                <div style="font-weight:800;color:var(--cp-ink);font-size:0.9rem;">Logs & Monitoring</div>
                <div style="font-size:0.72rem;color:var(--cp-ink-muted);">Activity, resource, error logs</div>
            </div>
        </a>
    </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
</style>

<script>
(function(){
    const _intervals = [];
    let _destroyed = false;
    document.addEventListener('livewire:navigating', function cleanup() {
        _destroyed = true; _intervals.forEach(clearInterval);
        document.removeEventListener('livewire:navigating', cleanup);
    });

    // Platform stats
    fetch('/admin/api/stats', { credentials:'same-origin' }).then(r=>r.json()).then(d => {
        document.getElementById('s-users').innerText = d.users + ' user';
        document.getElementById('s-instances').innerText = d.instances_running + ' / ' + d.instances_total;
        document.getElementById('s-databases').innerText = d.databases_running;
        document.getElementById('s-volumes').innerText = d.volumes_total;
        document.getElementById('s-buckets').innerText = d.buckets_total;
        document.getElementById('s-revenue').innerText = 'Rp ' + parseInt(d.revenue_month).toLocaleString('id-ID');
    });

    // Server monitor
    function barColor(pct) {
        if (pct > 90) return '#ef4444';
        if (pct > 70) return '#f59e0b';
        return '';
    }

    async function loadServer() {
        if (_destroyed) return;
        try {
            const res = await fetch('/admin/api/server', { credentials:'same-origin' });
            const d = await res.json();

            document.getElementById('srv-host').innerText = (d.hostname || '—') + ' · PHP ' + (d.php || '');
            document.getElementById('srv-os').innerText = d.os || '—';
            document.getElementById('srv-uptime').innerText = d.uptime || '—';

            // CPU
            if (d.cpu) {
                const cpuPct = d.cpu.percent || 0;
                document.getElementById('srv-cpu-pct').innerText = cpuPct + '%';
                const cpuBar = document.getElementById('srv-cpu-bar');
                cpuBar.style.width = cpuPct + '%';
                const bc = barColor(cpuPct);
                if (bc) cpuBar.style.background = bc;
                document.getElementById('srv-cpu-name').innerText = (d.cpu.name || '—') + ' · ' + (d.cpu.cores || '?') + ' cores';
            }

            // RAM
            if (d.ram) {
                const ramPct = d.ram.percent || 0;
                document.getElementById('srv-ram-pct').innerText = ramPct + '%';
                const ramBar = document.getElementById('srv-ram-bar');
                ramBar.style.width = ramPct + '%';
                const bc = barColor(ramPct);
                if (bc) ramBar.style.background = bc;
                const usedGb = (d.ram.used_mb / 1024).toFixed(1);
                const totalGb = (d.ram.total_mb / 1024).toFixed(1);
                document.getElementById('srv-ram-detail').innerText = usedGb + ' / ' + totalGb + ' GB';
            }

            // Docker
            if (d.docker) {
                document.getElementById('srv-docker-count').innerText = d.docker.running + ' containers';
                const containers = (d.docker.containers || []).slice(0, 8);
                document.getElementById('srv-docker-list').innerHTML = containers.map(c =>
                    `<div style="display:flex;justify-content:space-between;padding:2px 0;"><span style="color:#cdebb0;">${c.name}</span><span style="color:#5a7a55;font-size:0.55rem;">${c.status.split(' ')[0]}</span></div>`
                ).join('') || '—';
            }

            // Disks
            if (d.disk && d.disk.length) {
                document.getElementById('srv-disks').innerHTML = d.disk.map(dk => {
                    const pct = dk.percent || 0;
                    const bc = pct > 90 ? '#ef4444' : pct > 70 ? '#f59e0b' : 'linear-gradient(90deg,#38bdf8,#0ea5e9)';
                    return `<div style="background:#222a1e;border-radius:0.6rem;padding:10px 12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <div style="font-size:0.65rem;font-weight:700;color:#5a7a55;text-transform:uppercase;">Disk ${dk.drive || '?'}</div>
                            <div style="font-size:0.82rem;font-weight:800;color:#cdebb0;">${dk.used_gb}G / ${dk.total_gb}G <span style="color:#5a7a55;font-size:0.68rem;">(${pct}%)</span></div>
                        </div>
                        <div style="height:5px;background:#1a2116;border-radius:999px;">
                            <div style="height:5px;border-radius:999px;background:${bc};width:${pct}%;transition:width 0.8s;"></div>
                        </div>
                    </div>`;
                }).join('');
            }
        } catch(e) {}
    }

    loadServer();
    _intervals.push(setInterval(() => { if (!_destroyed) loadServer(); }, 5000));
})();
</script>
