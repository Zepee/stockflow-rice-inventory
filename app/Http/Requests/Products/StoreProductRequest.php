<?php

namespace App\Http\Requests\Products;

use App\Rules\UnsignedBigInt;
use App\Rules\UnsignedDecimal15_3;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'sku' => is_string($this->input('sku')) ? Str::upper(trim($this->input('sku'))) : $this->input('sku'),
            'unit' => is_string($this->input('unit')) ? Str::lower(trim($this->input('unit'))) : $this->input('unit'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'description' => ['nullable', 'string', 'max:4000'],
            'unit' => ['required', 'string', 'max:16'],
            'purchase_price' => ['required', new UnsignedBigInt],
            'selling_price' => ['required', new UnsignedBigInt],
            'minimum_stock' => ['required', new UnsignedDecimal15_3],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'sku.required' => 'SKU wajib diisi.',
            'sku.unique' => 'SKU tersebut sudah digunakan.',
            'unit.required' => 'Satuan produk wajib diisi.',
            'purchase_price.required' => 'Harga beli wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'minimum_stock.required' => 'Batas stok minimum wajib diisi.',
        ];
    }
}
