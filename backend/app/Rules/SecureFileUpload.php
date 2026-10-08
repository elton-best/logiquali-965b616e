<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureFileUpload implements ValidationRule
{
    private array $allowedMimes;
    private int $maxSize;
    private array $allowedExtensions;

    public function __construct(
        array $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'],
        int $maxSize = 10240, // 10MB in KB
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'txt']
    ) {
        $this->allowedMimes = $allowedMimes;
        $this->maxSize = $maxSize;
        $this->allowedExtensions = $allowedExtensions;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('Le fichier doit être un fichier valide.');
            return;
        }

        // Check file size
        if ($value->getSize() > $this->maxSize * 1024) {
            $fail("Le fichier ne doit pas dépasser {$this->maxSize}KB.");
            return;
        }

        // Check MIME type
        $mimeType = $value->getMimeType();
        if (!in_array($mimeType, $this->allowedMimes)) {
            $fail('Type de fichier non autorisé.');
            return;
        }

        // Check extension
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            $fail('Extension de fichier non autorisée.');
            return;
        }

        // Check for executable files
        if (in_array($extension, ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp'])) {
            $fail('Les fichiers exécutables ne sont pas autorisés.');
            return;
        }

        // Basic malicious content detection
        $content = file_get_contents($value->getRealPath());
        $maliciousPatterns = ['<script', '<?php', '<%', 'javascript:', 'vbscript:'];
        
        foreach ($maliciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $fail('Contenu potentiellement malveillant détecté.');
                return;
            }
        }
    }
}