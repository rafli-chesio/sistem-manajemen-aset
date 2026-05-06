<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BorrowRequestController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
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

    // Profile — all authenticated users
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Notifications — all authenticated users ───────────────────────────────
    Route::get('/notifications',       [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');

    // ── Categories & Locations (ADMIN only) ──────────────────────────────────
    Route::middleware('can:manage-admin')->group(function () {
        Route::post('/categories',              [CategoryController::class, 'store'])->name('categories.store');
        Route::post('/locations',               [LocationController::class, 'store'])->name('locations.store');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('/locations/{location}',  [LocationController::class, 'destroy'])->name('locations.destroy');
    });

    // ── Assets ────────────────────────────────────────────────────────────────
    // IMPORTANT: static routes (/assets/create) MUST be before dynamic (/assets/{asset})
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');

    // QR Code Scan (public, accessible to all logged-in users)
    Route::get('/assets/scan',         [QrCodeController::class, 'scan'])->name('assets.scan');
    Route::get('/assets/{asset}/qr',   [QrCodeController::class, 'generate'])->name('assets.qr');
    Route::post('/assets/{asset}/qr',  [QrCodeController::class, 'assign'])->name('assets.qr.assign');

    // Create & Store — ADMIN only
    Route::get('/assets/create',       [AssetController::class, 'create'])->name('assets.create');
    Route::post('/assets',             [AssetController::class, 'store'])->name('assets.store');

    // Import Excel — ADMIN only
    Route::get('/assets/import',       [ImportController::class, 'create'])->name('assets.import');
    Route::post('/assets/import',      [ImportController::class, 'store'])->name('assets.import.store');
    Route::get('/assets/import/template', [ImportController::class, 'template'])->name('assets.import.template');

    // Edit, Update — ADMIN + KAJUR
    Route::get('/assets/{asset}/edit',       [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}',            [AssetController::class, 'update'])->name('assets.update');
    Route::patch('/assets/{asset}',          [AssetController::class, 'update']);
    Route::post('/assets/{asset}/mark-lost', [AssetController::class, 'markLost'])->name('assets.mark-lost');
    Route::delete('/asset-images/{image}',   [AssetController::class, 'destroyImage'])->name('asset-images.destroy');

    // Delete — ADMIN only
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

    // Show (read) — all authenticated — MUST BE LAST in assets group
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

    // ── Borrow Requests ───────────────────────────────────────────────────────
    Route::get('/borrows', [BorrowRequestController::class, 'index'])->name('borrows.index');

    // Create request — KAJUR + ADMIN
    Route::get('/borrows/create',  [BorrowRequestController::class, 'create'])->name('borrows.create');
    Route::post('/borrows',        [BorrowRequestController::class, 'store'])->name('borrows.store');

    // Approve/Reject — ADMIN only
    Route::post('/borrows/{borrow}/approve', [BorrowRequestController::class, 'approve'])->name('borrows.approve');
    Route::post('/borrows/{borrow}/reject',  [BorrowRequestController::class, 'reject'])->name('borrows.reject');

    // Return — KAJUR + ADMIN
    Route::get('/borrows/{borrow}/return',  [ReturnController::class, 'create'])->name('returns.create');
    Route::post('/borrows/{borrow}/return', [ReturnController::class, 'store'])->name('returns.store');

    // Show — all authenticated — MUST BE LAST in borrows group
    Route::get('/borrows/{borrow}', [BorrowRequestController::class, 'show'])->name('borrows.show');

    // ── Users — ADMIN only ────────────────────────────────────────────────────
    Route::resource('users', UserController::class)->except(['show']);

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::get('/reports',       [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf',   [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/excel', [ReportController::class, 'exportExcel'])->name('reports.excel');

    // ── Audit Logs — ADMIN only ───────────────────────────────────────────────
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__ . '/auth.php';
