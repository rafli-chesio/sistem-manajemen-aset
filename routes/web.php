<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BorrowRequestController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
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

    // ── Categories & Locations (JSON endpoints — super_admin only) ─────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::post('/categories',              [CategoryController::class, 'store'])->name('categories.store');
        Route::post('/locations',               [LocationController::class, 'store'])->name('locations.store');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('/locations/{location}',  [LocationController::class, 'destroy'])->name('locations.destroy');
    });

    // ── Assets ────────────────────────────────────────────────────────────────
    // PENTING: route statis (/assets/create) harus SEBELUM route dinamis (/assets/{asset})
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');

    // Create & Store — super_admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/assets/create',             [AssetController::class, 'create'])->name('assets.create');
        Route::post('/assets',                   [AssetController::class, 'store'])->name('assets.store');
    });

    // Edit, Update, Mark Lost — super_admin + kajur
    Route::middleware('role:super_admin|kajur')->group(function () {
        Route::get('/assets/{asset}/edit',       [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}',            [AssetController::class, 'update'])->name('assets.update');
        Route::patch('/assets/{asset}',          [AssetController::class, 'update']);
        Route::post('/assets/{asset}/mark-lost', [AssetController::class, 'markLost'])->name('assets.mark-lost');
        Route::delete('/asset-images/{image}',   [AssetController::class, 'destroyImage'])->name('asset-images.destroy');
    });

    // Delete — super_admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
    });

    // Show (read) — semua authenticated, HARUS SETELAH /assets/create
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

    // ── Borrow Requests ───────────────────────────────────────────────────────
    Route::get('/borrows', [BorrowRequestController::class, 'index'])->name('borrows.index');

    // Buat permintaan — kajur only (admin tidak meminjam aset)
    Route::middleware('role:kajur')->group(function () {
        Route::get('/borrows/create',  [BorrowRequestController::class, 'create'])->name('borrows.create');
        Route::post('/borrows',        [BorrowRequestController::class, 'store'])->name('borrows.store');
    });

    // Approve/Reject — super_admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::post('/borrows/{borrow}/approve', [BorrowRequestController::class, 'approve'])->name('borrows.approve');
        Route::post('/borrows/{borrow}/reject',  [BorrowRequestController::class, 'reject'])->name('borrows.reject');
    });

    // Pengembalian — kajur only (yang meminjam yang mengembalikan)
    Route::middleware('role:kajur')->group(function () {
        Route::get('/borrows/{borrow}/return',  [ReturnController::class, 'create'])->name('returns.create');
        Route::post('/borrows/{borrow}/return', [ReturnController::class, 'store'])->name('returns.store');
    });

    // Show (read) — semua authenticated, HARUS SETELAH /borrows/create
    Route::get('/borrows/{borrow}', [BorrowRequestController::class, 'show'])->name('borrows.show');

    // ── Users — Super Admin only ──────────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    // ── Reports — super_admin + viewer ────────────────────────────────────────
    Route::middleware('role:super_admin|viewer')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // ── Audit Logs — Super Admin only ─────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__ . '/auth.php';
