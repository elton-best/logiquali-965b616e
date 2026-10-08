<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.read');
    }

    public function view(User $user, Document $document): bool
    {
        // Check confidentiality level
        if ($document->is_confidential) {
            return $this->canAccessConfidential($user, $document);
        }
        
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Author can edit draft documents
        if ($document->status === 'draft' && $document->author_id === $user->id) {
            return true;
        }
        
        return $user->can('documents.update') || $user->can('documents.manage');
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->can('documents.delete') || $user->can('documents.manage')) {
            return true;
        }
        
        // Author can delete own draft documents
        return $document->status === 'draft' && $document->author_id === $user->id;
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->isSuperAdmin() || $user->can('documents.manage');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->isSuperAdmin();
    }

    public function approve(User $user, Document $document): bool
    {
        return $user->isSuperAdmin() || $user->can('documents.manage');
    }

    public function publish(User $user, Document $document): bool
    {
        return $user->isSuperAdmin() || $user->can('documents.manage');
    }

    public function archive(User $user, Document $document): bool
    {
        return $user->isSuperAdmin() || $user->can('documents.manage');
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    protected function canAccessConfidential(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin() || $user->can('documents.manage')) {
            return true;
        }
        
        // Author can access own documents
        if ($document->author_id === $user->id) {
            return true;
        }
        
        // Check confidentiality level
        $level = $document->confidentiality_level;
        
        return match ($level) {
            'public' => true,
            'internal' => true,
            'confidential' => $user->can('documents.update') || $user->can('documents.manage'),
            'strictly_confidential' => false,
            default => false,
        };
    }
}
