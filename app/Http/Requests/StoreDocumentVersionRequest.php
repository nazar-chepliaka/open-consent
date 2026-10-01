<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof Document && $this->user()?->can('update', $document);
    }

    public function rules(): array
    {
        return [
            'version_label' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}
