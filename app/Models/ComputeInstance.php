<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComputeInstance extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'plan',
        'name',
        'os',
        'ip_address',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'usage_hours' => 'float',
        'price_per_hour' => 'float',
        'cost' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function stateLogs()
    {
        return $this->hasMany(ResourceStateLog::class, 'resource_id')->where('resource_type', 'compute_instance');
    }
}
