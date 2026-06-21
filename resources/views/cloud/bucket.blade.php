    <div>
        <div class="cp-banner" style="margin-bottom:1.5rem;">
            <div style="position:relative; z-index:1;">
                <p style="font-size:0.75rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; opacity:0.8; margin-bottom:4px;">CloudPet Storage</p>
                <h2>Manajemen Bucket</h2>
                <p style="color:rgba(255,255,255,0.72); font-weight:400; margin-top:4px;">
                    Kelola objek penyimpanan S3-compatible Anda.
                </p>
            </div>
        </div>

        <div style="background: #fff; border-radius: 1rem; border: 1px solid var(--cp-soft-border); padding: 1.5rem; box-shadow: var(--cp-shadow);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 class="cp-section-title" style="margin-bottom: 0;">📦 Daftar Storage Bucket Saya</h3>
                
                {{-- Tombol Pembuatan Bucket --}}
                @livewire('bucket.create-bucket')
            </div>

            {{-- Pesan Feedback --}}
            @if (session()->has('message'))
                <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 700; border: 1px solid #c8e6c9;">
                    {{ session('message') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div style="background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 700; border: 1px solid #ffcdd2;">
                    {{ session('error') }}
                </div>
            @endif
            
            <div class="cp-table-wrap">
                <div style="overflow-x: auto;">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Nama Bucket</th>
                                <th>Access Key</th>
                                <th>Secret Key</th>
                                <th>Dibuat Pada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($buckets as $bucket)
                                <tr>
                                    <td style="font-weight: 700;">
                                        <a href="{{ route('bucket.manager', $bucket->id) }}" wire:navigate style="color: #2e7d32; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                                            📂 {{ $bucket->bucket_name }}
                                        </a>
                                    </td>
                                    <td><code style="background: #f0f4ec; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $bucket->access_key }}</code></td>
                                    <td><code style="background: #f0f4ec; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $bucket->secret_key }}</code></td>
                                    <td style="color: #809279; font-size: 0.75rem; font-weight: 600;">{{ $bucket->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: 2.3rem 0.9rem; text-align: center; color: #8ca582; font-weight: 700;">
                                        <span style="font-size: 2rem; display: block; margin-bottom: 0.4rem;">☁️</span>
                                        Anda belum memiliki bucket. Silakan buat bucket baru.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endcomponent