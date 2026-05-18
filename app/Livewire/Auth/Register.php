<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('pages::auth.guest')]
class Register extends Component
{
    public string $name                  = '';
    public string $email                 = '';
    public string $password              = '';
    public string $password_confirmation = '';
    public string $animal_avatar         = '🐱';

    protected function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'animal_avatar' => ['required', 'string', 'in:' . implode(',', User::animalAvatars())],
        ];
    }

    protected $messages = [
        'name.required'      => 'Nama harus diisi.',
        'email.required'     => 'Email harus diisi.',
        'email.unique'       => 'Email sudah terdaftar.',
        'password.required'  => 'Password harus diisi.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'animal_avatar.in'   => 'Pilih avatar hewan yang valid.',
    ];

    public function register(): void
    {
        $validated = $this->validate();

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'role'          => 'user',
            'animal_avatar' => $validated['animal_avatar'],
        ]);

        event(new Registered($user));
        Auth::login($user);

        $this->redirect(route('user.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('pages::auth.register', [
            'avatars' => User::animalAvatars(),
        ]);
    }
}