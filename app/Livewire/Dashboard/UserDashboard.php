<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class UserDashboard extends Component
{
    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();

        return view('user-dashboard', [
            'user' => $user,
        ]);
    }
}