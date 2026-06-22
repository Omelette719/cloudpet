<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    protected BillingService $billing;

    public function __construct(BillingService $billing)
    {
        $this->billing = $billing;
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $user->refresh();

        return response()->json([
            'balance'                => (float) $user->balance,
            'account_status'         => $user->account_status,
            'membership_plan'        => $user->storage_plan ?? 'free',
            'membership_label'       => $user->membershipLabel(),
            'membership_expires_at'  => $user->storage_plan_expires_at?->toDateString(),
            'allowed_compute_plans'  => $user->allowedComputePlans(),
            'volume_used_gb'         => $user->volumeUsedGb(),
            'volume_limit_gb'        => $user->volumeLimitGb(),
            'volume_used_pct'        => $user->volumeUsedPercent(),
            'max_buckets'            => $user->maxBuckets(),
            'bucket_count'           => $user->storageBuckets()->count(),
            'can_run_instances'      => $user->canRunInstances(),
        ]);
    }

    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:10000000',
            'note'   => 'nullable|string|max:100',
        ]);

        try {
            $tx = $this->billing->topUp($request->user(), (float) $request->amount, $request->note ?? '');
            return response()->json(['success' => true, 'transaction' => $tx, 'balance' => (float) $request->user()->fresh()->balance]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function history(Request $request)
    {
        $history = $this->billing->getHistory($request->user(), 30);
        return response()->json($history);
    }

    public function storageSubscribe(Request $request)
    {
        $request->validate(['plan' => 'required|string|in:free,starter,pro,business']);

        try {
            $this->billing->subscribeStorage($request->user(), $request->plan);
            $user = $request->user()->fresh();
            return response()->json([
                'success'           => true,
                'membership_plan'   => $user->storage_plan,
                'membership_label'  => $user->membershipLabel(),
                'volume_limit_gb'   => $user->volumeLimitGb(),
                'balance'           => (float) $user->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
