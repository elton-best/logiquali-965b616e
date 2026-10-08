<?php

namespace App\Utils;

class ExportHeaders
{
    public static function attachmentHeaders(string $filename, string $mime = 'application/octet-stream', bool $inline = false): array
    {
        $disp = $inline ? 'inline' : 'attachment';
        $safeName = self::sanitizeFilename($filename);
        $utf8 = rawurlencode($safeName);
        $contentDisposition = sprintf('%s; filename="%s"; filename*=UTF-8\'\'%s', $disp, $safeName, $utf8);

        return [
            'Content-Type' => $mime,
            'Content-Disposition' => $contentDisposition,
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Export-Generator' => 'LogiQuali',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Type, Content-Length',
        ];
    }

    private static function sanitizeFilename(string $filename): string
    {
        $sanitized = trim($filename);
        $sanitized = str_replace(["\r", "\n", '"'], '', $sanitized);

        return basename($sanitized) ?: 'export';
    }
}
