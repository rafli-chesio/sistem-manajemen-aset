<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = true;
    public const UPDATED_AT = null; 

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entity()
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }
}
