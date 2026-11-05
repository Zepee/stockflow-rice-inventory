<?php

namespace App\Http\Requests\StockAdjustments;

use App\Rules\UnsignedDecimal15_3;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'submission_key' => ['required', 'uuid'],
            'business_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.direction' => ['required', Rule::in(['increase', 'decrease'])],
            'items.*.quantity' => ['required', 'gt:0', new UnsignedDecimal15_3],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'business_date.required' => 'Tanggal transaksi wajib diisi.',
            'reason.required' => 'Alasan penyesuaian wajib diisi.',
            'items.required' => 'Tambahkan sedikitnya satu produk.',
            'items.min' => 'Tambahkan sedikitnya satu produk.',
            'items.*.product_id.required' => 'Pilih produk.',
            'items.*.product_id.distinct' => 'Produk yang sama hanya boleh muncul satu kali dalam satu penyesuaian.',
            'items.*.quantity.required' => 'Kuantitas wajib diisi.',
        ];
    }
}
