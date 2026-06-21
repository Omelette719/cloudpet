@component('layouts.app')
<div class="cp-page">

    {{-- Banner Utama --}}
    <div class="cp-banner" style="margin-bottom:1.5rem;">
        <div style="position:relative;z-index:1;">
            <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;margin-bottom:4px;">
                Cloud Storage
            </p>
            <h2>Block Storage (Volume)</h2>
            <p style="color:rgba(255,255,255,0.72);font-weight:400;margin-top:4px;">
                Kelola kapasitas virtual hard disk tambahan dan pasang ke Compute Instance Anda.
            </p>
        </div>
    </div>

    {{-- Memanggil Komponen Livewire --}}
    <livewire:volume.volume-manager />

</div>
@endcomponent