@component('layouts.app')
    <div>
        <div class="cp-banner" style="margin-bottom:1.25rem;">
            <div style="position:relative; z-index:1;">
                <p style="font-size:0.75rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; opacity:0.8; margin-bottom:4px;">CloudPet</p>
                <h2>Kelola Layanan Cloud Anda</h2>
                <p style="color:rgba(255,255,255,0.72); font-weight:400; margin-top:4px;">
                    Pilih layanan di bawah untuk mulai menggunakan fitur CloudPet.
                </p>
            </div>
        </div>

        <div class="cp-grid-4">

            {{-- Cloud Computing (aktif) --}}
            <a href="{{ route('cloud.computing') }}" wire:navigate
               style="display:flex; flex-direction:column; gap:0.75rem; padding:1.25rem; border-radius:1rem; background:#fff; border:1px solid var(--cp-soft-border); text-decoration:none; transition:box-shadow 0.18s, transform 0.18s; box-shadow:var(--cp-shadow);"
               onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 20px 48px rgba(34,48,31,0.18)'"
               onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='var(--cp-shadow)'">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="width:46px; height:46px; border-radius:0.85rem; background:var(--cp-soft); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">☁️</div>
                    <span class="cp-badge">Aktif</span>
                </div>
                <div>
                    <div style="font-weight:800; font-size:1rem; color:var(--cp-ink); margin-bottom:4px;">Cloud Computing</div>
                    <div style="font-size:0.83rem; color:var(--cp-ink-muted); line-height:1.5;">Instance virtual untuk kebutuhan komputasi Anda.</div>
                </div>
                <div style="margin-top:auto; padding-top:0.75rem; border-top:1px solid var(--cp-soft-border); font-size:0.78rem; font-weight:700; color:var(--cp-primary-strong);">
                    Buka layanan →
                </div>
            </a>

            {{-- Storage / Bucket (Aktif) --}}
            <a href="{{ route('cloud.bucket') }}" wire:navigate
               style="display:flex; flex-direction:column; gap:0.75rem; padding:1.25rem; border-radius:1rem; background:#fff; border:1px solid var(--cp-soft-border); text-decoration:none; transition:box-shadow 0.18s, transform 0.18s; box-shadow:var(--cp-shadow);"
               onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 20px 48px rgba(34,48,31,0.18)'"
               onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='var(--cp-shadow)'">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="width:46px; height:46px; border-radius:0.85rem; background:var(--cp-soft); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">💾</div>
                    <span class="cp-badge">Aktif</span>
                </div>
                <div>
                    <div style="font-weight:800; font-size:1rem; color:var(--cp-ink); margin-bottom:4px;">Storage (Bucket)</div>
                    <div style="font-size:0.83rem; color:var(--cp-ink-muted); line-height:1.5;">Penyimpanan objek S3-compatible untuk data Anda.</div>
                </div>
                <div style="margin-top:auto; padding-top:0.75rem; border-top:1px solid var(--cp-soft-border); font-size:0.78rem; font-weight:700; color:var(--cp-primary-strong);">
                    Buka layanan →
                </div>
            </a>
            {{-- Database --}}
            <a href="{{ route('cloud.database') }}" wire:navigate
            style="display:flex; flex-direction:column; gap:0.75rem; padding:1.25rem; border-radius:1rem; background:#fff; border:1px solid var(--cp-soft-border); text-decoration:none; transition:box-shadow 0.18s, transform 0.18s; box-shadow:var(--cp-shadow);"
            onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 20px 48px rgba(34,48,31,0.18)'"
            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='var(--cp-shadow)'">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="width:46px; height:46px; border-radius:0.85rem; background:var(--cp-soft); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">🗄️</div>
                    <span class="cp-badge">Aktif</span>
                </div>
                <div>
                    <div style="font-weight:800; font-size:1rem; color:var(--cp-ink); margin-bottom:4px;">Managed Database</div>
                    <div style="font-size:0.83rem; color:var(--cp-ink-muted); line-height:1.5;">PostgreSQL, MySQL, MariaDB — siap pakai dalam hitungan detik.</div>
                </div>
                <div style="margin-top:auto; padding-top:0.75rem; border-top:1px solid var(--cp-soft-border); font-size:0.78rem; font-weight:700; color:var(--cp-primary-strong);">
                    Kelola Database →
                </div>
            </a>

            {{-- Block Storage --}}
            <a href="{{ route('cloud.volumes') }}" wire:navigate
            style="display:flex; flex-direction:column; gap:0.75rem; padding:1.25rem; border-radius:1rem; background:#fff; border:1px solid var(--cp-soft-border); text-decoration:none; transition:box-shadow 0.18s, transform 0.18s; box-shadow:var(--cp-shadow);"
            onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 20px 48px rgba(34,48,31,0.18)'"
            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='var(--cp-shadow)'">
                
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="width:46px; height:46px; border-radius:0.85rem; background:var(--cp-soft); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">💽</div>
                    {{-- Jika ingin badge seperti bucket, bisa tambahkan: --}}
                    {{-- <span class="cp-badge">Aktif</span> --}}
                </div>
                
                <div>
                    <div style="font-weight:800; font-size:1rem; color:var(--cp-ink); margin-bottom:4px;">Block Storage</div>
                    <div style="font-size:0.83rem; color:var(--cp-ink-muted); line-height:1.5;">Tambahkan kapasitas hard disk virtual (Volume) ekstra ke Compute Instance Anda.</div>
                </div>
                
                <div style="margin-top:auto; padding-top:0.75rem; border-top:1px solid var(--cp-soft-border); font-size:0.78rem; font-weight:700; color:var(--cp-primary-strong);">
                    Kelola Volume →
                </div>
            </a>

        </div>
    </div>
@endcomponent