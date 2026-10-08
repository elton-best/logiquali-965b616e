<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\User\WelcomeNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Mockery;
use PHPUnit\Framework\TestCase;

class WelcomeNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_welcome_notification_for_collaborator_with_temp_password(): void
    {
        $user = new class {
            public $name = 'John Doe';
            public $email = 'john@example.com';
        };

        $notification = new WelcomeNotification('collaborator', 'temporary_password', 'temp123');

        $mailData = $notification->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mailData);
        $this->assertEquals('[BestQHSE] Vos identifiants de connexion', $mailData->subject);
    }

    public function test_welcome_notification_for_client_with_link(): void
    {
        $user = new class {
            public $name = 'Jane Doe';
            public $email = 'jane@example.com';
        };

        $notification = new WelcomeNotification('client', 'set_password_link', null, 'https://example.com/set-password');

        $mailData = $notification->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mailData);
    }

    public function test_welcome_notification_database_payload(): void
    {
        $user = new class {};
        $notification = new WelcomeNotification('collaborator', 'temporary_password');

        $databaseData = $notification->toArray($user);

        $this->assertEquals('welcome', $databaseData['type']);
        $this->assertEquals('collaborator', $databaseData['user_type']);
        $this->assertArrayHasKey('message', $databaseData);
    }

    public function test_notification_channels(): void
    {
        $user = new class {};
        $notification = new WelcomeNotification('client');

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }
}
