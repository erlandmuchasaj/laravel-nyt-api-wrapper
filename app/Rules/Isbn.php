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

        return preg_match(
        '/^(?:ISBN(-1(?:(0)|3))?:?\ )?(?(1)(?(2)(?=[0-9X]{10}$|(?=(?:[0-9]+[- ]){3})[- 0-9X]{13}$)[0-9]{1,5}[- ]?[0-9]+[- ]?[0-9]+[- ]?[0-9X]|(?=[0-9]{13}$|(?=(?:[0-9]+[- ]){4})[- 0-9]{17}$)97[89][- ]?[0-9]{1,5}[- ]?[0-9]+[- ]?[0-9]+[- ]?[0-9])|(?=[0-9X]{10}$|(?=(?:[0-9]+[- ]){3})[- 0-9X]{13}$|97[89][0-9]{10}$|(?=(?:[0-9]+[- ]){4})[- 0-9]{17}$)(?:97[89][- ]?)?[0-9]{1,5}[- ]?[0-9]+[- ]?[0-9]+[- ]?[0-9X])$/',
        $value
        ) > 0;
    }
}
