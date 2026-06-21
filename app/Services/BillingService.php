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

            // Kalau user di-suspend karena saldo habis, aktifkan lagi
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
        // Proses semua user yang punya instance RUNNING
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
                // Saldo tidak cukup — stop semua instance, suspend akun
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

            // Update usage_hours dan cost per instance
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

    // ─── Langganan storage ────────────────────────────────────────────────────

    public function subscribeStorage(User $user, string $plan): void
    {
        $plans = User::STORAGE_PLANS;
        if (!isset($plans[$plan])) throw new \InvalidArgumentException('Paket tidak valid.');

        $planConf    = $plans[$plan];
        $priceMonth  = $planConf['price_month'];
        $quotaGb     = $planConf['quota_gb'];

        if ($plan === 'free') {
            // Downgrade ke free — cek apakah usage masih dalam batas
            if ($user->storage_used_gb > 30) {
                throw new \RuntimeException('Hapus beberapa instance/file dulu sebelum downgrade ke paket gratis (30 GB).');
            }
            $user->storage_plan           = 'free';
            $user->storage_quota_gb       = 30;
            $user->storage_plan_expires_at = null;
            $user->save();
            return;
        }

        if ((float) $user->balance < $priceMonth) {
            throw new \RuntimeException('Saldo tidak cukup. Dibutuhkan Rp ' . number_format($priceMonth, 0, ',', '.'));
        }

        DB::transaction(function () use ($user, $plan, $planConf, $priceMonth, $quotaGb) {
            $user->decrement('balance', $priceMonth);
            $user->storage_plan            = $plan;
            $user->storage_quota_gb        = $quotaGb;
            $user->storage_plan_expires_at = now()->addMonth();
            $user->save();

            BillingTransaction::create([
                'id'               => Str::uuid(),
                'user_id'          => $user->id,
                'amount'           => -$priceMonth,
                'transaction_type' => 'MONTHLY_BILLING',
                'description'      => 'Langganan storage ' . $planConf['label'] . ' (' . $quotaGb . ' GB)',
            ]);
        });
    }

    // ─── Sync storage usage ───────────────────────────────────────────────────

    public function syncStorageUsage(User $user): float
    {
        $instances = ComputeInstance::where('user_id', $user->id)
            ->whereNotIn('status', ['TERMINATED'])
            ->whereNotNull('metadata')
            ->get();

        $totalBytes = 0;
        foreach ($instances as $instance) {
            $path = $instance->metadata['storage_path'] ?? null;
            if ($path && is_dir($path)) {
                $totalBytes += $this->dirSize($path);
            }
        }

        $totalGb = round($totalBytes / (1024 ** 3), 3);
        $user->storage_used_gb = $totalGb;
        $user->save();

        // Kalau storage penuh, stop semua instance
        if ($user->isStorageFull()) {
            $running = ComputeInstance::where('user_id', $user->id)
                ->where('status', 'RUNNING')
                ->get();
            foreach ($running as $instance) {
                $this->compute->changeStatus($instance, 'stop');
            }
        }

        return $totalGb;
    }

    protected function dirSize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }
        return $size;
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