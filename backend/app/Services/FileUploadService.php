<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function uploadSecure(UploadedFile $file, string $directory = 'uploads'): array
    {
        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Store file
        $path = $file->storeAs($directory, $filename, 'private');
        
        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'filename' => $filename
        ];
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk('private')->delete($path);
    }

    public function getFileUrl(string $path): string
    {
        return Storage::disk('private')->url($path);
    }
}