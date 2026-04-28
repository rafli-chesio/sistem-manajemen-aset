<?php

use App\Services\BorrowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    app(BorrowService::class)->markOverdue();
})->dailyAt('00:01')->name('borrow.mark-overdue')->withoutOverlapping();

Schedule::call(function () {
    app(BorrowService::class)->expirePending();
})->dailyAt('00:05')->name('borrow.expire-pending')->withoutOverlapping();
