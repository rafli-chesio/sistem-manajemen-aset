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
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
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

    /** Kondisi item ini saat dikembalikan */
    public function returnItem()
    {
        return $this->hasOne(ReturnItem::class, 'borrow_item_id');
    }
}
