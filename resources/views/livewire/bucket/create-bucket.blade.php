<div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
    <button 
        wire:click="createBucket" 
        wire:loading.attr="disabled"
        class="cp-btn" 
        style="background-color: #5b7955; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background-color 0.2s;"
        onmouseover="this.style.backgroundColor='#4a6344'"
        onmouseout="this.style.backgroundColor='#5b7955'"
    >
        <span wire:loading.remove wire:target="createBucket">+ Buat Bucket Baru</span>
        <span wire:loading wire:target="createBucket">⏳ Menghubungi MiniStack...</span>
    </button>

    {{-- Pesan Sukses / Error --}}
    @if (session()->has('message'))
        <span style="color: #3b6033; font-size: 0.85rem; font-weight: 700;">{{ session('message') }}</span>
    @endif
    @if (session()->has('error'))
        <span style="color: #d32f2f; font-size: 0.85rem; font-weight: 700;">{{ session('error') }}</span>
    @endif
</div>