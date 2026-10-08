<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\User\WelcomeNotification;
use App\Notifications\User\PasswordResetNotification;
use App\Notifications\Enterprise\EnterpriseRegistrationConfirmation;
use App\Notifications\Enterprise\NewEnterpriseNotification;
use App\Notifications\Enterprise\EnterpriseApprovedNotification;
use App\Notifications\Enterprise\EnterpriseRejectedNotification;
use App\Notifications\Action\ActionNotification;
use App\Notifications\Subscription\SubscriptionNotification;
use App\Notifications\Comment\MentionNotification;
use App\Notifications\ExportReadyNotification;
use App\Notifications\ExportFailedNotification;
use App\Models\Enterprise;
use App\Models\Action;
use App\Models\Subscription;
use Illuminate\Console\Command;

class TestNotificationsSimple extends Command
{
    protected $signature = 'notifications:test-simple {--email=}';
    protected $description = 'Test notifications directly (simplified)';

    private $results = [];

    public function handle(): int
    {
        $this->info('🧪 TEST NOTIFICATIONS SIMPLIFIÉ');
        $this->newLine();

        $email = $this->option('email') ?? 'test@BestQHSE.com';
        $this->info("📧 Email: {$email}");
        $this->newLine();

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'user_type' => 'company',
                'is_active' => true,
            ]
        );

        $this->info("👤 User ID: {$user->id}");
        $this->newLine();

        // Test direct des notifications
        $this->runTests($user);

        $this->displayResults();

        return Command::SUCCESS;
    }

    private function runTests(User $user): void
    {
        $this->test('Welcome Collab', function () use ($user) {
            $user->notify(new WelcomeNotification('collaborator', 'temporary_password', 'TempPass123'));
        });

        $this->test('Welcome Client', function () use ($user) {
            $user->notify(new WelcomeNotification('client', 'set_password_link', null, 'http://localhost:3000/set-password'));
        });

        $this->test('Password Reset', function () use ($user) {
            $user->notify(new PasswordResetNotification('abc123', 'http://localhost:3000/reset'));
        });

        $this->test('Enterprise Registration', function () use ($user) {
            $ent = $this->mockEnterprise();
            $user->notify(new EnterpriseRegistrationConfirmation($ent));
        });

        $this->test('Enterprise Admin Alert', function () use ($user) {
            $ent = $this->mockEnterprise();
            $user->notify(new NewEnterpriseNotification($ent, $user));
        });

        $this->test('Enterprise Approved', function () use ($user) {
            $ent = $this->mockEnterprise();
            $user->notify(new EnterpriseApprovedNotification($ent));
        });

        $this->test('Enterprise Rejected', function () use ($user) {
            $ent = $this->mockEnterprise();
            $user->notify(new EnterpriseRejectedNotification($ent, 'Documents incomplets'));
        });

        $this->test('Action Assigned', function () use ($user) {
            $action = $this->mockAction($user);
            $user->notify(new ActionNotification($action, 'assigned'));
        });

        $this->test('Action Deadline', function () use ($user) {
            $action = $this->mockAction($user);
            $user->notify(new ActionNotification($action, 'deadline_approaching', ['days_remaining' => 2]));
        });

        $this->test('Action Overdue', function () use ($user) {
            $action = $this->mockAction($user);
            $user->notify(new ActionNotification($action, 'overdue', ['days_overdue' => 5]));
        });

        $this->test('Subscription Activated', function () use ($user) {
            $sub = $this->mockSubscription();
            $user->notify(new SubscriptionNotification($sub, 'activated'));
        });

        $this->test('Subscription Expiring', function () use ($user) {
            $sub = $this->mockSubscription();
            $user->notify(new SubscriptionNotification($sub, 'expiring', ['days_remaining' => 3]));
        });

        $this->test('Subscription Expired', function () use ($user) {
            $sub = $this->mockSubscription();
            $user->notify(new SubscriptionNotification($sub, 'expired'));
        });

        $this->test('User Mentioned', function () use ($user) {
            $author = $user;
            $comment = (object) ['id' => 1, 'content' => "Hey @{$user->name}!"];
            $entity = (object) ['id' => 1, 'title' => 'Test Entity'];
            $user->notify(new MentionNotification($author, $comment, $entity));
        });

        $this->test('Export Ready', function () use ($user) {
            $user->notify(new ExportReadyNotification('Plan de risques', '/exports/test.xlsx', 'test.xlsx'));
        });

        $this->test('Export Failed', function () use ($user) {
            $user->notify(new ExportFailedNotification('Plan de risques', 'Erreur mémoire'));
        });
    }

    private function test(string $name, callable $fn): void
    {
        $this->line(sprintf('%-30s', $name) . '...', null, false);

        try {
            $fn();
            $this->info(' ✅');
            $this->results[$name] = '✅';
        } catch (\Exception $e) {
            $this->error(' ❌');
            $this->error('  ' . substr($e->getMessage(), 0, 60));
            $this->results[$name] = '❌';
        }
    }

    private function mockEnterprise(): Enterprise
    {
        $ent = new Enterprise();
        $ent->id = 999;
        $ent->name = 'Test Enterprise';
        $ent->address = '123 Test Street';
        $ent->status = 'pending';
        return $ent;
    }

    private function mockAction(User $user): Action
    {
        $action = new Action();
        $action->id = 999;
        $action->title = 'Test Action';
        $action->description = 'Test description';
        $action->deadline_date = now()->addDays(10);
        $action->priority = 'high';
        $action->status = 'in_progress';
        $action->pilot = $user;
        return $action;
    }

    private function mockSubscription(): Subscription
    {
        $sub = new Subscription();
        $sub->id = 999;
        $sub->plan_name = 'Premium';
        $sub->start_date = now();
        $sub->end_date = now()->addYear();
        $sub->status = 'active';

        $ent = new Enterprise();
        $ent->id = 999;
        $ent->name = 'Test Enterprise';
        $sub->enterprise = $ent;

        return $sub;
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->info('═══ RÉSULTATS ═══');
        $this->newLine();

        $total = count($this->results);
        $success = count(array_filter($this->results, fn($r) => $r === '✅'));

        $this->info("✅ {$success}/{$total} tests réussis");

        if ($success === $total) {
            $this->info('🎉 PARFAIT ! TOUS LES TESTS SONT PASSÉS !');
        } else {
            $failed = $total - $success;
            $this->warn("⚠️  {$failed} test(s) échoué(s)");
        }

        $this->newLine();
        $this->line('📋 Vérifier les emails dans logs:');
        $this->line('   tail -f storage/logs/laravel.log | grep "Subject:"');
        $this->newLine();
        $this->line('📊 Vérifier les notifications en BDD:');
        $this->line('   SELECT COUNT(*) FROM notifications;');
    }
}
