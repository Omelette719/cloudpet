<div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
    <button 
        wire:click="createBucket" 
        wire:loading.attr="disabled"
        class="cp-btn"
    >
        <span wire:loading.remove wire:target="createBucket">➕ Buat Bucket Baru</span>
        <span wire:loading wire:target="createBucket">⏳ Menyiapkan...</span>
    </button>

    {{-- Pesan Sukses / Error --}}
    @if (session()->has('message'))
        <span style="color: #2e7d32; font-size: 0.8rem; font-weight: 700; background: #e8f5e9; padding: 0.3rem 0.6rem; border-radius: 6px; border: 1px solid #c8e6c9;">
            ✅ {{ session('message') }}
        </span>
    @endif
    @if (session()->has('error'))
        <span style="color: #c62828; font-size: 0.8rem; font-weight: 700; background: #ffebee; padding: 0.3rem 0.6rem; border-radius: 6px; border: 1px solid #ffcdd2;">
            ⚠️ {{ session('error') }}
        </span>
    @endif
</div>