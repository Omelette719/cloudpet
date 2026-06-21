<div>
    {{-- Notifikasi --}}
    @if (session()->has('success'))
        <div style="background: #eaf4dd; color: #3b5136; padding: 1rem; border-radius: 0.85rem; margin-bottom: 1rem; font-weight: 700; border: 1px solid #c6e0a8;">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background: #fde8e8; color: #9b2c2c; padding: 1rem; border-radius: 0.85rem; margin-bottom: 1rem; font-weight: 700; border: 1px solid #f5c6c6;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <div style="display:grid; grid-template-columns: 1fr 2.2fr; gap: 1.25rem; align-items: start;">
        
        {{-- BAGIAN KIRI: Form Buat Volume --}}
        <div class="cp-card">
            <h3 class="cp-section-title" style="margin-top:0;">Buat Volume Baru</h3>
            
            <form wire:submit.prevent="createVolume">
                {{-- Input Nama --}}
                <div style="margin-bottom:14px;">
                    <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Nama Volume</label>
                    <input type="text" wire:model="volumeName" placeholder="contoh: data-mysql" 
                        style="width:100%; padding: 0.65rem 0.8rem; border: 1px solid var(--cp-soft-border); border-radius: 0.6rem; font-size: 0.85rem; outline: none; background: #fff; color: var(--cp-ink); font-weight: 600;">
                    @error('volumeName') <span style="font-size: 0.75rem; color: #c62828; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                {{-- Input Kapasitas --}}
                <div style="margin-bottom:18px;">
                    <label class="cp-label" style="color:var(--cp-ink);font-size:0.78rem;margin-bottom:8px;display:block;">Kapasitas (GB)</label>
                    <input type="number" wire:model="sizeGb" min="1" max="1000" 
                        style="width:100%; padding: 0.65rem 0.8rem; border: 1px solid var(--cp-soft-border); border-radius: 0.6rem; font-size: 0.85rem; outline: none; background: #fff; color: var(--cp-ink); font-weight: 600;">
                    <div style="font-size:0.7rem; color:var(--cp-ink-muted); margin-top:6px; font-weight:600;">💰 Biaya: Rp 15 / GB per jam.</div>
                    @error('sizeGb') <span style="font-size: 0.75rem; color: #c62828; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="cp-btn" style="width:100%; padding: 12px; border-radius: 0.85rem; font-size: 0.9rem; justify-content: center;" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createVolume">Provisioning Volume</span>
                    <span wire:loading wire:target="createVolume">⏳ Memproses...</span>
                </button>
            </form>
        </div>

        {{-- BAGIAN KANAN: Daftar Volume --}}
        <div class="cp-card">
            <h3 class="cp-section-title" style="margin-top:0;">Daftar Volume Anda</h3>
            
            <div class="cp-table-wrap" wire:poll.5s>
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Informasi Volume</th>
                            <th>Status</th>
                            <th>Attached To (Terpasang)</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($volumes as $volume)
                            <tr style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='var(--cp-soft)'" onmouseout="this.style.backgroundColor='transparent'">
                                <td>
                                    <div style="font-weight: 800; color: var(--cp-ink); font-size: 0.95rem;">{{ $volume->volume_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--cp-ink-muted); font-weight: 600; margin-top: 2px;">💾 {{ $volume->size_gb }} GB</div>
                                </td>
                                <td>
                                    {{-- Desain Chip Status Identik dengan Computing --}}
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
                                        <div style="font-size: 0.8rem; color: var(--cp-ink); font-weight: 700;">🖥️ {{ $volume->computeInstance->name }}</div>
                                    @else
                                        <span style="color: var(--cp-ink-muted); font-size: 0.8rem; font-weight: 600;">-</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                        @if($volume->status === 'AVAILABLE')
                                            <button wire:click="openAttachModal({{ $volume->id }})"
                                                    style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #b8d4f0;background:#e8f2fb;color:#2b5fa0;">
                                                🔗 Attach
                                            </button>
                                            <button wire:click="deleteVolume({{ $volume->id }})"
                                                    wire:confirm="Yakin ingin menghapus volume {{ $volume->volume_name }}? Tindakan ini permanen."
                                                    style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #f5c6c6;background:#fde8e8;color:#9b2c2c;">
                                                🗑️ Delete
                                            </button>
                                        @elseif($volume->status === 'ATTACHED')
                                            <button wire:click="detachVolume({{ $volume->id }})"
                                                    wire:confirm="Lepas volume {{ $volume->volume_name }} dari {{ $volume->computeInstance->name ?? 'instance' }}?"
                                                    style="padding:6px 12px;border-radius:0.65rem;font-size:0.75rem;font-weight:700;cursor:pointer;border:1px solid #fde68a;background:#fef3c7;color:#92400e;">
                                                🔌 Detach
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 4rem 1rem; color: var(--cp-ink-muted); font-weight: 600;">
                                    <div style="font-size: 2.5rem; margin-bottom: 0.8rem; opacity: 0.6;">💽</div>
                                    Anda belum memiliki Block Storage.<br>Silakan buat di panel sebelah kiri.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    @if($attachingVolumeId)
    <div style="position:fixed;inset:0;background:rgba(34,48,31,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;">
        <div class="cp-card" style="width:380px;max-width:92%;">
            <h3 class="cp-section-title" style="margin-top:0;">Pasang Volume ke Instance</h3>

            @if($instances->isEmpty())
                <p style="font-size:0.85rem;color:var(--cp-ink-muted);">
                    Anda belum punya Compute Instance yang aktif. Buat instance duslu di menu Cloud Computing.
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