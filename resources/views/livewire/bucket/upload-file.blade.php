<div style="width: 100%;">
    <form wire:submit="saveFile" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; background: #fdfdfc; padding: 1rem 1.2rem; border-radius: 8px; border: 1px dashed #89a081;">
        
        {{-- Kiri: Input File --}}
        <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 0.4rem;">
            <span style="font-size: 0.7rem; font-weight: 800; color: #5b7955; text-transform: uppercase; letter-spacing: 0.05em;">
                📂 Pilih File (Target: <span style="color: #2e7d32;">{{ $prefix ? '/'.$prefix : 'Root' }}</span>)
            </span>
            <input type="file" wire:model="file" style="font-size: 0.85rem; color: #455b41; font-weight: 600; cursor: pointer; padding: 0.25rem 0;">
        </div>
        
        {{-- Tengah: Custom Metadata --}}
        <div style="flex: 1.5; min-width: 300px; display: flex; flex-direction: column; gap: 0.4rem;">
            <span style="font-size: 0.7rem; font-weight: 800; color: #5b7955; text-transform: uppercase; letter-spacing: 0.05em;">
                🏷️ Custom Metadata (Opsional)
            </span>
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" wire:model="metaKey" placeholder="Meta Key (Cth: Proyek)" style="padding: 0.5rem 0.6rem; border: 1px solid #c9dcb9; border-radius: 6px; font-size: 0.8rem; flex: 1; outline: none; background: #fafcf9; color: #455b41; font-weight: 600;">
                <input type="text" wire:model="metaValue" placeholder="Meta Value (Cth: Alpha)" style="padding: 0.5rem 0.6rem; border: 1px solid #c9dcb9; border-radius: 6px; font-size: 0.8rem; flex: 1; outline: none; background: #fafcf9; color: #455b41; font-weight: 600;">
            </div>
        </div>
        
        {{-- Kanan: Tombol Upload --}}
        <div style="display: flex; align-items: center;">
            <button type="submit" wire:loading.attr="disabled" class="cp-btn" style="background: #496443; color: white; padding: 0.5rem 1.2rem; font-size: 0.85rem; border-radius: 6px; cursor: pointer; border: none; font-weight: 700; height: 36px; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <span wire:loading.remove wire:target="file, saveFile">⬆️ Upload File</span>
                <span wire:loading wire:target="file, saveFile">⏳ Proses...</span>
            </button>
        </div>
    </form>

    @error('file') 
        <div style="color: #c62828; font-size: 0.8rem; font-weight: 700; margin-top: 0.5rem; padding-left: 0.2rem;">
            ⚠️ {{ $message }}
        </div> 
    @enderror
</div>