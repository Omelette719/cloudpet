<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Models\StorageBucket;
use App\Models\BlockVolume;
use App\Models\BillingTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;

#[Layout('layouts::app')]
class UserDashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        $bucketCount = StorageBucket::where('user_id', $user->id)->count();

        $totalBilling = BillingTransaction::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        return view('user-dashboard', [
            'user'         => $user,
            'bucketCount'  => $bucketCount,
            'totalBilling' => $totalBilling,
        ]);
    }
}
