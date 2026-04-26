<?php

namespace App\Console\Commands;

use App\Services\BorrowService;
use Illuminate\Console\Command;

class ProcessBorrowSchedules extends Command
{
    protected $signature   = 'borrows:process-schedules';
    protected $description = 'Auto-expire pending requests after 3 days and mark overdue approved requests';

    public function __construct(private readonly BorrowService $borrowService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = $this->borrowService->expirePending();
        $this->info("✓ {$expired} pending request(s) auto-rejected (expired).");

        $overdue = $this->borrowService->markOverdue();
        $this->info("✓ {$overdue} approved request(s) marked as overdue.");

        return Command::SUCCESS;
    }
}
