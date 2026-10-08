<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\System\DataProcessingNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Mockery;
use PHPUnit\Framework\TestCase;

class DataProcessingNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_export_notification(): void
    {
        $user = new class {
            public $name = 'John Doe';
        };

        $notification = new DataProcessingNotification(
            'export',
            'documents',
            true,
            null,
            '/downloads/documents.xlsx'
        );

        $mailData = $notification->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mailData);
        $this->assertStringContainsString('Export terminé', $mailData->subject);
    }

    public function test_failed_import_notification(): void
    {
        $user = new class {
            public $name = 'Jane Doe';
        };

        $notification = new DataProcessingNotification(
            'import',
            'data',
            false,
            'Ligne 1: format invalide, Ligne 5: doublon détecté'
        );

        $mailData = $notification->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mailData);
        $this->assertStringContainsString('Import échoué', $mailData->subject);
    }

    public function test_data_processing_notification_database_payload(): void
    {
        $user = new class {};
        $notification = new DataProcessingNotification(
            'export',
            'test_entity',
            true,
            null,
            '/downloads/test.xlsx'
        );

        $databaseData = $notification->toArray($user);

        $this->assertEquals('data_processing', $databaseData['type']);
        $this->assertEquals('export', $databaseData['process_type']);
        $this->assertEquals('test_entity', $databaseData['entity_type']);
        $this->assertEquals('/downloads/test.xlsx', $databaseData['file_path']);
        $this->assertTrue($databaseData['success']);
    }

    public function test_notification_channels(): void
    {
        $user = new class {};
        $notification = new DataProcessingNotification('export', 'test_entity');

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_array_message(): void
    {
        $user = new class {};
        $notification = new DataProcessingNotification('export', 'report', true);

        $arrayData = $notification->toArray($user);

        $this->assertArrayHasKey('type', $arrayData);
        $this->assertEquals('data_processing', $arrayData['type']);
        $this->assertTrue($arrayData['success']);
    }
}
