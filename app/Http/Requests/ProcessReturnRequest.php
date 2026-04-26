<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Both super_admin and kajur (for their own requests) can submit returns
        return $this->user()->hasAnyRole(['super_admin', 'kajur']);
    }

    public function rules(): array
    {
        return [
            'condition_after' => ['required', Rule::in(['GOOD', 'FAIR', 'POOR', 'DAMAGED'])],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'images'          => ['required', 'array', 'min:1'],
            'images.*'        => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'images.required'     => 'Minimal 1 foto wajib diunggah saat pengembalian.',
            'images.min'          => 'Minimal 1 foto wajib diunggah saat pengembalian.',
            'images.*.image'      => 'File yang diunggah harus berupa gambar.',
            'condition_after.required' => 'Kondisi akhir barang wajib diisi.',
        ];
    }
}
