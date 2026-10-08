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

class TestNotifications extends Command
{
    protected $signature = 'notifications:test-all
                            {--email= : Email destinataire pour tous les tests}
                            {--type= : Tester un type spécifique}';

    protected $description = 'Test all notification types';

    private $testEmail;
    private $results = [];

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║  🧪  TEST COMPLET DES NOTIFICATIONS  🧪               ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Email de test
        $this->testEmail = $this->option('email') ?? 'emerytossavi+1010002@gmail.com';
        $this->info("📧 Email de test: {$this->testEmail}");
        $this->newLine();

        // Vérifier MAIL_MAILER
        $mailer = config('mail.mailer');
        $this->warn("⚙️  MAIL_MAILER: {$mailer}");
        if ($mailer === 'log') {
            $this->info("✅ Emails seront dans storage/logs/laravel.log");
        }
        $this->newLine();

        // Créer utilisateur test
        $testUser = $this->createTestUser();

        // Lancer tests
        $type = $this->option('type');

        if ($type) {
            $this->runSpecificTest($type, $testUser);
        } else {
            $this->runAllTests($testUser);
        }

        // Afficher résultats
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
                'username' => 'Test User',
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
        $this->info('🚀 Lancement de TOUS les tests...');
        $this->newLine();

        $tests = [
            'welcome_collaborator' => fn() => $this->testWelcomeCollaborator($testUser),
            'welcome_client' => fn() => $this->testWelcomeClient($testUser),
            'password_reset' => fn() => $this->testPasswordReset($testUser),
            'enterprise_registration' => fn() => $this->testEnterpriseRegistration($testUser),
            'enterprise_approved' => fn() => $this->testEnterpriseApproved($testUser),
            'enterprise_rejected' => fn() => $this->testEnterpriseRejected($testUser),
            'complaint_submitted' => fn() => $this->testComplaintSubmitted($testUser),
            'complaint_replied' => fn() => $this->testComplaintReplied($testUser),
            'complaint_closed' => fn() => $this->testComplaintClosed($testUser),
            'document_approval' => fn() => $this->testDocumentApproval($testUser),
            'document_published' => fn() => $this->testDocumentPublished($testUser),
            'action_assigned' => fn() => $this->testActionAssigned($testUser),
            'action_deadline' => fn() => $this->testActionDeadline($testUser),
            'action_overdue' => fn() => $this->testActionOverdue($testUser),
            'subscription_activated' => fn() => $this->testSubscriptionActivated($testUser),
            'subscription_expiring' => fn() => $this->testSubscriptionExpiring($testUser),
            'subscription_expired' => fn() => $this->testSubscriptionExpired($testUser),
            'user_mentioned' => fn() => $this->testUserMentioned($testUser),
            'export_completed' => fn() => $this->testExportCompleted($testUser),
        ];

