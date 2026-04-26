<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * Create a new asset with optional multi-image upload.
     */
    public function create(array $data, array $images = []): Asset
    {
        return DB::transaction(function () use ($data, $images) {
            // Auto-generate asset code if not provided
            if (empty($data['asset_code'])) {
                $data['asset_code'] = $this->generateAssetCode($data['type']);
            }

            // CONSUMABLE must have stock; UNIQUE must not
            if ($data['type'] === Asset::TYPE_UNIQUE) {
                $data['stock']  = null;
                // Pakai status dari form jika ada, fallback ke AVAILABLE
                $data['status'] = $data['status'] ?? Asset::STATUS_AVAILABLE;
            } else {
                // CONSUMABLE: status tidak dipakai per-unit, tapi kolom NOT NULL
                // → simpan AVAILABLE sebagai nilai netral
                $data['stock']  = $data['stock'] ?? 0;
                $data['status'] = Asset::STATUS_AVAILABLE;
            }

            $asset = Asset::create($data);

            if (!empty($images)) {
                $this->storeImages($asset, $images);
            }

            $this->auditLog->log('asset.created', $asset, [
                'name' => $asset->name,
                'type' => $asset->type,
            ]);

            return $asset;
        });
    }

    /**
     * Update an existing asset.
     */
    public function update(Asset $asset, array $data, array $newImages = []): Asset
    {
        return DB::transaction(function () use ($asset, $data, $newImages) {
            $old = $asset->only(['name', 'condition', 'status', 'stock']);

            if ($data['type'] === Asset::TYPE_UNIQUE) {
                $data['stock'] = null;
            } else {
                // Consumable: keep status null
                unset($data['status']);
            }

            $asset->update($data);

            if (!empty($newImages)) {
                $this->storeImages($asset, $newImages);
            }

            $this->auditLog->log('asset.updated', $asset, [
                'old' => $old,
                'new' => $asset->fresh()->only(['name', 'condition', 'status', 'stock']),
            ]);

            return $asset->fresh(['images', 'category', 'location']);
        });
    }

    /**
     * Soft-delete an asset (only if it is not currently borrowed).
     */
    public function delete(Asset $asset): void
    {
        DB::transaction(function () use ($asset) {
            if ($asset->isUnique() && $asset->status === Asset::STATUS_BORROWED) {
                throw new \RuntimeException('Aset sedang dipinjam dan tidak bisa dihapus.');
            }

            $this->auditLog->log('asset.deleted', $asset, ['name' => $asset->name]);
            $asset->delete();
        });
    }

    /**
     * Remove a single image from an asset.
     */
    public function deleteImage(AssetImage $image): void
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    /**
     * Mark a unique asset as LOST.
     */
    public function markLost(Asset $asset): void
    {
        if (!$asset->isUnique()) {
            throw new \RuntimeException('Hanya aset unik yang bisa ditandai hilang.');
        }

        $asset->update(['status' => Asset::STATUS_LOST]);
        $this->auditLog->log('asset.marked_lost', $asset, ['name' => $asset->name]);
    }

    // ── Private helpers ────────────────────────────────────────────────

    private function storeImages(Asset $asset, array $images): void
    {
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $path = $image->store('assets/' . $asset->id, 'public');
                AssetImage::create(['asset_id' => $asset->id, 'path' => $path]);
            }
        }
    }

    private function generateAssetCode(string $type): string
    {
        $prefix = $type === Asset::TYPE_UNIQUE ? 'UNQ' : 'CSM';
        $sequence = str_pad(Asset::withTrashed()->count() + 1, 5, '0', STR_PAD_LEFT);
        return $prefix . '-' . date('Y') . '-' . $sequence;
    }
}
