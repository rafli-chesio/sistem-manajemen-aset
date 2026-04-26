<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('borrow.create');
    }

    public function rules(): array
    {
        return [
            'borrow_date'          => ['required', 'date', 'after_or_equal:today'],
            'return_date'          => ['required', 'date', 'after:borrow_date'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.asset_id'     => ['required', 'exists:assets,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'           => 'Minimal 1 barang harus dipilih.',
            'items.*.asset_id.exists'  => 'Aset tidak ditemukan.',
            'return_date.after'        => 'Tanggal kembali harus setelah tanggal pinjam.',
        ];
    }
}
