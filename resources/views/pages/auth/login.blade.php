<div class="cp-auth-card">
    <div class="cp-auth-heading">
        <h2>Masuk ke <span class="cp-wordmark-cloud">Cloud</span><span class="cp-wordmark-pet">Pet</span></h2>
        <p>Sarang awanmu menunggu, masuk dulu ya.</p>
    </div>

    <form wire:submit="login" novalidate class="cp-form">
        <div class="cp-input-group">
            <label for="email" class="cp-label">Email</label>
            <input wire:model="email" id="email" type="email"
                   class="cp-input {{ $errors->has('email') ? 'error' : '' }}"
                   placeholder="email@gmail.com" autocomplete="email" />
            @error('email')
                <p class="cp-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="cp-input-group">
            <label for="password" class="cp-label">Password</label>
            <input wire:model="password" id="password" type="password"
                   class="cp-input {{ $errors->has('password') ? 'error' : '' }}"
                   placeholder="••••••••" autocomplete="current-password" />
            @error('password')
                <p class="cp-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="cp-checkline">
            <input wire:model="remember" type="checkbox" />
            <span>Ingat aku ya</span>
        </label>

        <button type="submit" class="cp-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>Login</span>
            <span wire:loading>Sedang masuk...</span>
        </button>
    </form>

    <p class="cp-auth-footer">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate>Daftar di sini</a>
    </p>
</div>