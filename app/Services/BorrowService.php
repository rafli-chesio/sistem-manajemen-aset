<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BorrowItem;
use App\Models\BorrowRequest;
use App\Models\User;
use App\Notifications\BorrowRequestStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BorrowService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function createRequest(User $user, array $data, array $items): BorrowRequest
    {
        return DB::transaction(function () use ($user, $data, $items) {

            foreach ($items as $item) {
                $asset = Asset::lockForUpdate()->findOrFail($item['asset_id']);
                if (!$asset->hasEnoughStock((int) $item['quantity'])) {
                    throw new \RuntimeException(
                        "Aset \"{$asset->name}\" tidak tersedia atau stok tidak mencukupi."
                    );
                }
            }

            $request = BorrowRequest::create([
                'user_id'     => $user->id,
                'status'      => BorrowRequest::STATUS_PENDING,
                'borrow_date' => $data['borrow_date'],
                'return_date' => $data['return_date'],
                'notes'       => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $asset = Asset::find($item['asset_id']);
                BorrowItem::create([
                    'request_id'      => $request->id,
                    'asset_id'        => $item['asset_id'],
                    'quantity'        => $item['quantity'],
                    'condition_before' => $asset->condition,
                ]);
            }

            $this->auditLog->log('borrow.created', $request, [
                'user'  => $user->name,
                'items' => collect($items)->pluck('asset_id')->toArray(),
            ]);

            return $request->load(['items.asset', 'user']);
        });
    }


    public function approve(BorrowRequest $request, User $approver): BorrowRequest
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Hanya permintaan PENDING yang bisa disetujui.');
        }

        return DB::transaction(function () use ($request, $approver) {
   
            foreach ($request->items as $item) {
                $asset = Asset::lockForUpdate()->findOrFail($item->asset_id);

                if (!$asset->hasEnoughStock($item->quantity)) {
                    throw new \RuntimeException(
                        "Stok aset \"{$asset->name}\" tidak mencukupi saat persetujuan. " .
                        "Permintaan tidak bisa disetujui."
                    );
                }
            }

            foreach ($request->items as $item) {
                $asset = Asset::find($item->asset_id);
                if ($asset->isUnique()) {
                    $asset->update(['status' => Asset::STATUS_BORROWED]);
                } else {
                    $asset->decrement('stock', $item->quantity);
                }
            }

            $request->update([
                'status'      => BorrowRequest::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->auditLog->log('borrow.approved', $request, [
                'approver' => $approver->name,
            ]);


            $request->user->notify(new BorrowRequestStatusChanged($request, 'approved'));

            return $request->fresh();
        });
    }

    public function reject(BorrowRequest $request, User $approver, string $reason): BorrowRequest
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Hanya permintaan PENDING yang bisa ditolak.');
        }

        return DB::transaction(function () use ($request, $approver, $reason) {
            $request->update([
                'status'           => BorrowRequest::STATUS_REJECTED,
                'approved_by'      => $approver->id,
                'approved_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            $this->auditLog->log('borrow.rejected', $request, [
                'approver' => $approver->name,
                'reason'   => $reason,
            ]);

            $request->user->notify(new BorrowRequestStatusChanged($request, 'rejected'));

            return $request->fresh();
        });
    }


    public function markOverdue(): int
    {
        $overdue = BorrowRequest::where('status', BorrowRequest::STATUS_APPROVED)
            ->where('return_date', '<', today())
            ->get();

        foreach ($overdue as $req) {
            $req->update(['status' => BorrowRequest::STATUS_OVERDUE]);
            $req->user->notify(new BorrowRequestStatusChanged($req, 'overdue'));
            $this->auditLog->log('borrow.overdue', $req, ['return_date' => $req->return_date->toDateString()]);
        }

        return $overdue->count();
    }

    public function expirePending(): int
    {
        $expired = BorrowRequest::where('status', BorrowRequest::STATUS_PENDING)
            ->where('created_at', '<', now()->subDays(3))
            ->get();

        foreach ($expired as $req) {
            $req->update([
                'status'           => BorrowRequest::STATUS_REJECTED,
                'rejection_reason' => 'Permintaan kedaluwarsa otomatis setelah 3 hari tidak diproses.',
            ]);
            $req->user->notify(new BorrowRequestStatusChanged($req, 'expired'));
            $this->auditLog->log('borrow.expired', $req);
        }

        return $expired->count();
    }
}
