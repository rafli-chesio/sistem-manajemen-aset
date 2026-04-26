<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetImage;
use App\Models\Category;
use App\Models\Location;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assetService) {}

    public function index(Request $request): Response
    {
        $this->authorize('asset.view');

        $assets = Asset::with(['category', 'location', 'images'])
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('asset_code', 'like', "%{$s}%")
                ->orWhere('brand', 'like', "%{$s}%"))
            ->when($request->type,     fn($q, $t) => $q->where('type', $t))
            ->when($request->status,   fn($q, $s) => $q->where('status', $s))
            ->when($request->category, fn($q, $c) => $q->where('category_id', $c))
            ->when($request->location, fn($q, $l) => $q->where('location_id', $l))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Assets/Index', [
            'assets'     => $assets,
            'categories' => Category::orderBy('name')->get(),
            'locations'  => Location::orderBy('name')->get(),
            'filters'    => $request->only(['search', 'type', 'status', 'category', 'location']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('asset.create');

        return Inertia::render('Assets/Create', [
            'categories' => Category::orderBy('name')->get(),
            'locations'  => Location::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        try {
            $this->assetService->create(
                $request->except(['images']),
                $request->file('images', [])
            );

            return redirect()->route('assets.index')
                ->with('success', 'Aset berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Asset $asset): Response
    {
        $this->authorize('asset.view');

        $asset->load(['category', 'location', 'images',
            'borrowItems.request.user',
        ]);

        return Inertia::render('Assets/Show', [
            'asset' => $asset,
        ]);
    }

    public function edit(Asset $asset): Response
    {
        $this->authorize('asset.edit');

        $asset->load(['images', 'category', 'location']);

        return Inertia::render('Assets/Edit', [
            'asset'      => $asset,
            'categories' => Category::orderBy('name')->get(),
            'locations'  => Location::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        try {
            $this->assetService->update(
                $asset,
                $request->except(['images']),
                $request->file('images', [])
            );

            return redirect()->route('assets.show', $asset)
                ->with('success', 'Aset berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('asset.delete');

        try {
            $this->assetService->delete($asset);
            return redirect()->route('assets.index')
                ->with('success', 'Aset berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroyImage(AssetImage $image): RedirectResponse
    {
        $this->authorize('asset.edit');

        $assetId = $image->asset_id;
        $this->assetService->deleteImage($image);

        return back()->with('success', 'Gambar berhasil dihapus.');
    }

    public function markLost(Asset $asset): RedirectResponse
    {
        $this->authorize('asset.edit');

        try {
            $this->assetService->markLost($asset);
            return back()->with('success', 'Aset ditandai hilang.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
