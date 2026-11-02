<?php

namespace App\Services;

class NameNormalizer
{
    public const VERSION = '1';

    public function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = $this->removeArabicDiacritics($value);
        $value = str_replace('ـ', '', $value);
        $value = (new FieldNormalizer)->arabicIndicToWestern($value);
        $value = mb_strtolower($value, 'UTF-8');

        return $value;
    }

    public function tokenSortedKey(string $value): string
    {
        $normalized = $this->normalize($value);
        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($tokens);

        return implode(' ', $tokens);
    }

    public function transliterationKey(string $value): string
    {
        $normalized = $this->normalize($value);
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');

        if ($transliterator === null) {
            return $normalized;
        }

        return $transliterator->transliterate($normalized) ?: $normalized;
    }

    private function removeArabicDiacritics(string $value): string
    {
        $diacritics = ['\x{064B}', '\x{064C}', '\x{064D}', '\x{064E}', '\x{064F}', '\x{0650}', '\x{0651}', '\x{0652}', '\x{0653}', '\x{0654}', '\x{0655}', '\x{0656}', '\x{0657}', '\x{0658}', '\x{0670}'];

        return preg_replace('/'.implode('|', $diacritics).'/u', '', $value) ?? $value;
    }
}
