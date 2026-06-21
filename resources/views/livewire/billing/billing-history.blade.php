<div>
    <div class="cp-banner" style="margin-bottom:1.5rem;">
        <div style="position:relative; z-index:1;">
            <p style="font-size:0.75rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; opacity:0.8; margin-bottom:4px;">Billing & Usage</p>
            <h2>Tagihan & Pemakaian</h2>
            <p style="color:rgba(255,255,255,0.72); font-weight:400; margin-top:4px;">
                Pantau biaya infrastruktur CloudPet (Pay-As-You-Go) Anda secara transparan.
            </p>
        </div>
    </div>

    {{-- Ringkasan Tagihan --}}
    <div style="margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <div class="cp-card" style="display: flex; align-items: center; gap: 1rem; border-left: 5px solid #2e7d32;">
            <div style="font-size: 3.5rem;">💳</div>
            <div>
                <p style="font-size: 0.85rem; font-weight: 700; color: #89a081; text-transform: uppercase;">Total Tagihan Bulan Ini</p>
                <h3 style="font-size: 2rem; color: #2e7d32; margin: 0;">Rp {{ number_format($totalThisMonth, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Transaksi --}}
    <div class="cp-card">
        <h3 class="cp-section-title">📄 Riwayat Transaksi</h3>
        <div class="cp-table-wrap">
            <table class="cp-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Tanggal</th>
                        <th style="width: 20%;">Tipe</th>
                        <th style="width: 40%;">Deskripsi</th>
                        <th style="text-align: right; width: 20%;">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td style="color: #768971; font-size: 0.85rem; font-weight: 600;">
                                {{ $trx->created_at->format('d M Y, H:i') }}
                            </td>
                            <td>
                                <span style="background: #eef5e7; color: #496443; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; border: 1px solid #c9dcb9;">
                                    {{ $trx->transaction_type }}
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #45613f;">{{ $trx->description }}</td>
                            <td style="text-align: right; font-weight: 700; color: #c62828;">
                                Rp {{ number_format($trx->amount, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem 1rem; color: #8ca582; font-weight: 600;">
                                <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">📭</span>
                                Belum ada tagihan atau transaksi pemakaian infrastruktur.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>