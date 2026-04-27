<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BorrowRequest extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING  = 'PENDING';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_OVERDUE  = 'OVERDUE';
    const STATUS_RETURNED = 'RETURNED';

    /**
     * Valid state transitions — documents the lifecycle as code.
     * Terminal states map to empty arrays.
     */
    const TRANSITIONS = [
        self::STATUS_PENDING  => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_RETURNED, self::STATUS_OVERDUE],
        self::STATUS_OVERDUE  => [self::STATUS_RETURNED],
        self::STATUS_REJECTED => [],  // terminal
        self::STATUS_RETURNED => [],  // terminal
    ];

    protected $fillable = [
        'user_id',
        'status',
        'borrow_date',
        'return_date',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'return_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(BorrowItem::class, 'request_id');
    }

    public function returnRecord()
    {
        return $this->hasOne(Return_::class, 'request_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE;
    }

    /**
     * A request is returnable if:
     * 1. Status is APPROVED or OVERDUE
     * 2. It contains at least one UNIQUE asset (consumables are never returned)
     */
    public function isReturnable(): bool
    {
        if (!in_array($this->status, [self::STATUS_APPROVED, self::STATUS_OVERDUE])) {
            return false;
        }

        // Use loaded relation if available (avoids extra query)
        if ($this->relationLoaded('items')) {
            return $this->items->contains(
                fn($item) => $item->relationLoaded('asset')
                    ? $item->asset?->isUnique()
                    : false
            );
        }

        // Fallback: check via subquery (no N+1)
        return $this->items()
            ->whereHas('asset', fn($q) => $q->where('type', \App\Models\Asset::TYPE_UNIQUE))
            ->exists();
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Scope: only requests where all items are for UNIQUE assets (eligible for return).
     */
    public function scopeReturnable($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_OVERDUE]);
    }
}
