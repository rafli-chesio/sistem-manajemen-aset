<?php

use App\Services\BorrowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ─────────────────────────────────────────────────────────

/**
 * Jalankan setiap tengah malam:
 * 1. Tandai peminjaman yang melewati batas kembali → OVERDUE
 * 2. Auto-reject permintaan PENDING yang sudah > 3 hari tidak diproses
 *
 * Setup di server: tambahkan ke crontab
 *   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 */
Schedule::call(function () {
    app(BorrowService::class)->markOverdue();
})->dailyAt('00:01')->name('borrow.mark-overdue')->withoutOverlapping();

Schedule::call(function () {
    app(BorrowService::class)->expirePending();
})->dailyAt('00:05')->name('borrow.expire-pending')->withoutOverlapping();
