<?php

namespace App\Http\Requests;

use App\Models\AiConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ai_connections', 'name')->where('user_id', $this->user()->id),
            ],
            'provider' => ['required', Rule::in([AiConnection::PROVIDER_OPENAI])],
            'api_key' => ['required', 'string', 'max:4096'],
            'model' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'make_default' => ['sometimes', 'boolean'],
        ];
    }
}
