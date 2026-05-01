<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('asset.create');
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'brand'       => ['nullable', 'string', 'max:100'],
            'year'        => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'condition'   => ['required', Rule::in(['GOOD', 'FAIR', 'POOR', 'DAMAGED'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'department'  => ['nullable', 'string', 'max:100'],
            'type'        => ['required', Rule::in([Asset::TYPE_UNIQUE, Asset::TYPE_CONSUMABLE])],
            'status'      => ['nullable', Rule::in([Asset::STATUS_AVAILABLE, Asset::STATUS_DAMAGED, Asset::STATUS_LOST])],
            'stock'       => ['required_if:type,CONSUMABLE', 'nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'asset_code'  => ['nullable', 'string', 'max:50', 'unique:assets,asset_code'],
            'images'      => ['nullable', 'array', 'max:10'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB per image
        ];
    }

    public function messages(): array
    {
        return [
            'stock.required_if' => 'Jumlah stok wajib diisi untuk barang habis pakai.',
            'images.*.max'      => 'Ukuran setiap gambar maksimal 5MB.',
        ];
    }
}
