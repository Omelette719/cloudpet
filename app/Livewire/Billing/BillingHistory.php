<?php

namespace App\Livewire\Billing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\BillingTransaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

#[Layout('layouts::app')]
class BillingHistory extends Component
{
    public function render()
    {
        // Ambil riwayat tagihan user ini (diurutkan dari yang terbaru)
        $transactions = BillingTransaction::where('user_id', Auth::id())
                            ->orderBy('created_at', 'desc')
                            ->get();

        // Hitung total khusus bulan berjalan
        $totalThisMonth = BillingTransaction::where('user_id', Auth::id())
                            ->whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year)
                            ->sum('amount');

        return view('livewire.billing.billing-history', [
            'transactions' => $transactions,
            'totalThisMonth' => $totalThisMonth,
        ]);
    }
}