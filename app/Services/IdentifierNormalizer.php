<?php

namespace App\Services;

class IdentifierNormalizer
{
    public const VERSION = '1';

    public function normalize(string $type, string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return (new FieldNormalizer)->arabicIndicToWestern($value);
    }
}
