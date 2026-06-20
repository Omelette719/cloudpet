<div style="display: flex; flex-direction: column; align-items: flex-end;">
    <form wire:submit="saveFile" style="display: flex; align-items: center; gap: 0.8rem; background: #f0f4ec; padding: 0.4rem 0.6rem; border-radius: 8px; border: 1px dashed #89a081;">
        
        <div style="display: flex; flex-direction: column; gap: 0.2rem;">
            {{-- Indikator Visual Folder Target --}}
            <span style="font-size: 0.65rem; font-weight: 800; color: #5b7955; text-transform: uppercase; letter-spacing: 0.05em;">
                ⇧ Target: <span style="color: #2e7d32;">{{ $prefix ? '/'.$prefix : 'Root Bucket' }}</span>
            </span>
            <input type="file" wire:model="file" style="font-size: 0.8rem; color: #455b41; max-width: 200px; font-weight: 600; cursor: pointer;">
        </div>
        
        <button type="submit" wire:loading.attr="disabled" class="cp-btn" style="background: #496443; color: white; padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 6px; cursor: pointer; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <span wire:loading.remove wire:target="file, saveFile">Upload File</span>
            <span wire:loading wire:target="file, saveFile">⏳ Proses...</span>
        </button>
    </form>

    @error('file') 
        <span style="color: #c62828; font-size: 0.75rem; font-weight: 700; margin-top: 0.4rem; padding-right: 0.5rem;">
            ⚠️ {{ $message }}
        </span> 
    @enderror
</div>