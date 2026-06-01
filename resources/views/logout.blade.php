<div>
    @if($variant === 'admin')
        <button wire:click="logout"
                wire:confirm="Yakin mau logout? 👋"
                wire:loading.attr="disabled"
                type="button"
                class="cp-logout">
            <span wire:loading.remove wire:target="logout">👋</span>
            <span wire:loading wire:target="logout">⏳</span>
            <span wire:loading.remove wire:target="logout">Logout</span>
            <span wire:loading wire:target="logout">Logging out...</span>
        </button>
    @else
        <button wire:click="logout"
                wire:confirm="Yakin mau logout? 👋"
                wire:loading.attr="disabled"
                type="button"
                class="cp-logout">
            <span wire:loading.remove wire:target="logout">👋</span>
            <span wire:loading wire:target="logout">⏳</span>
            <span wire:loading.remove wire:target="logout">Logout</span>
            <span wire:loading wire:target="logout">Logging out...</span>
        </button>
    @endif

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    @script
    <script>
        Livewire.on('submit-logout-form', () => {
            document.getElementById('logout-form').submit();
        });
    </script>
    @endscript
</div>