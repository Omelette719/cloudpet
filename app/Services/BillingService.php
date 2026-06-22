<?php

namespace App\Services;

use App\Models\BillingTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\ComputeInstance;
use App\Models\User;
use Illuminate\Support\Str;

class BillingService
{
    protected ComputeService $compute;

    public function __construct(ComputeService $compute)
    {
        $this->compute = $compute;
    }

    // ─── Top-up saldo ─────────────────────────────────────────────────────────

    public function topUp(User $user, float $amount, string $note = ''): BillingTransaction
    {
        if ($amount <= 0) throw new \InvalidArgumentException('Jumlah top-up harus lebih dari 0.');

        $tx = null;
        DB::transaction(function () use ($user, $amount, $note, &$tx) {
            $user->increment('balance', $amount);
            $user->refresh();

            if ($user->account_status === 'SUSPENDED' && $user->balance > 0) {
                $user->account_status = 'ACTIVE';
                $user->save();
            }

            $tx = BillingTransaction::create([
                'id'               => Str::uuid(),
                'user_id'          => $user->id,
                'amount'           => $amount,
                'transaction_type' => 'TOPUP',
                'description'      => $note ?: 'Top-up saldo manual',
            ]);
        });

        return $tx;
    }

    // ─── Billing tick (dipanggil tiap jam dari scheduler) ────────────────────

    public function runHourlyTick(): void
    {
        $userIds = ComputeInstance::where('status', 'RUNNING')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;
            $this->chargeUser($user);
        }
    }

    public function chargeUser(User $user): void
    {
        $instances = ComputeInstance::where('user_id', $user->id)
            ->where('status', 'RUNNING')
            ->get();

        if ($instances->isEmpty()) return;

        $totalCost = $instances->sum(fn($i) => (float) ($i->price_per_hour ?? $i->metadata['price_per_hour'] ?? 0));

        if ($totalCost <= 0) return;

        DB::transaction(function () use ($user, $instances, $totalCost) {
            $newBalance = (float) $user->balance - $totalCost;

            if ($newBalance < 0) {
                foreach ($instances as $instance) {
                    $this->compute->changeStatus($instance, 'stop');
                }
                $user->balance       = 0;
                $user->account_status = 'SUSPENDED';
                $user->save();

                BillingTransaction::create([
                    'id'               => Str::uuid(),
                    'user_id'          => $user->id,
                    'amount'           => -(float) $user->balance,
                    'transaction_type' => 'HOURLY_USAGE',
                    'description'      => 'Saldo habis — semua instance dihentikan otomatis',
                ]);
                return;
            }

            $user->balance = $newBalance;
            $user->save();

            foreach ($instances as $instance) {
                $instance->usage_hours    = ($instance->usage_hours ?? 0) + 1;
                $instance->cost           = ($instance->cost ?? 0) + ($instance->price_per_hour ?? 0);
                $instance->save();
            }

            $desc = $instances->map(fn($i) => ($i->metadata['type'] ?? 'vm') . ':' . $i->name)->join(', ');
            BillingTransaction::create([
                'id'               => Str::uuid(),
                'user_id'          => $user->id,
                'amount'           => -$totalCost,
                'transaction_type' => 'HOURLY_USAGE',
                'description'      => 'Penggunaan per jam: ' . $desc,
            ]);
        });
    }

    // ─── Langganan membership ────────────────────────────────────────────────

    public function subscribeStorage(User $user, string $plan): void
    {
        $plans = User::MEMBERSHIP_PLANS;
        if (!isset($plans[$plan])) throw new \InvalidArgumentException('Paket tidak valid.');

        $planConf   = $plans[$plan];
        $priceMonth = $planConf['price_month'];

        if ($plan === 'free') {
            if ($user->volumeUsedGb() > $planConf['volume_limit_gb']) {
                throw new \RuntimeException(
                    'Total block storage Anda (' . $user->volumeUsedGb() . ' GB) melebihi batas paket Gratis (' . $planConf['volume_limit_gb'] . ' GB). Hapus beberapa volume terlebih dahulu.'
                );
            }
            if ($user->storageBuckets()->count() > $planConf['max_buckets']) {
                throw new \RuntimeException(
                    'Jumlah bucket Anda melebihi batas paket Gratis (' . $planConf['max_buckets'] . ' bucket). Hapus beberapa bucket terlebih dahulu.'
                );
            }
            $user->storage_plan            = 'free';
            $user->storage_quota_gb        = $planConf['volume_limit_gb'];
            $user->storage_plan_expires_at = null;
            $user->save();
            return;
        }

        if ((float) $user->balance < $priceMonth) {
            throw new \RuntimeException('Saldo tidak cukup. Dibutuhkan Rp ' . number_format($priceMonth, 0, ',', '.'));
        }

        DB::transaction(function () use ($user, $plan, $planConf, $priceMonth) {
            $user->decrement('balance', $priceMonth);
            $user->storage_plan            = $plan;
            $user->storage_quota_gb        = $planConf['volume_limit_gb'];
            $user->storage_plan_expires_at = now()->addMonth();
            $user->save();

            BillingTransaction::create([
                'id'               => Str::uuid(),
                'user_id'          => $user->id,
                'amount'           => -$priceMonth,
                'transaction_type' => 'MONTHLY_BILLING',
                'description'      => 'Langganan membership ' . $planConf['label'],
            ]);
        });
    }

    // ─── Riwayat transaksi ────────────────────────────────────────────────────

    public function getHistory(User $user, int $limit = 20): \Illuminate\Support\Collection
    {
        return $user->billingTransactions()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
