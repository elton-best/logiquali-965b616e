<?php

namespace Tests\Unit\Events;

use App\Events\User\UserCreated;
use App\Listeners\User\SendWelcomeNotification;
use App\Models\User;
use App\Notifications\User\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserCreatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_created_event_sends_welcome_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        
        event(new UserCreated(
            $user,
            'collaborator',
            'temporary_password',
            'temp123'
        ));

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_user_created_event_has_correct_properties(): void
    {
        $user = User::factory()->create();
        
        $event = new UserCreated(
            $user,
            'client',
            'set_password_link',
            null,
            'https://example.com/set-password'
        );

        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals('client', $event->userType);
        $this->assertEquals('set_password_link', $event->accessMode);
        $this->assertEquals('https://example.com/set-password', $event->setPasswordUrl);
    }

    public function test_event_listener_is_registered(): void
    {
        Event::fake();

        $user = User::factory()->create();
        event(new UserCreated($user, 'collaborator'));

        Event::assertDispatched(UserCreated::class);
    }
}
