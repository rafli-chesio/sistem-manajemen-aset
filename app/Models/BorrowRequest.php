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

    public function isReturnable(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_OVERDUE]);
    }

    /**
     * Scope: only requests where all items are for UNIQUE assets (eligible for return).
     */
    public function scopeReturnable($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_OVERDUE]);
    }
}
