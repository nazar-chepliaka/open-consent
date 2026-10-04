<?php

namespace App\Services\Ai;

class AiProviderResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $message,
    ) {}

    public static function success(string $message = 'Підключення перевірено.'): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
