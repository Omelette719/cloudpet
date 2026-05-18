<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class AdminDashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query()
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->filter !== 'all', fn ($q) =>
                $q->where('role', $this->filter)
            )
            ->orderBy('created_at', 'desc');

        return view('admin-dashboard', [
            'users'      => $query->paginate(10),
            'totalUsers' => User::where('role', 'user')->count(),
            'totalAdmin' => User::where('role', 'admin')->count(),
        ]);
    }
}