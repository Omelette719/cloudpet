<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManagedDatabase extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id', 'user_id', 'plan_id', 'engine',
        'db_name', 'db_user', 'db_password',
        'host', 'port', 'rds_identifier',
        'status', 'metadata', 'provision_log',
        'price_per_hour', 'usage_hours', 'cost',
        'started_at', 'stopped_at',
    ];

    protected $hidden = ['db_password'];

    protected function casts(): array
    {
        return [
            'metadata'       => 'array',
            'price_per_hour' => 'decimal:2',
            'usage_hours'    => 'decimal:4',
            'cost'           => 'decimal:2',
            'started_at'     => 'datetime',
            'stopped_at'     => 'datetime',
        ];
    }

    public function user()   { return $this->belongsTo(User::class); }
    public function plan()   { return $this->belongsTo(Plan::class, 'plan_id'); }
}
