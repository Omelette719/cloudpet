<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceStateLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'resource_type',
        'resource_id',
        'old_state',
        'new_state',
        'triggered_by',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}
