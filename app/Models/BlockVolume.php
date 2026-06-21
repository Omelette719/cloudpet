<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockVolume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'compute_instance_id',
        'volume_name',
        'size_gb',
        'status',
        'provider_volume_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function computeInstance()
    {
        return $this->belongsTo(ComputeInstance::class);
    }
}