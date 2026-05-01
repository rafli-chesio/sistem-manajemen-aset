<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_UNIQUE      = 'UNIQUE';
    const TYPE_CONSUMABLE  = 'CONSUMABLE';

    const STATUS_AVAILABLE = 'AVAILABLE';
    const STATUS_BORROWED  = 'BORROWED';
    const STATUS_DAMAGED   = 'DAMAGED';
    const STATUS_LOST      = 'LOST';

    const CONDITION_GOOD    = 'GOOD';
    const CONDITION_FAIR    = 'FAIR';
    const CONDITION_POOR    = 'POOR';
    const CONDITION_DAMAGED = 'DAMAGED';

    protected $fillable = [
        'name',
        'brand',
        'year',
        'condition',
        'category_id',
        'location_id',
        'department',
        'type',
        'stock',
        'status',
        'description',
        'asset_code',
    ];

    protected function casts(): array
    {
        return [
            'year'  => 'integer',
            'stock' => 'integer',
        ];
    }

    // ----- Relationships -----

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function images()
    {
        return $this->hasMany(AssetImage::class);
    }

    public function borrowItems()
    {
        return $this->hasMany(BorrowItem::class);
    }

    // ----- Helpers -----

    public function isUnique(): bool
    {
        return $this->type === self::TYPE_UNIQUE;
    }

    public function isConsumable(): bool
    {
        return $this->type === self::TYPE_CONSUMABLE;
    }

    public function isAvailable(): bool
    {
        if ($this->isUnique()) {
            return $this->status === self::STATUS_AVAILABLE;
        }

        return $this->stock > 0;
    }

    public function hasEnoughStock(int $qty): bool
    {
        if ($this->isUnique()) {
            return $this->status === self::STATUS_AVAILABLE && $qty === 1;
        }

        return $this->stock >= $qty;
    }
}
