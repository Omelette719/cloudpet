<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'user_id', 'action', 'resource_type', 'resource_id', 'ip_address',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $resourceType = null, ?string $resourceId = null, ?string $userId = null): static
    {
        /** @var static $log */
        $log = self::create([
            'id'            => (string) Str::uuid(),
            'user_id'       => $userId ?? \Illuminate\Support\Facades\Auth::id(),
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'ip_address'    => \Illuminate\Support\Facades\Request::ip(),
        ]);
        return $log;
    }
}
