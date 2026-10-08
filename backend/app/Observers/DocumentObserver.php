<?php

namespace App\Observers;

use App\Models\Document;
use App\Traits\NotifiesSiteUsers;

class DocumentObserver
{
    use NotifiesSiteUsers;

    public function created(Document $document): void
    {
        if ($document->site_id) {
            $this->notifySiteUsers($document->site_id, 'document_created', [
                'type' => 'document_created',
                'message' => "Nouveau document: {$document->title}",
                'urgency' => 'info',
                'action_url' => "/company/documents/{$document->id}",
            ]);
        }
    }

    public function updated(Document $document): void
    {
        if ($document->wasChanged('status') && $document->site_id) {
            $urgency = $document->status === 'approved' ? 'info' : 'high';
            
            $this->notifySiteUsers($document->site_id, 'document_status_changed', [
                'type' => 'document_status_changed',
                'message' => "Document {$document->title} - Statut: {$document->status}",
                'urgency' => $urgency,
                'action_url' => "/company/documents/{$document->id}",
            ]);
        }
    }
}
