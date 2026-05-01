<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('asset.edit');
    }

    public function rules(): array
    {
        $assetId = $this->route('asset')->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'brand'       => ['nullable', 'string', 'max:100'],
            'year'        => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'condition'   => ['required', Rule::in(['GOOD', 'FAIR', 'POOR', 'DAMAGED'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'department'  => ['nullable', 'string', 'max:100'],
            'type'        => ['required', Rule::in([Asset::TYPE_UNIQUE, Asset::TYPE_CONSUMABLE])],
            'stock'       => ['required_if:type,CONSUMABLE', 'nullable', 'integer', 'min:0'],
            'status'      => ['required_if:type,UNIQUE', 'nullable', Rule::in(['AVAILABLE', 'BORROWED', 'DAMAGED', 'LOST'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'asset_code'  => ['nullable', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($assetId)],
            'images'      => ['nullable', 'array', 'max:10'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
