<?php

namespace App\Http\Requests;

use App\Models\AiConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->route('ai_connection');

        return $this->user() !== null
            && $connection instanceof AiConnection
            && $connection->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        /** @var AiConnection $connection */
        $connection = $this->route('ai_connection');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ai_connections', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($connection->id),
            ],
            'provider' => ['required', Rule::in([AiConnection::PROVIDER_OPENAI])],
            'api_key' => ['nullable', 'string', 'max:4096'],
            'model' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'make_default' => ['sometimes', 'boolean'],
        ];
    }
}
