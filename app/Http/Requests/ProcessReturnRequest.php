<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('kajur');
    }

    public function rules(): array
    {
        return [
            // Array kondisi per borrow_item (satu entry per UNIQUE item)
            'item_conditions'                       => ['required', 'array', 'min:1'],
            'item_conditions.*.borrow_item_id'      => ['required', 'integer', 'exists:borrow_items,id'],
            'item_conditions.*.condition_after'     => ['required', Rule::in(['GOOD', 'FAIR', 'POOR', 'DAMAGED'])],
            'item_conditions.*.notes'               => ['nullable', 'string', 'max:500'],

            // Catatan umum untuk return record
            'notes'    => ['nullable', 'string', 'max:1000'],

            // Foto wajib minimal 1
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_conditions.required'                  => 'Data kondisi barang wajib diisi.',
            'item_conditions.*.borrow_item_id.required' => 'ID item peminjaman tidak valid.',
            'item_conditions.*.borrow_item_id.exists'   => 'Item peminjaman tidak ditemukan.',
            'item_conditions.*.condition_after.required'=> 'Kondisi akhir setiap barang wajib diisi.',
            'images.required'                           => 'Minimal 1 foto wajib diunggah saat pengembalian.',
            'images.min'                                => 'Minimal 1 foto wajib diunggah saat pengembalian.',
            'images.*.image'                            => 'File yang diunggah harus berupa gambar.',
        ];
    }
}
