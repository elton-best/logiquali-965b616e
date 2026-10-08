<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\User\WelcomeNotification;
use Illuminate\Console\Command;

class TestUserNotifications extends Command
{
    protected $signature = 'test:user-notifications {user_id?}';

    protected $description = 'Test user notifications system';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? 1;
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        $this->info("Testing notification system for user: {$user->email}");

        // Envoyer une notification de bienvenue
        $user->notify(new WelcomeNotification(
            'collaborator',
            'set_password_link',
            null,
            'https://app.BestQHSE.com/set-password?token=test123'
        ));

        $this->info("✅ Welcome notification sent!");

        // Vérifier dans user_notifications
        $notificationCount = $user->userNotifications()->count();
        $this->info("📊 User has {$notificationCount} notification(s) in user_notifications table");

        // Afficher les dernières notifications
        $recent = $user->userNotifications()->latest()->take(3)->get();

        if ($recent->isNotEmpty()) {
            $this->newLine();
            $this->info("Recent notifications:");
            foreach ($recent as $notification) {
                $this->line("- [{$notification->type}] " . ($notification->read_at ? '✓ Read' : '○ Unread'));
            }
        }

        return 0;
    }
}

