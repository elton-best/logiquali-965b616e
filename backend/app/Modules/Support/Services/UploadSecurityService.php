<?php

namespace App\Modules\Support\Services;

use Illuminate\Http\UploadedFile;

class UploadSecurityService
{
    private array $allowedMimeTypes = [
        'text/csv',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    private array $allowedExtensions = ['csv', 'txt', 'xlsx', 'xls'];

    public function validateSpreadsheet(UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $this->allowedExtensions, true)) {
            throw new \RuntimeException('Format de fichier non autorisé.');
        }

        $mime = $file->getClientMimeType();
        if ($mime && !in_array($mime, $this->allowedMimeTypes, true)) {
            throw new \RuntimeException('Type MIME non autorisé.');
        }

        // Basic heuristic to reject potentially malicious uploads
        $content = file_get_contents($file->getRealPath());
        if ($content !== false && preg_match('/<\?php|<script|base64_decode\s*\(/i', $content)) {
            throw new \RuntimeException('Fichier suspect détecté.');
        }
    }
}
