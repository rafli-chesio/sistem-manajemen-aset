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
        $user    = auth()->user();
        $isKajur = $user->isKajur();

        $query = Asset::with(['category', 'location', 'images']);

        // RBAC: Kajur only sees their department assets
        if ($isKajur) {
            $userDepartments = is_array($user->department) ? $user->department : [];
            $query->whereIn('department', $userDepartments);
        } else {
            // Admin / Viewer can filter by department
            $query->when($request->department, fn($q, $d) => $q->where('department', $d));
        }

        $assets = $query
            ->when($request->search, fn($q, $s) => $q->where(function($query) use ($s) {
                $query->where('name', 'like', "%{$s}%")
                      ->orWhere('asset_code', 'like', "%{$s}%")
                      ->orWhere('brand', 'like', "%{$s}%");
            }))
            ->when($request->type,      fn($q, $t) => $q->where('type', $t))
            ->when($request->status,    fn($q, $s) => $q->where('status', $s))
            ->when($request->condition, fn($q, $c) => $q->where('condition', $c))
            ->when($request->category,  fn($q, $c) => $q->where('category_id', $c))
            ->when($request->location,  fn($q, $l) => $q->where('location_id', $l))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Flatten departments if they are arrays in DB (JSON)
        $departments = \App\Models\User::whereNotNull('department')
            ->pluck('department')
            ->map(fn($d) => is_array($d) ? $d : (is_string($d) ? json_decode($d, true) ?? [$d] : []))
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Assets/Index', [
            'assets'      => $assets,
            'available_assets' => Asset::where('type', 'FIXED')->where('status', 'AVAILABLE')->count(),
            'borrowed_assets'  => Asset::where('type', 'FIXED')->where('status', 'BORROWED')->count(),
            'maintenance_assets' => Asset::where('status', 'MAINTENANCE')->count(),
            'categories'  => Category::orderBy('name')->get(),
            'locations'   => Location::orderBy('name')->get(),
            'departments' => $departments,
            'filters'     => $request->only(['search', 'type', 'status', 'condition', 'category', 'location', 'department']),
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth()->user()->canManageAssets(), 403);

        $departments = \App\Models\User::whereNotNull('department')
            ->pluck('department')
            ->map(fn($d) => is_array($d) ? $d : (is_string($d) ? json_decode($d, true) ?? [$d] : []))
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Assets/Create', [
            'categories'  => Category::orderBy('name')->get(),
            'locations'   => Location::orderBy('name')->get(),
            'departments' => $departments,
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        try {
            $data = $request->except(['images']);
            $user = auth()->user();
            if ($user->isKajur()) {
                $userDepartments = is_array($user->department) ? $user->department : [];
                // If Kajur submits a department, ensure it's in their allowed list
                if (isset($data['department']) && in_array($data['department'], $userDepartments)) {
                    // Valid
                } else {
                    // Force to their first department if not specified or invalid
                    $data['department'] = $userDepartments[0] ?? null;
                }
            }

            $this->assetService->create(
                $data,
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

        $asset->load(['category', 'location', 'images',
            'borrowItems.request.user',
        ]);

        return Inertia::render('Assets/Show', [
            'asset' => $asset,
        ]);
    }

    public function edit(Asset $asset): Response
    {
        abort_unless(auth()->user()->canManageAssets(), 403);

        $asset->load(['images', 'category', 'location']);

        $departments = \App\Models\User::whereNotNull('department')
            ->pluck('department')
            ->map(fn($d) => is_array($d) ? $d : (is_string($d) ? json_decode($d, true) ?? [$d] : []))
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Assets/Edit', [
            'asset'       => $asset,
            'categories'  => Category::orderBy('name')->get(),
            'locations'   => Location::orderBy('name')->get(),
            'departments' => $departments,
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        try {
            $data = $request->except(['images']);
            $user = auth()->user();
            if ($user->isKajur()) {
                $userDepartments = is_array($user->department) ? $user->department : [];
                if (isset($data['department']) && in_array($data['department'], $userDepartments)) {
                    // Valid
                } else {
                    $data['department'] = $userDepartments[0] ?? null;
                }
            }

            $this->assetService->update(
                $asset,
                $data,
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
        abort_unless(auth()->user()->isAdmin(), 403);

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
        abort_unless(auth()->user()->canManageAssets(), 403);

        $assetId = $image->asset_id;
        $this->assetService->deleteImage($image);

        return back()->with('success', 'Gambar berhasil dihapus.');
    }

    public function markLost(Asset $asset): RedirectResponse
    {
        abort_unless(auth()->user()->canManageAssets(), 403);

        try {
            $this->assetService->markLost($asset);
            return back()->with('success', 'Aset ditandai hilang.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
