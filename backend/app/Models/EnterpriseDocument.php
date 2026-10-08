<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class EnterpriseDocument extends Model
{
    protected $fillable = [
        'enterprise_id',
        'document_type',
        'document_number',
        'name',
        'stored_path',
        'uploaded_by',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Generate a temporary signed URL for secure document download
     */
    public function getSignedDownloadUrl(): string
    {
        $ttlMinutes = (int) config('documents.superadmin_signed_url_ttl_minutes', 10);
        $diskDriver = config('filesystems.disks.public.driver', config('filesystems.default'));

        // If files are stored on S3, prefer generating a presigned S3 URL that forces attachment
        if ($diskDriver === 's3') {
            try {
                $mime = Storage::disk('public')->mimeType($this->stored_path) ?: 'application/octet-stream';
            } catch (\Exception $e) {
                $mime = 'application/octet-stream';
            }

            $filename = $this->name ?: basename($this->stored_path);

            return Storage::disk('public')->temporaryUrl(
                $this->stored_path,
                now()->addMinutes($ttlMinutes),
                [
                    'ResponseContentType' => $mime,
                    'ResponseContentDisposition' => 'attachment; filename="' . $filename . '"',
                ]
            );
        }

        return URL::temporarySignedRoute(
            'superadmin.documents.download',
            now()->addMinutes($ttlMinutes),
            ['enterprise' => $this->enterprise_id, 'document' => $this->id]
        );
    }

    /**
     * Generate a temporary signed URL for secure document preview (inline)
     */
    public function getSignedPreviewUrl(): string
    {
        $ttlMinutes = (int) config('documents.superadmin_signed_url_ttl_minutes', 10);
        $diskDriver = config('filesystems.disks.public.driver', config('filesystems.default'));

        // If files are stored on S3, generate a presigned S3 URL that forces inline disposition
        if ($diskDriver === 's3') {
            try {
                $mime = Storage::disk('public')->mimeType($this->stored_path) ?: 'application/octet-stream';
            } catch (\Exception $e) {
                $mime = 'application/octet-stream';
            }

            $filename = $this->name ?: basename($this->stored_path);

            return Storage::disk('public')->temporaryUrl(
                $this->stored_path,
                now()->addMinutes($ttlMinutes),
                [
                    'ResponseContentType' => $mime,
                    'ResponseContentDisposition' => 'inline; filename="' . $filename . '"',
                ]
            );
        }

        return URL::temporarySignedRoute(
            'superadmin.documents.preview',
            now()->addMinutes($ttlMinutes),
            ['enterprise' => $this->enterprise_id, 'document' => $this->id]
        );
    }

    /**
     * Get file size in human-readable format
     */
    public function getFileSizeAttribute(): string
    {
        if (!Storage::disk('public')->exists($this->stored_path)) {
            return 'N/A';
        }

        $bytes = Storage::disk('public')->size($this->stored_path);
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->stored_path, PATHINFO_EXTENSION);
    }
}
