<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = ['name', 'email', 'password', 'role', 'animal_avatar', 'balance', 'account_status'];

    protected $hidden = ['password', 'remember_token', 'role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'balance'           => 'decimal:2',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isUser(): bool  { return $this->role === 'user'; }

    public static function animalAvatars(): array
    {
        return ['🐱','🐶','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐸','🐧','🐦','🦆','🦉','🦋','🐢','🐠'];
    }

    // Relationships for Cloud Services
    public function computeInstances()
    {
        return $this->hasMany(ComputeInstance::class);
    }

    public function storageBuckets()
    {
        return $this->hasMany(StorageBucket::class);
    }

    public function managedDatabases()
    {
        return $this->hasMany(ManagedDatabase::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function billingTransactions()
    {
        return $this->hasMany(BillingTransaction::class);
    }
}