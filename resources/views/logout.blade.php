@if($variant === 'admin')
    <button wire:click="logout"
            wire:confirm="Yakin mau logout? 👋"
            class="cp-logout">
        <span>👋</span><span>Logout</span>
    </button>
@else
    <button wire:click="logout"
            wire:confirm="Yakin mau logout? 👋"
            class="cp-logout">
        <span>👋</span><span>Logout</span>
    </button>
@endif