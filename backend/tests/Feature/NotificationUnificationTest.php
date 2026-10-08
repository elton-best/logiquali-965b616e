<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SiteEventNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Test de la chaîne d'unification des notifications (Chantier 2)
 */
class NotificationUnificationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_active' => true]);
    }

    /**
     * Test 1: SiteEventNotification peut être créée et envoyée
     */
    public function test_site_event_notification_can_be_created()
    {
        Notification::fake();

        $notification = new SiteEventNotification('test_event', [
            'message' => 'Test notification',
        ]);

        $this->user->notify($notification);

        Notification::assertSentTo($this->user, SiteEventNotification::class);
    }

    /**
     * Test 2: Notification est écrite dans la table notifications (Laravel)
     */
    public function test_notification_written_to_database()
    {
        // Envoyer une notification
        $this->user->notify(new SiteEventNotification('test_event', [
            'message' => 'Test notification',
            'actor_name' => 'TestActor',
        ]));

        // Vérifier qu'elle est dans la table
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'App\\Models\\User',
            'type' => 'App\\Notifications\\SiteEventNotification',
        ]);
    }

    /**
     * Test 3: NotifiesSiteUsers trait écrit dans les deux tables (unification)
     */
    public function test_notifies_site_users_writes_to_laravel_table_only()
    {
        // Tester en utilisant un Observer réel qui utilise le trait
        $audit = \App\Models\Audit::factory()->create([
            'assigned_to' => $this->user->id,
        ]);

        // Vérifier que la notification est créée dans la table Laravel uniquement
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'App\\Models\\User',
        ]);
        $this->assertDatabaseCount('user_notifications', 0);
    }

    /**
     * Test 4: UserNotificationController peut lire les notifications unifiées
     */
    public function test_user_notification_controller_reads_unified_notifications()
    {
        // Créer une notification Laravel
        $this->user->notify(new SiteEventNotification('laravel_event', [
            'message' => 'Laravel notification',
        ]));

        // Tester que le contrôleur lit la source unifiée Laravel
        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'type', 'data', 'source', 'created_at']],
            'unread_count',
            'total',
        ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /**
     * Test 5: Mark as read fonctionne pour les deux sources
     */
    public function test_mark_as_read_works_for_laravel_source()
    {
        // Créer notifications
        $laravelNotif = $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\SiteEventNotification',
            'data' => ['message' => 'Test'],
            'read_at' => null,
        ]);

        // Marquer Laravel comme lue
        $response = $this->actingAs($this->user)->postJson("/api/v1/notifications/{$laravelNotif->id}/read");
        $response->assertStatus(200);

        // Vérifier qu'elle est marquée comme lue
        $this->assertNotNull($laravelNotif->fresh()->read_at);
    }
}
