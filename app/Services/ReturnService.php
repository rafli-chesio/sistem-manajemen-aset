<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\Return_;
use App\Models\ReturnImage;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReturnService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * Process the return of a borrow request.
     *
     * Business rules enforced:
     * - Request must be APPROVED or OVERDUE
     * - At least 1 photo must be provided
     * - No partial returns — all UNIQUE items returned at once
     * - Consumable items are excluded (stock already deducted at approval)
     * - Per-item condition tracked via return_items table
     *
     * @param  array<int, array{borrow_item_id: int, condition_after: string, notes: ?string}> $itemConditions
     */
    public function processReturn(
        BorrowRequest $request,
        User $processor,
        array $itemConditions,   // ← per-item, bukan satu string lagi
        array $images,
        ?string $notes = null
    ): Return_ {
        if (!$request->isReturnable()) {
            throw new \RuntimeException('Permintaan ini tidak dapat dikembalikan dalam status saat ini.');
        }

        if (empty($images)) {
            throw new \RuntimeException('Minimal 1 foto harus diunggah saat pengembalian.');
        }

        $uniqueItems = $request->items->filter(fn($item) => $item->asset->isUnique());

        if ($uniqueItems->isEmpty()) {
            throw new \RuntimeException('Barang habis pakai tidak bisa dikembalikan.');
        }

        // Validasi: semua UNIQUE item harus ada kondisinya
        $submittedIds = collect($itemConditions)->pluck('borrow_item_id')->toArray();
        foreach ($uniqueItems as $item) {
            if (!in_array($item->id, $submittedIds)) {
                throw new \RuntimeException(
                    "Kondisi untuk \"{$item->asset->name}\" belum diisi."
                );
            }
        }

        return DB::transaction(function () use ($request, $processor, $itemConditions, $images, $notes, $uniqueItems) {

            // ── Tentukan kondisi paling buruk sebagai kondisi global return ──
            $conditionOrder  = ['GOOD' => 4, 'FAIR' => 3, 'POOR' => 2, 'DAMAGED' => 1];
            $worstCondition  = collect($itemConditions)
                ->sortBy(fn($ic) => $conditionOrder[$ic['condition_after']] ?? 4)
                ->first()['condition_after'] ?? 'GOOD';

            // ── Buat record return utama ───────────────────────────────────
            $return = Return_::create([
                'request_id'      => $request->id,
                'processed_by'    => $processor->id,
                'returned_at'     => now(),
                'condition_after' => $worstCondition,  // agregat: kondisi terburuk
                'notes'           => $notes,
            ]);

            // ── Simpan kondisi PER ITEM ke return_items ────────────────────
            foreach ($itemConditions as $ic) {
                ReturnItem::create([
                    'return_id'       => $return->id,
                    'borrow_item_id'  => $ic['borrow_item_id'],
                    'condition_after' => $ic['condition_after'],
                    'notes'           => $ic['notes'] ?? null,
                ]);
            }

            // ── Simpan foto bukti ──────────────────────────────────────────
            $imagePaths = [];
            foreach ($images as $image) {
                if ($image instanceof UploadedFile) {
                    $path = $image->store('returns/' . $return->id, 'public');
                    ReturnImage::create(['return_id' => $return->id, 'path' => $path]);
                    $imagePaths[] = $path;
                }
            }

            // ── Update status & kondisi aset UNIQUE ───────────────────────
            foreach ($uniqueItems as $item) {
                $ic    = collect($itemConditions)->firstWhere('borrow_item_id', $item->id);
                $cond  = $ic['condition_after'] ?? 'GOOD';

                $item->asset->update([
                    'status'    => Asset::STATUS_AVAILABLE,
                    'condition' => $cond,   // ← kondisi spesifik per aset
                ]);
            }

            // ── Update status request ──────────────────────────────────────
            $request->update(['status' => BorrowRequest::STATUS_RETURNED]);

            // ── Audit log ─────────────────────────────────────────────────
            $this->auditLog->log('return.processed', $return, [
                'request_id'   => $request->id,
                'item_conditions' => $itemConditions,
                'photo_count'  => count($imagePaths),
                'processed_by' => $processor->name,
            ]);

            return $return->load(['images', 'returnItems.borrowItem.asset', 'borrowRequest.items.asset']);
        });
    }
}
