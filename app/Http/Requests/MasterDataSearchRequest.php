<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDataSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100']];
    }
}
