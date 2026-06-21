<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'animal_avatar',
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
            'two_factor_confirmed_at'  => 'datetime',
        ];
    }

    // ── Paket storage ─────────────────────────────────────────────────────────
    const STORAGE_PLANS = [
        'free'     => ['label' => 'Gratis',   'quota_gb' => 30,   'price_month' => 0,       'color' => '#6b7280'],
        'starter'  => ['label' => 'Starter',  'quota_gb' => 100,  'price_month' => 15000,   'color' => '#2563eb'],
        'pro'      => ['label' => 'Pro',       'quota_gb' => 512,  'price_month' => 50000,   'color' => '#7c3aed'],
        'business' => ['label' => 'Business', 'quota_gb' => 2048, 'price_month' => 150000,  'color' => '#b45309'],
    ];

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isUser(): bool  { return $this->role === 'user'; }

    public function isStorageFull(): bool
    {
        return $this->storage_used_gb >= $this->storage_quota_gb;
    }

    public function storageUsedPercent(): float
    {
        if ($this->storage_quota_gb <= 0) return 100;
        return round(($this->storage_used_gb / $this->storage_quota_gb) * 100, 1);
    }

    public function storagePlanLabel(): string
    {
        return self::STORAGE_PLANS[$this->storage_plan ?? 'free']['label'] ?? 'Gratis';
    }

    public function canRunInstances(): bool
    {
        return $this->account_status === 'ACTIVE'
            && (float) $this->balance > 0
            && !$this->isStorageFull();
    }

    public static function animalAvatars(): array
    {
        return ['🐱','🐶','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐸','🐧','🐦','🦆','🦉','🦋','🐢','🐠'];
    }

    // ── Relationships ─────────────────────────────────────────────────────────
    public function computeInstances()   { return $this->hasMany(ComputeInstance::class); }
    public function storageBuckets()     { return $this->hasMany(StorageBucket::class); }
    public function managedDatabases()   { return $this->hasMany(ManagedDatabase::class); }
    public function activityLogs()       { return $this->hasMany(ActivityLog::class); }
    public function billingTransactions(){ return $this->hasMany(BillingTransaction::class); }
}