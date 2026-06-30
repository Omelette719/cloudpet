<div>

    {{-- Notifikasi --}}
    @if (session()->has('success'))
        <div style="background:#eaf4dd;color:#3b5136;padding:1rem;border-radius:0.85rem;margin-bottom:1rem;font-weight:700;border:1px solid #c6e0a8;">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background:#fde8e8;color:#9b2c2c;padding:1rem;border-radius:0.85rem;margin-bottom:1rem;font-weight:700;border:1px solid #f5c6c6;">
            {{ session('error') }}
        </div>
    @endif

    {{-- ═══════ BARIS 1: Form + Terminal ═══════ --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start;margin-bottom:1.25rem;">

        {{-- KIRI: Form Buat Volume --}}
        <div class="cp-card">
            <h3 class="cp-section-title" style="margin-top:0;">Buat Volume Baru</h3>

            <form wire:submit.prevent="createVolume">
                <div style="margin-bottom:14px;">
                    <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Nama Volume</label>
                    <input type="text" wire:model="volumeName" placeholder="contoh: data-mysql"
                        style="width:100%;padding:0.65rem 0.8rem;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.85rem;outline:none;background:#fff;color:var(--cp-ink);font-weight:600;">
                    @error('volumeName') <span style="font-size:0.75rem;color:#c62828;margin-top:4px;display:block;">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:18px;">
                    <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Kapasitas (GB)</label>
                    <input type="number" wire:model="sizeGb" min="1" max="1000"
                        style="width:100%;padding:0.65rem 0.8rem;border:1px solid var(--cp-soft-border);border-radius:0.6rem;font-size:0.85rem;outline:none;background:#fff;color:var(--cp-ink);font-weight:600;">
                    <div style="font-size:0.7rem;color:var(--cp-ink-muted);margin-top:6px;font-weight:600;">Biaya: Rp 15 / GB per jam.</div>
                    @error('sizeGb') <span style="font-size:0.75rem;color:#c62828;margin-top:4px;display:block;">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="cp-btn" style="width:100%;padding:12px;border-radius:0.85rem;font-size:0.9rem;justify-content:center;" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createVolume">Provisioning Volume</span>
                    <span wire:loading wire:target="createVolume">Memproses...</span>
                </button>
            </form>
        </div>

        {{-- KANAN: Panel Terminal / Panduan --}}
        <div style="background:var(--cp-soft);border:1px solid var(--cp-soft-border);border-radius:0.95rem;padding:16px;min-height:300px;">

            @if($provisioningLog)
                {{-- ── Terminal Provisioning ── --}}
                <div @if($provisioningLog['status'] === 'PROVISIONING') wire:poll.2s @endif>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--cp-ink-muted);">Proses Pembuatan Volume</div>
                    @if($provisioningLog['status'] === 'PROVISIONING')
                        <span style="font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#fef3c7;color:#92400e;">PROVISIONING</span>
                    @elseif($provisioningLog['status'] === 'AVAILABLE')
                        <span style="font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#eaf4dd;color:#3b5136;">AVAILABLE</span>
                    @elseif($provisioningLog['status'] === 'ERROR')
                        <span style="font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#fde8e8;color:#9b2c2c;">ERROR</span>
                    @endif
                </div>

                <pre id="vol-provision-terminal" style="background:#1b1f17;color:#cdebb0;font-family:'Cascadia Code','Fira Code',monospace;font-size:0.74rem;line-height:1.55;border-radius:0.7rem;padding:12px 14px;margin:0;height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;">{{ $provisioningLog['log'] }}</pre>

                {{-- Hasil provisioning --}}
                @if($provisioningLog['status'] === 'AVAILABLE')
                    <div style="margin-top:10px;background:#eaf4dd;border:1px solid #c6e0a8;border-radius:0.75rem;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:0.82rem;font-weight:700;color:#3b5136;">Volume berhasil dibuat!</span>
                        <button wire:click="dismissProvisioning" style="background:none;border:none;font-size:0.78rem;font-weight:700;color:#5a7a55;cursor:pointer;text-decoration:underline;">Tutup</button>
                    </div>
                @elseif($provisioningLog['status'] === 'ERROR')
                    <div style="margin-top:10px;background:#fde8e8;border:1px solid #f5c6c6;border-radius:0.75rem;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:0.82rem;font-weight:700;color:#9b2c2c;">Provisioning gagal. Cek log di atas.</span>
                        <button wire:click="dismissProvisioning" style="background:none;border:none;font-size:0.78rem;font-weight:700;color:#9b2c2c;cursor:pointer;text-decoration:underline;">Tutup</button>
                    </div>
                @endif

                <script>
                    (() => {
                        const el = document.getElementById('vol-provision-terminal');
                        if (el) el.scrollTop = el.scrollHeight;
                    })();
                </script>
                </div>

            @else
                {{-- ── Panel Panduan ── --}}
                <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--cp-ink-muted);margin-bottom:12px;">Panduan Block Storage</div>

                @foreach([
                    ['step' => '1', 'text' => 'Isi nama volume (contoh: <code style="background:#e8f0e4;padding:2px 6px;border-radius:4px;font-size:0.78rem;">data-mysql</code>, <code style="background:#e8f0e4;padding:2px 6px;border-radius:4px;font-size:0.78rem;">backup-01</code>).'],
                    ['step' => '2', 'text' => 'Tentukan kapasitas sesuai kebutuhan (1 &ndash; 1000 GB).'],
                    ['step' => '3', 'text' => 'Klik <strong>"Provisioning Volume"</strong> &mdash; proses pembuatan ditampilkan di panel ini.'],
                    ['step' => '4', 'text' => 'Setelah berstatus <span style="background:#eaf4dd;color:#3b5136;font-size:0.68rem;font-weight:700;padding:2px 7px;border-radius:999px;">AVAILABLE</span>, pasang ke Compute Instance via tombol <strong>Attach</strong>.'],
                ] as $s)
                <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;">
                    <span style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--cp-primary-start),var(--cp-primary-end));color:#fff;font-size:0.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $s['step'] }}</span>
                    <span style="font-size:0.83rem;color:var(--cp-ink-muted);line-height:1.5;padding-top:2px;">{!! $s['text'] !!}</span>
                </div>
                @endforeach

                <div style="margin-top:14px;background:#e8f0e4;border:1px solid #d4e4cc;border-radius:0.7rem;padding:10px 14px;">
                    <div style="font-size:0.72rem;font-weight:700;color:var(--cp-ink);margin-bottom:4px;">Mount Path di Instance</div>
                    <code style="font-size:0.78rem;color:#3b5136;font-weight:600;">/mnt/volumes/{nama-volume}</code>
                </div>
            @endif

        </div>
    </div>

    {{-- ═══════ BARIS 2: Daftar Volume ═══════ --}}
    <div class="cp-card">
        <h3 class="cp-section-title" style="margin-top:0;">Daftar Volume Anda</h3>

        <div class="cp-table-wrap">
            <table class="cp-table">
                <thead>
                    <tr>
                        <th>Informasi Volume</th>
                        <th>Status</th>
                        <th>Terpasang Ke</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($volumes as $volume)
                        <tr style="transition:background-color 0.2s;" onmouseover="this.style.backgroundColor='var(--cp-soft)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td>
                                <div style="font-weight:800;color:var(--cp-ink);font-size:0.95rem;">{{ $volume->volume_name }}</div>
                                <div style="font-size:0.75rem;color:var(--cp-ink-muted);font-weight:600;margin-top:2px;">{{ $volume->size_gb }} GB</div>
                            </td>
                            <td>
                                @if($volume->status === 'PROVISIONING')
                                    <span style="background:#fef3c7;color:#92400e;font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;">PROVISIONING</span>
                                @elseif($volume->status === 'AVAILABLE')
                                    <span style="background:#eaf4dd;color:#3b5136;font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;">AVAILABLE</span>
                                @elseif($volume->status === 'ATTACHED')
                                    <span style="background:#e8f2fb;color:#2b5fa0;font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;">ATTACHED</span>
                                @elseif($volume->status === 'ERROR')
                                    <span style="background:#fde8e8;color:#9b2c2c;font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;">ERROR</span>
                                @else
                                    <span style="background:#f3f4f6;color:#6b7280;font-size:0.68rem;font-weight:700;padding:3px 9px;border-radius:999px;">{{ $volume->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($volume->computeInstance)
                                    <div style="font-size:0.8rem;color:var(--cp-ink);font-weight:700;">{{ $volume->computeInstance->name }}</div>
                                @else
                                    <span style="color:var(--cp-ink-muted);font-size:0.8rem;font-weight:600;">&mdash;</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;justify-content:flex-end;gap:6px;">

                                    @if(in_array($volume->status, ['AVAILABLE', 'PROVISIONING', 'ERROR']))
                                        <button wire:click="deleteVolume('{{ $volume->id }}')"
                                                wire:confirm="Yakin ingin menghapus volume {{ $volume->volume_name }}? Tindakan ini permanen."
                                                style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #f5c6c6;background:#fde8e8;color:#9b2c2c;">
                                            Hapus
                                        </button>
                                    @endif

                                    @if($volume->status === 'AVAILABLE')
                                        <button wire:click="openAttachModal('{{ $volume->id }}')"
                                                style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #b8d4f0;background:#e8f2fb;color:#2b5fa0;">
                                            Attach
                                        </button>
                                    @endif

                                    @if($volume->status === 'ATTACHED')
                                        <button wire:click="detachVolume('{{ $volume->id }}')"
                                                wire:confirm="Lepas volume {{ $volume->volume_name }} dari {{ $volume->computeInstance->name ?? 'instance' }}?"
                                                style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #fde68a;background:#fef3c7;color:#92400e;">
                                            Detach
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:3rem 1rem;color:var(--cp-ink-muted);font-weight:600;">
                                <div style="font-size:2rem;margin-bottom:0.5rem;opacity:0.5;">💽</div>
                                Belum ada Block Volume. Buat di panel atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════ Modal Attach ═══════ --}}
    @if($attachingVolumeId)
    <div style="position:fixed;inset:0;background:rgba(34,48,31,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;">
        <div class="cp-card" style="width:380px;max-width:92%;">
            <h3 class="cp-section-title" style="margin-top:0;">Pasang Volume ke Instance</h3>

            @if($instances->isEmpty())
                <p style="font-size:0.85rem;color:var(--cp-ink-muted);">
                    Belum ada Compute Instance aktif. Buat dulu di menu Cloud Computing.
                </p>
            @else
                <select wire:model="attachInstanceId"
                        style="width:100%;padding:0.6rem;border-radius:0.6rem;border:1px solid var(--cp-soft-border);margin-bottom:1rem;">
                    <option value="">-- Pilih Compute Instance --</option>
                    @foreach($instances as $instance)
                        <option value="{{ $instance->id }}">{{ $instance->name }} ({{ $instance->status }})</option>
                    @endforeach
                </select>
            @endif

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button wire:click="closeAttachModal" class="cp-btn"
                        style="width:auto;background:#f0f5ea;color:#61765d;box-shadow:none;">Batal</button>
                <button wire:click="confirmAttach" class="cp-btn" style="width:auto;" @disabled($instances->isEmpty())>
                    Pasang
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
