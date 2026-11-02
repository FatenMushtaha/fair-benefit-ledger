<?php

namespace App\Enums;

enum Locale: string
{
    case Arabic = 'ar';
    case English = 'en';

    public function label(): string
    {
        return __('ui.locale.'.$this->value);
    }

    public function direction(): string
    {
        return $this === self::Arabic ? 'rtl' : 'ltr';
    }

    public static function fallback(): self
    {
        return self::English;
    }

    public static function fromStored(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::fallback();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $locale): string => $locale->value, self::cases());
    }
}
