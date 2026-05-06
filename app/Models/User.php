<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    // --- Role Constants ---
    const ROLE_ADMIN  = 'ADMIN';
    const ROLE_KAJUR  = 'KAJUR';
    const ROLE_VIEWER = 'VIEWER';

    public static array $roles = [
        self::ROLE_ADMIN  => 'Administrator',
        self::ROLE_KAJUR  => 'Kepala Jurusan',
        self::ROLE_VIEWER => 'Viewer / Staf',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'department',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'department'        => 'array',
        ];
    }

    // --- Role Helpers ---

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKajur(): bool
    {
        return $this->role === self::ROLE_KAJUR;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function canManageAssets(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_KAJUR]);
    }

    public function canApprove(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::$roles[$this->role] ?? $this->role;
    }

    // --- Relationships ---

    public function borrowRequests()
    {
        return $this->hasMany(BorrowRequest::class);
    }

    public function approvedRequests()
    {
        return $this->hasMany(BorrowRequest::class, 'approved_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function stockUsages()
    {
        return $this->hasMany(StockUsage::class, 'used_by');
    }
}
