<?php

namespace App\Services\Core;

class SecureTokenService
{
    /**
     * Generate a URL-safe cryptographically secure token.
     */
    public function generateUrlToken(int $bytes = 32): string
    {
        $raw = random_bytes(max(16, $bytes));

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
