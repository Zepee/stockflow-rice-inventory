<?php

namespace App\Http\Requests\Purchases;

use App\Rules\UnsignedBigInt;
use App\Rules\UnsignedDecimal15_3;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PreparePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $owner = $this->user()->isOwner();

        return [
            'draft_key' => ['required', 'uuid'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'business_date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'gt:0', new UnsignedDecimal15_3],
            'items.*.purchase_price_override' => $owner ? ['nullable', new UnsignedBigInt] : ['prohibited'],
            'items.*.override_reason' => $owner ? ['nullable', 'string', 'max:1000'] : ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('items', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $hasOverride = isset($item['purchase_price_override']) && $item['purchase_price_override'] !== '';
                $hasReason = isset($item['override_reason']) && trim((string) $item['override_reason']) !== '';

                if ($hasOverride && ! $hasReason) {
                    $validator->errors()->add("items.{$index}.override_reason", 'Alasan wajib diisi jika harga beli diubah.');
                }

                if (! $hasOverride && $hasReason) {
                    $validator->errors()->add("items.{$index}.override_reason", 'Alasan hanya diisi jika harga beli diubah.');
                }
            }
        }];
    }
}
