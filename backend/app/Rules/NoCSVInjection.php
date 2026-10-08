<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoCSVInjection implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $value = trim($value);

        // Détecter les formules Excel/CSV dangereuses
        $dangerousPatterns = ['=', '+', '-', '@', "\t", "\r"];
        
        if (strlen($value) > 0 && in_array($value[0], $dangerousPatterns, true)) {
            $fail('Le champ :attribute contient un caractère potentiellement dangereux au début (injection CSV détectée).');
        }

        // Détecter les commandes DDE (Dynamic Data Exchange)
        if (preg_match('/^(@|=|\+|-)\s*(cmd|powershell|mshta|regsvr32)/i', $value)) {
            $fail('Le champ :attribute contient une commande système potentiellement dangereuse.');
        }
    }
}
