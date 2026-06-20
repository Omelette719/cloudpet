<div>
    <form wire:submit="upload" style="display: flex; gap: 0.5rem;">
        <input type="file" wire:model="file" style="font-size: 0.8rem;">
        <button type="submit" wire:loading.attr="disabled" class="cp-btn" style="background: #5b7955; color: white; padding: 0.3rem 0.8rem; border-radius: 4px;">
            <span wire:loading.remove>Upload</span>
            <span wire:loading>...</span>
        </button>
    </form>
</div>