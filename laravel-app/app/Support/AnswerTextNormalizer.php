<?php

namespace App\Support;

/**
 * Normalizes free-text answers for fair comparison (fill-in, short text).
 */
final class AnswerTextNormalizer
{
    public static function normalize(string $value): string
    {
        $s = trim($value);
        if ($s === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $s = mb_strtolower($s, 'UTF-8');
        } else {
            $s = strtolower($s);
        }
        // Collapse internal whitespace (tabs, multiple spaces, NBSP)
        $s = preg_replace('/[\s\x{00A0}]+/u', ' ', $s) ?? $s;

        return trim($s);
    }

    public static function fillInMatches(?string $student, ?string $expected): bool
    {
        if ($expected === null || $expected === '') {
            return false;
        }
        $a = self::normalize((string) $student);
        $b = self::normalize((string) $expected);

        return $a !== '' && $b !== '' && $a === $b;
    }
}
