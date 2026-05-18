<div class="cp-auth-card register">
    <div class="cp-auth-heading">
        <h2>Daftar <span class="cp-wordmark-cloud">Cloud</span><span class="cp-wordmark-pet">Pet</span></h2>
        <p>Buat akun <span class="cp-wordmark-cloud">Cloud</span><span class="cp-wordmark-pet">Pet</span> kamu dulu ya.</p>
    </div>

    <form wire:submit="register" novalidate class="cp-form">
        <div class="cp-input-group">
            <label for="name" class="cp-label">Nama</label>
            <input wire:model="name" id="name" type="text"
                   class="cp-input {{ $errors->has('name') ? 'error' : '' }}"
                   placeholder="Nama panggilanmu" autocomplete="name" />
            @error('name') <p class="cp-error">{{ $message }}</p> @enderror
        </div>

        <div class="cp-input-group">
            <label for="email" class="cp-label">Email</label>
            <input wire:model="email" id="email" type="email"
                   class="cp-input {{ $errors->has('email') ? 'error' : '' }}"
                   placeholder="email@gmail.com" autocomplete="email" />
            @error('email') <p class="cp-error">{{ $message }}</p> @enderror
        </div>

        <div class="cp-input-group">
            <label for="password" class="cp-label">Password</label>
            <input wire:model="password" id="password" type="password"
                   class="cp-input {{ $errors->has('password') ? 'error' : '' }}"
                   placeholder="Min 8 karakter, huruf besar + angka" autocomplete="new-password" />
            @error('password') <p class="cp-error">{{ $message }}</p> @enderror
        </div>

        <div class="cp-input-group">
            <label for="password_confirmation" class="cp-label">Konfirmasi Password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password"
                   class="cp-input"
                   placeholder="Ulangi password" autocomplete="new-password" />
        </div>

        <div class="cp-input-group">
            <p class="cp-label">Pilih Avatar Hewanmu</p>
            <div class="cp-avatar-grid">
                @foreach($avatars as $avatar)
                    <button type="button"
                            wire:click="$set('animal_avatar', '{{ $avatar }}')"
                            class="cp-avatar-option {{ $animal_avatar === $avatar ? 'active' : '' }}"
                            aria-label="Pilih avatar {{ $avatar }}">
                        {{ $avatar }}
                    </button>
                @endforeach
            </div>
            @error('animal_avatar') <p class="cp-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="cp-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>Daftar</span>
            <span wire:loading>Mendaftarkan...</span>
        </button>
    </form>

    <p class="cp-auth-footer">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate>Login di sini</a>
    </p>
</div>