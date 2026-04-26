<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Store a newly created location (AJAX/JSON endpoint).
     * Used by the inline "Tambah Lokasi" form on the asset create/edit page.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('location.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:locations,name'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.unique'   => 'Lokasi dengan nama ini sudah ada.',
        ]);

        $location = Location::create(['name' => $validated['name']]);

        return response()->json([
            'id'   => $location->id,
            'name' => $location->name,
        ], 201);
    }

    /**
     * Delete a location.
     */
    public function destroy(Location $location): JsonResponse
    {
        $this->authorize('location.delete');

        if ($location->assets()->exists()) {
            return response()->json(['message' => 'Lokasi masih digunakan oleh aset.'], 422);
        }

        $location->delete();

        return response()->json(['message' => 'Lokasi berhasil dihapus.']);
    }
}
