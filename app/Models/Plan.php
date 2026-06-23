<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'service_type',
        'name',
        'vcpu',
        'ram',
        'storage',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function computeInstances()
    {
        return $this->hasMany(ComputeInstance::class, 'plan_id');
    }

    public function managedDatabases()
    {
        return $this->hasMany(ManagedDatabase::class, 'plan_id');
    }
}
