<?php

namespace App\Notifications;

use App\Models\BorrowRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewBorrowRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly BorrowRequest $borrowRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'borrow.created',
            'message'    => "{$this->borrowRequest->user->name} mengajukan peminjaman baru #{$this->borrowRequest->id}.",
            'request_id' => $this->borrowRequest->id,
            'url'        => "/borrows/{$this->borrowRequest->id}",
        ];
    }
}
