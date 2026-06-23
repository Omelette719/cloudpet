<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageBucket extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'bucket_name',
        'access_key',
        'secret_key',
    ];

    protected $hidden = [
        'secret_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
