<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockVolume extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'compute_instance_id',
        'volume_name',
        'size_gb',
        'status',
        'provider_volume_id',
        'provision_log',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function computeInstance()
    {
        return $this->belongsTo(ComputeInstance::class);
    }
    
    public function isProvisioning(): bool
    {
        return $this->status === 'PROVISIONING';
    }
    
    public function isAvailable(): bool
    {
        return $this->status === 'AVAILABLE';
    }

    public function isError(): bool
    {
        return $this->status === 'ERROR';
    }
}
