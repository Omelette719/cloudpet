<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class Logout extends Component
{
    public string $variant = 'user'; // 'user' | 'admin'

    public function logout(): void
    {
        // Submit logout form - form dirender di view blade
        $this->dispatch('submit-logout-form');
    }

    public function render()
    {
        return view('logout');
    }
}