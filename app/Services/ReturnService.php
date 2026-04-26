<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\Return_;
use App\Models\ReturnImage;
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
     * - No partial returns — all items in the request are returned at once
     * - Consumable items cannot be returned (filtered out automatically)
     */
    public function processReturn(
        BorrowRequest $request,
        User $processor,
        string $conditionAfter,
        array $images,
        ?string $notes = null
    ): Return_ {
        if (!$request->isReturnable()) {
            throw new \RuntimeException('Permintaan ini tidak dapat dikembalikan dalam status saat ini.');
        }

        if (empty($images)) {
            throw new \RuntimeException('Minimal 1 foto harus diunggah saat pengembalian.');
        }

        // Check: no consumable-only requests eligible for return
        $hasUniqueItems = $request->items->contains(
            fn($item) => $item->asset->isUnique()
        );
        if (!$hasUniqueItems) {
            throw new \RuntimeException('Barang habis pakai tidak bisa dikembalikan.');
        }

        return DB::transaction(function () use ($request, $processor, $conditionAfter, $images, $notes) {
            // ── Create return record ──────────────────────────────────
            $return = Return_::create([
                'request_id'   => $request->id,
                'processed_by' => $processor->id,
                'returned_at'  => now(),
                'condition_after' => $conditionAfter,
                'notes'        => $notes,
            ]);

            // ── Store return photos ────────────────────────────────────
            $imagePaths = [];
            foreach ($images as $image) {
                if ($image instanceof UploadedFile) {
                    $path = $image->store('returns/' . $return->id, 'public');
                    ReturnImage::create(['return_id' => $return->id, 'path' => $path]);
                    $imagePaths[] = $path;
                }
            }

            // ── Update unique asset statuses back to AVAILABLE ─────────
            foreach ($request->items as $item) {
                $asset = $item->asset;
                if ($asset->isUnique()) {
                    $asset->update([
                        'status'    => Asset::STATUS_AVAILABLE,
                        'condition' => $conditionAfter,
                    ]);
                }
                // Consumable items are NOT returned — stock was already deducted
            }

            // ── Update borrow request status ──────────────────────────
            $request->update(['status' => BorrowRequest::STATUS_RETURNED]);

            // ── Audit log with photo references ───────────────────────
            $this->auditLog->log('return.processed', $return, [
                'request_id'    => $request->id,
                'condition'     => $conditionAfter,
                'photo_count'   => count($imagePaths),
                'photo_paths'   => $imagePaths,
                'processed_by'  => $processor->name,
            ]);

            return $return->load(['images', 'borrowRequest.items.asset']);
        });
    }
}
