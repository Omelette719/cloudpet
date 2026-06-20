@component('layouts.app')
    <div>
        <div class="cp-banner">
            <div style="position: relative; z-index:1;">
                <p>Cloud Services</p>
                <h2>Kelola Layanan Cloud Anda</h2>
                <p>Pilih layanan di bawah untuk mulai menggunakan fitur CloudPet.</p>
            </div>
        </div>

        <div style="margin:1rem 0;">
            <div class="cp-grid-3">
                <a href="{{ route('cloud.computing') }}" class="cp-side-link" style="display:block; padding:1rem;">
                    <span style="font-size:1.6rem">☁️</span>
                    <div style="display:inline-block; margin-left:0.6rem; vertical-align:middle;">
                        <div style="font-weight:800">Cloud Computing</div>
                        <div style="font-size:0.85rem; color:#7a9674">Instance virtual untuk kebutuhan komputasi.</div>
                    </div>
                </a>

                <a href="#" class="cp-side-link soon" style="display:block; padding:1rem;">
                    <span style="font-size:1.6rem">💾</span>
                    <div style="display:inline-block; margin-left:0.6rem; vertical-align:middle;">
                        <div style="font-weight:800">Storage (Bucket)</div>
                        <div style="font-size:0.85rem; color:#7a9674">Penyimpanan objek S3-compatible (coming soon).</div>
                    </div>
                </a>

                <a href="#" class="cp-side-link soon" style="display:block; padding:1rem;">
                    <span style="font-size:1.6rem">🗄️</span>
                    <div style="display:inline-block; margin-left:0.6rem; vertical-align:middle;">
                        <div style="font-weight:800">Managed Database</div>
                        <div style="font-size:0.85rem; color:#7a9674">Layanan database terkelola (coming soon).</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endcomponent
