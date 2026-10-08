<?php

namespace App\Events\System;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompleted
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $fileName;
    public string $fileUrl;
    public int $recordCount;
    public bool $success;
    public ?array $errors;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $user,
        string $fileName,
        string $fileUrl,
        int $recordCount,
        bool $success = true,
        ?array $errors = null
    ) {
        $this->user = $user;
        $this->fileName = $fileName;
        $this->fileUrl = $fileUrl;
        $this->recordCount = $recordCount;
        $this->success = $success;
        $this->errors = $errors;
    }
}
