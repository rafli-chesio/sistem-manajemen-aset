<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'borrow_item_id',
        'condition_after',
        'notes',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function returnRecord(): BelongsTo
    {
        return $this->belongsTo(AssetReturn::class, 'return_id');
    }

    public function borrowItem(): BelongsTo
    {
        return $this->belongsTo(BorrowItem::class, 'borrow_item_id');
    }

    // ── Helper ──────────────────────────────────────────────────────────────

    /** Apakah kondisi menurun dibanding sebelum dipinjam? */
    public function isConditionWorsened(): bool
    {
        $order = ['GOOD' => 4, 'FAIR' => 3, 'POOR' => 2, 'DAMAGED' => 1];
        $before = $this->borrowItem?->condition_before ?? 'GOOD';

        return ($order[$this->condition_after] ?? 4) < ($order[$before] ?? 4);
    }
}
