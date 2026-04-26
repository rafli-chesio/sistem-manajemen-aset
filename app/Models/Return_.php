<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Named Return_ to avoid conflict with PHP 8's built-in `return` keyword.
 * Table name is `returns`.
 */
class Return_ extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'request_id',
        'processed_by',
        'returned_at',
        'condition_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
        ];
    }

    public function borrowRequest()
    {
        return $this->belongsTo(BorrowRequest::class, 'request_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function images()
    {
        return $this->hasMany(ReturnImage::class, 'return_id');
    }
}
