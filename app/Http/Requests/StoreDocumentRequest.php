<?php

namespace App\Http\Requests;

use App\Models\Vault;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vault = $this->route('vault');

        return $vault instanceof Vault && $this->user()?->can('createDocument', $vault);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}
