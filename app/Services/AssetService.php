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


    public function create(array $data, array $images = []): Asset
    {
        return DB::transaction(function () use ($data, $images) {
 
            if (empty($data['asset_code'])) {
                $data['asset_code'] = $this->generateAssetCode($data['type']);
            }

            if ($data['type'] === Asset::TYPE_UNIQUE) {
                $data['stock']  = null;
                // Pakai status dari form jika ada, fallback ke AVAILABLE
                $data['status'] = $data['status'] ?? Asset::STATUS_AVAILABLE;
            } else {

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


    public function update(Asset $asset, array $data, array $newImages = []): Asset
    {
        return DB::transaction(function () use ($asset, $data, $newImages) {
            $old = $asset->only(['name', 'condition', 'status', 'stock']);

            if (isset($data['type']) && $asset->type !== $data['type'] && $asset->borrowItems()->exists()) {
                throw new \RuntimeException(
                    'Tipe aset tidak bisa diubah karena sudah ada riwayat peminjaman.'
                );
            }

            if ($data['type'] === Asset::TYPE_UNIQUE) {
                $data['stock'] = null;

            } else {

                $data['stock']  = $data['stock'] ?? $asset->stock ?? 0;
                $data['status'] = Asset::STATUS_AVAILABLE;
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


    public function deleteImage(AssetImage $image): void
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    public function markLost(Asset $asset): void
    {
        if (!$asset->isUnique()) {
            throw new \RuntimeException('Hanya aset unik yang bisa ditandai hilang.');
        }

        $asset->update(['status' => Asset::STATUS_LOST]);
        $this->auditLog->log('asset.marked_lost', $asset, ['name' => $asset->name]);
    }


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
        $year   = date('Y');

        do {
            $count    = Asset::withTrashed()->count() + 1;
            $sequence = str_pad($count, 5, '0', STR_PAD_LEFT);
            $code     = "{$prefix}-{$year}-{$sequence}";
        } while (Asset::withTrashed()->where('asset_code', $code)->exists());

        return $code;
    }
}
