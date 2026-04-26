<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnImage extends Model
{
    use HasFactory;

    protected $fillable = ['return_id', 'path'];

    public function returnRecord()
    {
        return $this->belongsTo(Return_::class, 'return_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
