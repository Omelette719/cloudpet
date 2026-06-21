<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageBucket extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'bucket_name',
        // 'visibility',  <-- HAPUS BARIS INI
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
