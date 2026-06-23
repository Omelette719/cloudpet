<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'action', 'resource_type', 'resource_id', 'ip_address',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $resourceType = null, ?string $resourceId = null, ?int $userId = null): self
    {
        return self::create([
            'id'            => (string) Str::uuid(),
            'user_id'       => $userId ?? auth()->id(),
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'ip_address'    => request()->ip(),
        ]);
    }
}
