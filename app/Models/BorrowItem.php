<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'asset_id',
        'quantity',
        'condition_before',
        'condition_after',
        'returned_by',
        'returned_at',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'integer',
            'returned_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(BorrowRequest::class, 'request_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function returnItem()
    {
        return $this->hasOne(ReturnItem::class, 'borrow_item_id');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
