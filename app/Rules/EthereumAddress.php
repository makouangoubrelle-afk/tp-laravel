<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EthereumAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        if (! self::isValid($value)) {
            $fail('L\'adresse wallet doit être au format 0x suivi de 40 caractères hexadécimaux (ex. 0xf39f…92266). Laissez vide si le chauffeur n\'a pas encore de wallet.');
        }
    }

    public static function isValid(mixed $value): bool
    {
        $address = self::normalize($value);

        return $address !== null && preg_match('/^0x[a-f0-9]{40}$/', $address) === 1;
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $address = trim($value);
        if (! str_starts_with($address, '0x')) {
            $address = '0x'.$address;
        }

        return strtolower($address);
    }
}