        foreach ($tests as $name => $test) {
            try {
                $test();
                $this->results[$name] = '✅';
            } catch (\Exception $e) {
                $this->results[$name] = '❌ ' . $e->getMessage();
                $this->error("   Erreur: {$e->getMessage()}");
            }
        }
    }

    private function runSpecificTest(string $type, User $testUser): void
    {
        $this->info("🎯 Test spécifique: {$type}");
        $this->newLine();

        $method = 'test' . Str::studly($type);

        if (method_exists($this, $method)) {
            try {
                $this->$method($testUser);
                $this->results[$type] = '✅';
            } catch (\Exception $e) {
                $this->results[$type] = '❌ ' . $e->getMessage();
                $this->error("Erreur: {$e->getMessage()}");
            }
        } else {
            $this->error("Type de test inconnu: {$type}");
        }
    }

    // === TESTS INDIVIDUELS ===

    private function testWelcomeCollaborator(User $user): void
    {
        $this->line('1. 📧 Welcome Collaborator...');
        event(new UserCreated($user, 'collaborator', 'temporary_password', 'TempPass123'));
        $this->line('   ✓ Event dispatched');
    }

    private function testWelcomeClient(User $user): void
    {
        $this->line('2. 📧 Welcome Client...');
        event(new UserCreated($user, 'client', 'set_password_link', null, 'http://localhost:3000/set-password'));
        $this->line('   ✓ Event dispatched');
    }

    private function testPasswordReset(User $user): void
    {
        $this->line('3. 📧 Password Reset...');
        $token = Str::random(64);
        $resetUrl = "http://localhost:3000/reset-password?token={$token}";
        event(new PasswordResetRequested($user, $token, $resetUrl));
        $this->line('   ✓ Event dispatched');
    }

    private function testEnterpriseRegistration(User $user): void
    {
        $this->line('4. 📧 Enterprise Registration...');
        $enterprise = Enterprise::firstOrCreate(
            ['name' => 'Test Enterprise'],
            ['email' => 'emerytossavi+1010002@gmail.com'],
            ['address' => '123 Test St', 'status' => 'pending']
        );
        event(new EnterpriseRegistered($enterprise, $user));
        $this->line('   ✓ Event dispatched');
    }

    private function testEnterpriseApproved(User $user): void
    {
        $this->line('5. 📧 Enterprise Approved...');
        $enterprise = Enterprise::first() ?? Enterprise::factory()->create();
        event(new EnterpriseApproved($enterprise));
        $this->line('   ✓ Event dispatched');
    }

    private function testEnterpriseRejected(User $user): void
    {
        $this->line('6.  Enterprise Rejected...');
        $enterprise = Enterprise::first() ?? Enterprise::factory()->create();
        event(new EnterpriseRejected($enterprise, 'Documents incomplets'));
        $this->line('    Event dispatched');
    }

    private function testComplaintSubmitted(User $user): void
    {
        $this->line('7.  Complaint Submitted...');
        $complaint = new Reclamation([
            'ref' => 'REC-TEST-001',
            'title' => 'Test Complaint',
            'description' => 'Test description',
            'client_name' => 'Test Client',
        ]);
        event(new ComplaintSubmitted($complaint));
        $this->line('    Event dispatched');
    }

    private function testComplaintReplied(User $user): void
    {
        $this->line('8.  Complaint Replied...');
        $complaint = new Reclamation([
            'ref' => 'REC-TEST-001',
            'title' => 'Test Complaint',
            'description' => 'Test description',
        ]);
        event(new ComplaintReplied($complaint, 'Voici notre réponse...'));
        $this->line('    Event dispatched');
    }

    private function testComplaintClosed(User $user): void
    {
        $this->line('9.  Complaint Closed...');
        $complaint = new Reclamation([
            'ref' => 'REC-TEST-001',
            'title' => 'Test Complaint',
            'description' => 'Test description',
        ]);
        event(new ComplaintClosed($complaint, 'Résolution finale', 'http://survey.com'));
        $this->line('    Event dispatched');
    }

    private function testDocumentApproval(User $user): void
    {
        $this->line('10.  Document Approval Request...');
        $doc = new Document([
            'title' => 'Test Document',
            'code' => 'DOC-001',
            'version' => '1.0',
        ]);
        event(new DocumentSubmittedForApproval($doc, $user));
        $this->line('    Event dispatched');
    }

    private function testDocumentPublished(User $user): void
    {
        $this->line('11.  Document Published...');
        $doc = new Document([
            'title' => 'Test Document',
            'code' => 'DOC-001',
            'version' => '1.0',
        ]);
        event(new DocumentPublished($doc, $user));
        $this->line('    Event dispatched');
    }

    private function testActionAssigned(User $user): void
    {
        $this->line('12.  Action Assigned...');
        $action = new Action([
            'title' => 'Test Action',
            'description' => 'Test description',
            'deadline_date' => now()->addDays(10),
            'priority' => 'high',
            'status' => 'in_progress',
        ]);
        $action->pilot = $user;
        event(new ActionAssigned($action));
        $this->line('    Event dispatched');
    }

    private function testActionDeadline(User $user): void
    {
        $this->line('13.  Action Deadline Approaching...');
        $action = new Action([
            'title' => 'Test Action',
            'deadline_date' => now()->addDays(2),
            'status' => 'in_progress',
        ]);
        $action->pilot = $user;
        event(new ActionDeadlineApproaching($action, 2));
        $this->line('    Event dispatched');
    }

    private function testActionOverdue(User $user): void
    {
        $this->line('14.  Action Overdue...');
        $action = new Action([
            'title' => 'Test Action',
            'deadline_date' => now()->subDays(5),
            'status' => 'in_progress',
        ]);
        $action->pilot = $user;
        event(new ActionOverdue($action, 5));
        $this->line('    Event dispatched');
    }

    private function testSubscriptionActivated(User $user): void
    {
        $this->line('15.  Subscription Activated...');
        $sub = new Subscription([
            'plan_name' => 'Premium',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'active',
        ]);
        $sub->enterprise = new Enterprise(['name' => 'Test Enterprise']);
        event(new SubscriptionActivated($sub));
        $this->line('    Event dispatched');
    }

    private function testSubscriptionExpiring(User $user): void
    {
        $this->line('16.  Subscription Expiring...');
        $sub = new Subscription([
            'plan_name' => 'Premium',
            'end_date' => now()->addDays(3),
            'status' => 'active',
        ]);
        $sub->enterprise = new Enterprise(['name' => 'Test Enterprise']);
        event(new SubscriptionExpiring($sub, 3));
        $this->line('    Event dispatched');
    }

    private function testSubscriptionExpired(User $user): void
    {
        $this->line('17.  Subscription Expired...');
        $sub = new Subscription([
            'plan_name' => 'Premium',
            'end_date' => now()->subDay(),
            'status' => 'expired',
        ]);
        $sub->enterprise = new Enterprise(['name' => 'Test Enterprise']);
        event(new SubscriptionExpired($sub));
        $this->line('    Event dispatched');
    }

    private function testUserMentioned(User $user): void
    {
        $this->line('18.  User Mentioned...');
        $author = User::where('email', '!=', $user->email)->first() ?? $user;
        $comment = (object)['id' => 1, 'content' => "Hey @{$user->name}, check this out!"];
        $entity = (object)['id' => 1, 'title' => 'Test Entity'];

        event(new UserMentioned($user, $author, $comment, $entity));
        $this->line('    Event dispatched');
    }

    private function testExportCompleted(User $user): void
    {
        $this->line('19.  Export Completed...');
        event(new ExportCompleted('export', 'Inventaire Documents', true, null, '/exports/test.xlsx', $user));
        $this->line('    Event dispatched');
    }

    private function displayResults(): void
    {
        $this->newLine(2);
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║    RÉSULTATS DES TESTS                              ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();

        $total = count($this->results);
        $success = count(array_filter($this->results, fn($r) => $r === ''));

        foreach ($this->results as $test => $result) {
            $this->line(sprintf('%-30s %s', $test, $result));
        }

        $this->newLine();
        $this->info(" Tests réussis: {$success}/{$total}");

        if ($success === $total) {
            $this->info(' TOUS LES TESTS SONT PASSÉS ! ');
        }

        $this->newLine();
        $this->warn(' Vérifier les logs:');
        $this->line('   tail -f storage/logs/laravel.log | grep "Notification sent"');
        $this->newLine();
        $this->warn(' Vérifier les emails (si MAIL_MAILER=log):');
        $this->line('   grep "Subject:" storage/logs/laravel.log | tail -20');
    }
}
