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
            // borrow_date selalu hari ini — dikirim otomatis dari frontend
            'borrow_date'      => ['required', 'date', 'after_or_equal:today'],

            // return_date: untuk CONSUMABLE-only request, sama dengan borrow_date
            // Frontend mengirim borrow_date sebagai return_date jika tidak ada UNIQUE item
            'return_date'      => ['required', 'date', 'after_or_equal:borrow_date'],

            'notes'            => ['nullable', 'string', 'max:1000'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.asset_id' => ['required', 'exists:assets,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'          => 'Minimal 1 barang harus dipilih.',
            'items.min'               => 'Minimal 1 barang harus dipilih.',
            'items.*.asset_id.exists' => 'Aset tidak ditemukan.',
            'return_date.after_or_equal' => 'Tanggal kembali tidak valid.',
        ];
    }
}
