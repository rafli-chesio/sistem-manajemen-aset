<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BorrowRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Root redirect ──────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard'));

// ── Authenticated routes ────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard — all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze default) — all authenticated users
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Notifications — all authenticated users ───────────────────────────────
    Route::get('/notifications',        [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read',  [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count',  [NotificationController::class, 'count'])->name('notifications.count');

    // ── Assets (view: all | create/edit/delete: super_admin + kajur) ──────────
    Route::get('/assets',               [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/{asset}',       [AssetController::class, 'show'])->name('assets.show');

    Route::middleware('role:super_admin|kajur')->group(function () {
        Route::get('/assets/create',        [AssetController::class, 'create'])->name('assets.create');
        Route::post('/assets',              [AssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{asset}/edit',  [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}',       [AssetController::class, 'update'])->name('assets.update');
        Route::patch('/assets/{asset}',     [AssetController::class, 'update']);
        Route::post('/assets/{asset}/mark-lost', [AssetController::class, 'markLost'])->name('assets.mark-lost');
        Route::delete('/asset-images/{image}',   [AssetController::class, 'destroyImage'])->name('asset-images.destroy');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::delete('/assets/{asset}',    [AssetController::class, 'destroy'])->name('assets.destroy');
    });

    // ── Borrow Requests ───────────────────────────────────────────────────────
    // View all: super_admin, viewer | View own + Create: kajur | Approve/Reject: super_admin
    Route::get('/borrows',              [BorrowRequestController::class, 'index'])->name('borrows.index');
    Route::get('/borrows/{borrow}',     [BorrowRequestController::class, 'show'])->name('borrows.show');

    Route::middleware('role:super_admin|kajur')->group(function () {
        Route::get('/borrows/create',   [BorrowRequestController::class, 'create'])->name('borrows.create');
        Route::post('/borrows',         [BorrowRequestController::class, 'store'])->name('borrows.store');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::post('/borrows/{borrow}/approve', [BorrowRequestController::class, 'approve'])->name('borrows.approve');
        Route::post('/borrows/{borrow}/reject',  [BorrowRequestController::class, 'reject'])->name('borrows.reject');
    });

    // ── Returns ───────────────────────────────────────────────────────────────
    // Submit: kajur | View all: super_admin, viewer
    Route::middleware('role:super_admin|kajur')->group(function () {
        Route::get('/borrows/{borrow}/return',  [ReturnController::class, 'create'])->name('returns.create');
        Route::post('/borrows/{borrow}/return', [ReturnController::class, 'store'])->name('returns.store');
    });

    // ── Users — Super Admin only ──────────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    // ── Reports — super_admin + viewers ──────────────────────────────────────
    Route::middleware('role:super_admin|viewer')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // ── Audit Logs — Super Admin only ─────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__ . '/auth.php';
