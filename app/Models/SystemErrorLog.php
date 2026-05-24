<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'service_module',
        'error_level',
        'message',
        'stack_trace',
        'resolved',
    ];

    protected $casts = [
        'stack_trace' => 'array',
        'resolved' => 'boolean',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}
