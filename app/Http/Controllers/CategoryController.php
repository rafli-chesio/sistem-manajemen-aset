<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Store a newly created category (AJAX/JSON endpoint).
     * Used by the inline "Tambah Kategori" form on the asset create/edit page.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('category.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => 'Kategori dengan nama ini sudah ada.',
        ]);

        $category = Category::create(['name' => $validated['name']]);

        return response()->json([
            'id'   => $category->id,
            'name' => $category->name,
        ], 201);
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('category.delete');

        if ($category->assets()->exists()) {
            return response()->json(['message' => 'Kategori masih digunakan oleh aset.'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
