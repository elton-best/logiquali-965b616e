<?php

namespace Tests\Unit\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Notifications\Document\DocumentWorkflowNotification;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\TestCase;

class DocumentWorkflowNotificationTest extends TestCase
{
    public function test_approval_request_notification(): void
    {
        $document = new class {
            public $id = 1;
            public $title = 'Procédure Qualité';
            public $code = 'PRO-001';
            public $version = '2.1';
            public $type = 'Procédure';
        };

        $actor = new class {
            public $id = 10;
            public $name = 'John Doe';
        };

        $notifiable = new class {
            public $name = 'Jane Approver';
        };

        $notification = new DocumentWorkflowNotification('approval_request', $document, $actor);

        $mailData = $notification->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $mailData);
        $this->assertEquals("[BestQHSE] Demande d'approbation : Procédure Qualité", $mailData->subject);
    }

    public function test_publication_notification(): void
    {
        $document = new class {
            public $id = 2;
            public $title = 'Manuel Utilisateur';
            public $code = 'MAN-002';
            public $version = '1.0';
            public $type = 'Manuel';
            public $published_at = null;
        };

        $actor = new class {
            public $id = 5;
            public $name = 'Admin User';
        };

        $notifiable = new class {
            public $name = 'User Reader';
        };

        $notification = new DocumentWorkflowNotification('publication', $document, $actor);

        $mailData = $notification->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $mailData);
        $this->assertEquals('[BestQHSE] Nouveau document publié : Manuel Utilisateur', $mailData->subject);
    }

    public function test_notification_database_payload_approval(): void
    {
        $document = new class {
            public $id = 3;
            public $title = 'Test Doc';
            public $code = 'TST-003';
            public $version = '1.0';
        };

        $notification = new DocumentWorkflowNotification('approval_request', $document);

        $notifiable = new class {};
        $databaseData = $notification->toArray($notifiable);

        $this->assertEquals('document_workflow', $databaseData['type']);
        $this->assertEquals('approval_request', $databaseData['workflow_type']);
        $this->assertEquals(3, $databaseData['entity_id']);
        $this->assertStringContainsString('approbation', $databaseData['message']);
    }

    public function test_notification_database_payload_publication(): void
    {
        $document = new class {
            public $id = 4;
            public $title = 'Published Doc';
            public $code = 'PUB-004';
            public $version = '2.0';
        };

        $notification = new DocumentWorkflowNotification('publication', $document);

        $notifiable = new class {};
        $databaseData = $notification->toArray($notifiable);

        $this->assertEquals('document_workflow', $databaseData['type']);
        $this->assertEquals('publication', $databaseData['workflow_type']);
        $this->assertStringContainsString('publié', $databaseData['message']);
    }

    public function test_notification_database_payload_verification_completed(): void
    {
        $document = new class {
            public $id = 7;
            public $title = 'Verification Done';
            public $code = 'VER-007';
            public $version = '1.0';
        };

        $notification = new DocumentWorkflowNotification('verification_completed', $document, null, [
            'outcome' => 'verified',
        ]);

        $notifiable = new class {};
        $databaseData = $notification->toArray($notifiable);

        $this->assertEquals('verification_completed', $databaseData['workflow_type']);
        $this->assertStringContainsString('Verification deja finalisee', $databaseData['message']);
        $this->assertEquals('verified', $databaseData['context']['outcome']);
    }

    public function test_notification_database_payload_approval_completed(): void
    {
        $document = new class {
            public $id = 8;
            public $title = 'Approval Done';
            public $code = 'APP-008';
            public $version = '2.0';
        };

        $notification = new DocumentWorkflowNotification('approval_completed', $document, null, [
            'outcome' => 'approved',
        ]);

        $notifiable = new class {};
        $databaseData = $notification->toArray($notifiable);

        $this->assertEquals('approval_completed', $databaseData['workflow_type']);
        $this->assertStringContainsString('Approbation deja finalisee', $databaseData['message']);
        $this->assertEquals('approved', $databaseData['context']['outcome']);
    }

    public function test_notification_channels(): void
    {
        $document = new class {
            public $id = 5;
            public $title = 'Test';
            public $code = 'T';
            public $version = '1';
        };

        $notification = new DocumentWorkflowNotification('approval_request', $document);

        $notifiable = new class {};
        $channels = $notification->via($notifiable);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    public function test_broadcast_message(): void
    {
        $document = new class {
            public $id = 6;
            public $title = 'Broadcast Test';
            public $code = 'BRC-006';
            public $version = '1.0';
        };

        $notification = new DocumentWorkflowNotification('publication', $document);

        $notifiable = new class {};
        $broadcastData = $notification->toBroadcast($notifiable);

        $this->assertArrayHasKey('type', $broadcastData->data);
        $this->assertEquals('document_workflow', $broadcastData->data['type']);
        $this->assertEquals('publication', $broadcastData->data['workflow_type']);
    }
}
