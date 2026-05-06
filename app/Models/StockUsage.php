<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockUsage extends Model
{
    protected $fillable = [
        'asset_id',
        'used_by',
        'quantity_used',
        'purpose',
        'notes',
        'performed_at',
    ];

    protected $casts = [
        'performed_at'  => 'datetime',
        'quantity_used' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
