<?php

namespace App\Utils;

class FormulaParser
{
    /**
     * Évaluer une formule mathématique simple de manière sécurisée
     * Supporte: +, -, *, /, (), nombres, variables
     */
    public static function evaluate(string $formula, array $variables = []): ?float
    {
        try {
            // Substituer les variables
            foreach ($variables as $key => $value) {
                $formula = str_replace("{{$key}}", $value, $formula);
            }

            // Nettoyer et valider
            $formula = self::sanitize($formula);
            
            if (!self::isValid($formula)) {
                throw new \InvalidArgumentException("Formule invalide");
            }

            // Évaluer de manière sécurisée
            $result = self::parse($formula);
            
            return is_numeric($result) ? (float) $result : null;
        } catch (\Exception $e) {
            \Log::error("Erreur formule: " . $e->getMessage());
            return null;
        }
    }

    protected static function sanitize(string $formula): string
    {
        return preg_replace('/\s+/', '', $formula);
    }

    protected static function isValid(string $formula): bool
    {
        return preg_match('/^[\d+\-*\/.()]+$/', $formula) === 1;
    }

    protected static function parse(string $formula): float
    {
        // Parenthèses (récursif)
        while (preg_match('/\(([^()]+)\)/', $formula, $matches)) {
            $subResult = self::parse($matches[1]);
            $formula = str_replace($matches[0], $subResult, $formula);
        }

        // * et /
        while (preg_match('/([\d.]+)\s*([*\/])\s*([\d.]+)/', $formula, $matches)) {
            $left = (float) $matches[1];
            $op = $matches[2];
            $right = (float) $matches[3];
            $result = $op === '*' ? $left * $right : ($right != 0 ? $left / $right : 0);
            $formula = str_replace($matches[0], $result, $formula);
        }

        // + et -
        while (preg_match('/([\d.]+)\s*([+\-])\s*([\d.]+)/', $formula, $matches)) {
            $left = (float) $matches[1];
            $op = $matches[2];
            $right = (float) $matches[3];
            $result = $op === '+' ? $left + $right : $left - $right;
            $formula = str_replace($matches[0], $result, $formula);
        }

        return (float) $formula;
    }
}
