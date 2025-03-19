<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Isbn implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isIsbn($value)) {
            $fail("The $attribute must be a valid ISBN 10 or ISBN 13 number.");
        }
    }

    private function isIsbn(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        $value = strtoupper((string) preg_replace('/[^0-9X]/', '', (string) $value));

        return $this->validateISBN10($value) || $this->validateISBN13($value);
    }

    private function validateISBN10(string $isbn): bool
    {
        if (strlen($isbn) !== 10) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            if ($i === 9 && $isbn[$i] === 'X') {
                $sum += 10;
            } else {
                $sum += (10 - $i) * intval($isbn[$i]);
            }
        }

        return $sum % 11 === 0;
    }

    private function validateISBN13(string $isbn): bool
    {
        if (strlen($isbn) !== 13) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($isbn[$i]) * ($i % 2 === 0 ? 1 : 3);
        }

        return $sum % 10 === 0;
    }
}
