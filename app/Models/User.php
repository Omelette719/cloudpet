<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'id', 'name', 'email', 'password', 'role', 'animal_avatar',
        'balance', 'account_status',
        'storage_quota_gb', 'storage_used_gb', 'storage_plan', 'storage_plan_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'role'];

    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'balance'                  => 'decimal:2',
            'storage_used_gb'          => 'decimal:3',
            'storage_plan_expires_at'  => 'datetime',
        ];
    }

    // ── Membership tiers ─────────────────────────────────────────────────────

    const MEMBERSHIP_PLANS = [
        'free' => [
            'label'           => 'Gratis',
            'max_vcpu'        => 1,
            'max_ram_mb'      => 2048,
            'allowed_db_plans'=> ['db-micro'],
            'volume_limit_gb' => 30,
            'max_buckets'     => 1,
            'max_databases'   => 1,
            'price_month'     => 0,
            'color'           => '#6b7280',
        ],
        'starter' => [
            'label'           => 'Starter',
            'max_vcpu'        => 2,
            'max_ram_mb'      => 4096,
            'allowed_db_plans'=> ['db-micro', 'db-small'],
            'volume_limit_gb' => 100,
            'max_buckets'     => 3,
            'max_databases'   => 3,
            'price_month'     => 15000,
            'color'           => '#2563eb',
        ],
        'pro' => [
            'label'           => 'Pro',
            'max_vcpu'        => 4,
            'max_ram_mb'      => 8192,
            'allowed_db_plans'=> ['db-micro', 'db-small', 'db-medium'],
            'volume_limit_gb' => 512,
            'max_buckets'     => 10,
            'max_databases'   => 5,
            'price_month'     => 50000,
            'color'           => '#7c3aed',
        ],
        'business' => [
            'label'           => 'Business',
            'max_vcpu'        => 8,
            'max_ram_mb'      => 16384,
            'allowed_db_plans'=> ['db-micro', 'db-small', 'db-medium'],
            'volume_limit_gb' => 2048,
            'max_buckets'     => 50,
            'max_databases'   => 20,
            'price_month'     => 150000,
            'color'           => '#b45309',
        ],
    ];

    // Backward compat alias
    const STORAGE_PLANS = self::MEMBERSHIP_PLANS;

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isUser(): bool  { return $this->role === 'user'; }

    // ── Membership helpers ───────────────────────────────────────────────────

    public function membershipTier(): array
    {
        return self::MEMBERSHIP_PLANS[$this->storage_plan ?? 'free'] ?? self::MEMBERSHIP_PLANS['free'];
    }

    public function membershipLabel(): string
    {
        return $this->membershipTier()['label'];
    }

    public function maxVcpu(): int
    {
        return $this->membershipTier()['max_vcpu'];
    }

    public function maxRamMb(): int
    {
        return $this->membershipTier()['max_ram_mb'];
    }

    public function canUseResources(int $vcpu, int $ramMb): bool
    {
        return $vcpu >= 1 && $vcpu <= $this->maxVcpu()
            && $ramMb >= 512 && $ramMb <= $this->maxRamMb();
    }

    // ── Volume (block storage) limits ────────────────────────────────────────

    public function volumeLimitGb(): int
    {
        return $this->membershipTier()['volume_limit_gb'];
    }

    public function volumeUsedGb(): int
    {
        return (int) $this->blockVolumes()
            ->whereNotIn('status', ['ERROR'])
            ->sum('size_gb');
    }

    public function isVolumeFull(): bool
    {
        return $this->volumeUsedGb() >= $this->volumeLimitGb();
    }

    public function volumeUsedPercent(): float
    {
        $limit = $this->volumeLimitGb();
        if ($limit <= 0) return 100;
        return round(($this->volumeUsedGb() / $limit) * 100, 1);
    }

    // ── Bucket limits ────────────────────────────────────────────────────────

    public function maxBuckets(): int
    {
        return $this->membershipTier()['max_buckets'];
    }

    public function canCreateBucket(): bool
    {
        return $this->storageBuckets()->count() < $this->maxBuckets();
    }

    // ── Database limits ──────────────────────────────────────────────────────

    public function allowedDbPlans(): array
    {
        return $this->membershipTier()['allowed_db_plans'];
    }

    public function canUseDbPlan(string $planName): bool
    {
        return in_array($planName, $this->allowedDbPlans());
    }

    public function maxDatabases(): int
    {
        return $this->membershipTier()['max_databases'];
    }

    public function canCreateDatabase(): bool
    {
        return $this->managedDatabases()
            ->whereNotIn('status', ['TERMINATED'])
            ->count() < $this->maxDatabases();
    }

    // ── Instance check ───────────────────────────────────────────────────────

    public function canRunInstances(): bool
    {
        return $this->account_status === 'ACTIVE'
            && (float) $this->balance > 0;
    }

    public static function animalAvatars(): array
    {
        return ['🐱','🐶','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐸','🐧','🐦','🦆','🦉','🦋','🐢','🐠'];
    }

    // ── Relationships ─────────────────────────────────────────────────────────
    public function computeInstances()   { return $this->hasMany(ComputeInstance::class); }
    public function storageBuckets()     { return $this->hasMany(StorageBucket::class); }
    public function managedDatabases()   { return $this->hasMany(ManagedDatabase::class); }
    public function blockVolumes()       { return $this->hasMany(BlockVolume::class); }
    public function activityLogs()       { return $this->hasMany(ActivityLog::class); }
    public function billingTransactions(){ return $this->hasMany(BillingTransaction::class); }
}
