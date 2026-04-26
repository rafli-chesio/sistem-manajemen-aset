<?php

namespace App\Notifications;

use App\Models\BorrowRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BorrowRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly BorrowRequest $borrowRequest,
        public readonly string $eventType // 'approved', 'rejected', 'overdue', 'expired'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $messages = [
            'approved' => "Permintaan peminjaman #{$this->borrowRequest->id} Anda telah disetujui.",
            'rejected' => "Permintaan peminjaman #{$this->borrowRequest->id} Anda ditolak. Alasan: " .
                          ($this->borrowRequest->rejection_reason ?? '-'),
            'overdue'  => "Permintaan peminjaman #{$this->borrowRequest->id} telah melewati batas waktu pengembalian!",
            'expired'  => "Permintaan peminjaman #{$this->borrowRequest->id} otomatis ditolak karena tidak diproses dalam 3 hari.",
        ];

        return [
            'type'       => "borrow.{$this->eventType}",
            'message'    => $messages[$this->eventType] ?? "Status permintaan #{$this->borrowRequest->id} telah diperbarui.",
            'request_id' => $this->borrowRequest->id,
            'url'        => "/borrows/{$this->borrowRequest->id}",
        ];
    }
}
