<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    // --- Type ---
    const TYPE_FIXED      = 'FIXED';
    const TYPE_CONSUMABLE = 'CONSUMABLE';

    // --- Status ---
    const STATUS_AVAILABLE  = 'AVAILABLE';
    const STATUS_BORROWED   = 'BORROWED';
    const STATUS_MAINTENANCE = 'MAINTENANCE';
    const STATUS_LOST       = 'LOST';
    const STATUS_ARCHIVED   = 'ARCHIVED';

    // --- Condition ---
    const CONDITION_BAIK         = 'BAIK';
    const CONDITION_RUSAK_RINGAN = 'RUSAK_RINGAN';
    const CONDITION_RUSAK_BERAT  = 'RUSAK_BERAT';

    public static array $conditions = [
        self::CONDITION_BAIK         => 'Baik',
        self::CONDITION_RUSAK_RINGAN => 'Rusak Ringan',
        self::CONDITION_RUSAK_BERAT  => 'Rusak Berat',
    ];

    public static array $statuses = [
        self::STATUS_AVAILABLE   => 'Tersedia',
        self::STATUS_BORROWED    => 'Dipinjam',
        self::STATUS_MAINTENANCE => 'Maintenance',
        self::STATUS_LOST        => 'Hilang',
        self::STATUS_ARCHIVED    => 'Diarsipkan',
    ];

    public static array $types = [
        self::TYPE_FIXED      => 'Aset Tetap',
        self::TYPE_CONSUMABLE => 'Barang Habis Pakai',
    ];

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
        'qr_code',
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

    public function stockUsages()
    {
        return $this->hasMany(StockUsage::class);
    }

    // ----- Helpers -----

    public function isFixed(): bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    public function isConsumable(): bool
    {
        return $this->type === self::TYPE_CONSUMABLE;
    }

    public function isAvailable(): bool
    {
        if ($this->isFixed()) {
            return $this->status === self::STATUS_AVAILABLE;
        }

        return $this->stock > 0;
    }

    public function hasEnoughStock(int $qty): bool
    {
        if ($this->isFixed()) {
            return $this->status === self::STATUS_AVAILABLE && $qty === 1;
        }

        return $this->stock >= $qty;
    }

    public function getConditionLabelAttribute(): string
    {
        return self::$conditions[$this->condition] ?? $this->condition;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::$statuses[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::$types[$this->type] ?? $this->type;
    }
}
