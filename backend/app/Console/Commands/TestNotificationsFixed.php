<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Enterprise;
use App\Models\Reclamation;
use App\Models\Document;
use App\Models\Action;
use App\Models\Subscription;
use App\Events\User\UserCreated;
use App\Events\User\PasswordResetRequested;
use App\Events\Enterprise\EnterpriseRegistered;
use App\Events\Enterprise\EnterpriseApproved;
use App\Events\Enterprise\EnterpriseRejected;
use App\Events\Complaint\ComplaintSubmitted;
use App\Events\Complaint\ComplaintReplied;
use App\Events\Complaint\ComplaintClosed;
use App\Events\Document\DocumentSubmittedForApproval;
use App\Events\Document\DocumentPublished;
use App\Events\Action\ActionAssigned;
use App\Events\Action\ActionDeadlineApproaching;
use App\Events\Action\ActionOverdue;
use App\Events\Subscription\SubscriptionActivated;
use App\Events\Subscription\SubscriptionExpiring;
use App\Events\Subscription\SubscriptionExpired;
use App\Events\Comment\UserMentioned;
use App\Events\System\ExportCompleted;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestNotificationsFixed extends Command
{
    protected $signature = 'notifications:test 
                            {--email= : Email destinataire}
                            {--type= : Type spécifique}';

    protected $description = 'Test all notifications (FIXED)';

    private $testEmail;
    private $results = [];

    public function handle(): int
    {
        $this->info('🧪 TEST NOTIFICATIONS - FIXED VERSION');
        $this->newLine();

        $this->testEmail = $this->option('email') ?? 'test@BestQHSE.com';
        $this->info("📧 Email: {$this->testEmail}");
        $this->newLine();

        $testUser = $this->createTestUser();

        $this->runAllTests($testUser);

        $this->displayResults();

        return Command::SUCCESS;
    }

    private function createTestUser(): User
    {
        $this->info('👤 Création utilisateur test...');

        $user = User::firstOrCreate(
            ['email' => $this->testEmail],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'user_type' => 'company',
                'is_active' => true,
            ]
        );

        $this->line("   ✓ User ID: {$user->id}");
        $this->newLine();

        return $user;
    }

    private function runAllTests(User $testUser): void
    {
        $this->info('🚀 Tests...');
        $this->newLine();

        $tests = [
            'welcome_collaborator' => fn() => $this->test1($testUser),
            'welcome_client' => fn() => $this->test2($testUser),
            'password_reset' => fn() => $this->test3($testUser),
            'enterprise_registration' => fn() => $this->test4($testUser),
            'enterprise_approved' => fn() => $this->test5($testUser),
            'enterprise_rejected' => fn() => $this->test6($testUser),
            'complaint_submitted' => fn() => $this->test7($testUser),
            'complaint_replied' => fn() => $this->test8($testUser),
            'complaint_closed' => fn() => $this->test9($testUser),
            'document_approval' => fn() => $this->test10($testUser),
            'document_published' => fn() => $this->test11($testUser),
            'action_assigned' => fn() => $this->test12($testUser),
            'action_deadline' => fn() => $this->test13($testUser),
            'action_overdue' => fn() => $this->test14($testUser),
            'subscription_activated' => fn() => $this->test15($testUser),
            'subscription_expiring' => fn() => $this->test16($testUser),
            'subscription_expired' => fn() => $this->test17($testUser),
            'user_mentioned' => fn() => $this->test18($testUser),
            'export_completed' => fn() => $this->test19($testUser),
        ];

        foreach ($tests as $name => $test) {
            try {
                $test();
                $this->results[$name] = '✅';
            } catch (\Exception $e) {
                $this->results[$name] = '❌';
                $this->error("   Error: " . substr($e->getMessage(), 0, 50));
            }
        }
    }

    private function test1(User $user): void
    {
        $this->line('1. Welcome Collaborator...');
        event(new UserCreated($user, 'collaborator', 'temporary_password', 'TempPass123'));
        $this->line('   ✓');
    }

    private function test2(User $user): void
    {
        $this->line('2. Welcome Client...');
        event(new UserCreated($user, 'client', 'set_password_link', null, 'http://localhost:3000/set-password'));
        $this->line('   ✓');
    }

    private function test3(User $user): void
    {
        $this->line('3. Password Reset...');
        event(new PasswordResetRequested($user, Str::random(64), 'http://localhost:3000/reset'));
        $this->line('   ✓');
    }

    private function test4(User $user): void
    {
        $this->line('4. Enterprise Registration...');
        $ent = Enterprise::first();
        if (!$ent) {
            $ent = new Enterprise();
            $ent->name = 'Test Enterprise';
            $ent->address = '123 Test';
            $ent->status = 'pending';
            $ent->id = 999;
        }
        event(new EnterpriseRegistered($ent, $user));
        $this->line('   ✓');
    }

    private function test5(User $user): void
    {
        $this->line('5. Enterprise Approved...');
        $ent = Enterprise::first();
        if (!$ent) {
            $ent = new Enterprise();
            $ent->name = 'Test Enterprise';
            $ent->id = 999;
        }
        event(new EnterpriseApproved($ent));
        $this->line('   ✓');
    }

    private function test6(User $user): void
    {
        $this->line('6. Enterprise Rejected...');
        $ent = Enterprise::first();
        if (!$ent) {
            $ent = new Enterprise();
            $ent->name = 'Test Enterprise';
            $ent->id = 999;
        }
        event(new EnterpriseRejected($ent, 'Documents incomplets'));
        $this->line('   ✓');
    }

    private function test7(User $user): void
    {
        $this->line('7. Complaint Submitted...');
        $c = new Reclamation();
        $c->id = 999;
        $c->ref = 'REC-TEST-001';
        $c->title = 'Test Complaint';
        $c->description = 'Test description';
        $c->client_name = 'Test Client';
        $c->user_id = $user->id;
        $c->setRelation('user', $user);
        event(new ComplaintSubmitted($c));
        $this->line('   ✓');
    }

    private function test8(User $user): void
    {
        $this->line('8. Complaint Replied...');
        $c = new Reclamation();
        $c->id = 999;
        $c->ref = 'REC-TEST-001';
        $c->title = 'Test Complaint';
        $c->description = 'Test';
        $c->user_id = $user->id;
        $c->setRelation('user', $user);
        event(new ComplaintReplied($c, 'Notre réponse...'));
        $this->line('   ✓');
    }

    private function test9(User $user): void
    {
        $this->line('9. Complaint Closed...');
        $c = new Reclamation();
        $c->id = 999;
        $c->ref = 'REC-TEST-001';
        $c->title = 'Test Complaint';
        $c->description = 'Test';
        $c->user_id = $user->id;
        $c->setRelation('user', $user);
        event(new ComplaintClosed($c, 'Résolution', 'http://survey.com'));
        $this->line('   ✓');
    }

    private function test10(User $user): void
    {
        $this->line('10. Document Approval...');
        $doc = new Document();
        $doc->id = 999;
        $doc->title = 'Test Doc';
        $doc->code = 'DOC-001';
        $doc->version = '1.0';
        event(new DocumentSubmittedForApproval($doc, collect([$user]), $user));
        $this->line('   ✓');
    }

    private function test11(User $user): void
    {
        $this->line('11. Document Published...');
        $doc = new Document();
        $doc->id = 999;
        $doc->title = 'Test Doc';
        $doc->code = 'DOC-001';
        $doc->version = '1.0';
        event(new DocumentPublished($doc, collect([$user]), $user));
        $this->line('   ✓');
    }

    private function test12(User $user): void
    {
        $this->line('12. Action Assigned...');
        $action = new Action();
        $action->id = 999;
        $action->title = 'Test Action';
        $action->description = 'Test';
        $action->deadline = now()->addDays(10);
        $action->priority = 'high';
        $action->status = 'in_progress';
        $action->responsible_id = $user->id;
        $action->setRelation('responsible', $user);
        event(new ActionAssigned($action));
        $this->line('   ✓');
    }

    private function test13(User $user): void
    {
        $this->line('13. Action Deadline...');
        $action = new Action();
        $action->id = 999;
        $action->title = 'Test Action';
        $action->deadline = now()->addDays(2);
        $action->status = 'in_progress';
        $action->responsible_id = $user->id;
        $action->setRelation('responsible', $user);
        event(new ActionDeadlineApproaching($action, 2));
        $this->line('   ✓');
    }

    private function test14(User $user): void
    {
        $this->line('14. Action Overdue...');
        $action = new Action();
        $action->id = 999;
        $action->title = 'Test Action';
        $action->deadline = now()->subDays(5);
        $action->status = 'in_progress';
        $action->responsible_id = $user->id;
        $action->initiator_id = $user->id;
        $action->setRelation('responsible', $user);
        $action->setRelation('initiator', $user);
        event(new ActionOverdue($action, 5));
        $this->line('   ✓');
    }

    private function test15(User $user): void
    {
        $this->line('15. Subscription Activated...');
        $sub = new Subscription();
        $sub->id = 999;
        $sub->plan_name = 'Premium';
        $sub->start_date = now();
        $sub->end_date = now()->addYear();
        $sub->status = 'active';
        $ent = new Enterprise();
        $ent->id = 999;
        $ent->name = 'Test Enterprise';
        $sub->setRelation('enterprise', $ent);
        event(new SubscriptionActivated($sub));
        $this->line('   ✓');
    }

    private function test16(User $user): void
    {
        $this->line('16. Subscription Expiring...');
        $sub = new Subscription();
        $sub->id = 999;
        $sub->plan_name = 'Premium';
        $sub->end_date = now()->addDays(3);
        $sub->status = 'active';
        $ent = new Enterprise();
        $ent->id = 999;
        $ent->name = 'Test Enterprise';
        $sub->setRelation('enterprise', $ent);
        event(new SubscriptionExpiring($sub, 3));
        $this->line('   ✓');
    }

    private function test17(User $user): void
    {
        $this->line('17. Subscription Expired...');
        $sub = new Subscription();
        $sub->id = 999;
        $sub->plan_name = 'Premium';
        $sub->end_date = now()->subDay();
        $sub->status = 'expired';
        $ent = new Enterprise();
        $ent->id = 999;
        $ent->name = 'Test Enterprise';
        $sub->setRelation('enterprise', $ent);
        event(new SubscriptionExpired($sub));
        $this->line('   ✓');
    }

    private function test18(User $user): void
    {
        $this->line('18. User Mentioned...');
        $author = User::where('id', '!=', $user->id)->first() ?? $user;
        $comment = (object) ['id' => 1, 'content' => "Hey @{$user->name}!"];
        $entity = (object) ['id' => 1, 'title' => 'Test'];
        event(new UserMentioned($user, $author, $comment, $entity));
        $this->line('   ✓');
    }

    private function test19(User $user): void
    {
        $this->line('19. Export Completed...');
        event(new ExportCompleted($user, 'Inventaire.xlsx', '/exports/test.xlsx', 100, true, null, 'inventory'));
        $this->line('   ✓');
    }

    private function displayResults(): void
    {
        $this->newLine(2);
        $this->info('═══ RÉSULTATS ═══');
        $this->newLine();

        $total = count($this->results);
        $success = count(array_filter($this->results, fn($r) => $r === '✅'));

        foreach ($this->results as $test => $result) {
            $this->line(sprintf('%-30s %s', $test, $result));
        }

        $this->newLine();
        $this->info("✅ {$success}/{$total} tests");

        if ($success === $total) {
            $this->info('🎉 TOUS PASSÉS !');
        }

        $this->newLine();
        $this->line('📋 Vérifier logs:');
        $this->line('   tail -f storage/logs/laravel.log | grep "Notification sent"');
    }
}
