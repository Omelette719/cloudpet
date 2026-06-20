<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Models\StorageBucket;
use App\Services\MiniStackService; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Aws\Exception\AwsException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class UserDashboard extends Component
{
    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        // Ambil daftar bucket
        $buckets = StorageBucket::where('user_id', $user->id)->latest()->get();

        return view('user-dashboard', [
            'user' => $user,
            'buckets' => $buckets,
            'bucketCount' => $buckets->count(),
        ]);
    }
}