<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Offer;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Provisions an explicit local/demo company account in the Laravel database.
 *
 * This seeder is never called by the normal production database seed. Use the
 * `demo:provision` command deliberately when a test account is needed.
 */
class DemoCompanySeeder extends Seeder
{
    public function provision(): array
    {
        $email = (string) env('DEMO_COMPANY_EMAIL', 'demo.entreprise@logiquali.test');
        $username = (string) env('DEMO_COMPANY_USERNAME', 'demo.entreprise');
        $password = (string) env('DEMO_COMPANY_PASSWORD', 'DemoLogiQuali2026!');
        $enterpriseEmail = (string) env('DEMO_ENTERPRISE_EMAIL', 'demo.entreprise@logiquali.test');

        return DB::transaction(function () use ($email, $username, $password, $enterpriseEmail): array {
            $trialEndsAt = now()->addYear();

            $enterprise = Enterprise::updateOrCreate(
                ['email' => $enterpriseEmail],
                [
                    'name' => 'LOGIQUALI Entreprise Démo',
                    'sigle' => 'DEMO',
                    'registration_number' => 'DEMO-RCCM-001',
                    'rccm_number' => 'DEMO-RCCM-001',
                    'ifu_number' => 'DEMO-IFU-001',
                    'status' => 'active',
                    'approval_status' => 'approved',
                    'field' => 'Conseil et services QHSE',
                    'domaine_activite_set' => true,
                    'address' => 'Cotonou, Bénin',
                    'city' => 'Cotonou',
                    'country' => 'Bénin',
                    'trial_ends_at' => $trialEndsAt,
                ],
            );

            $site = Site::updateOrCreate(
                [
                    'enterprise_id' => $enterprise->id,
                    'name' => 'Site principal — Démo',
                ],
                [
                    'location' => 'Cotonou, Bénin',
                    'city' => 'Cotonou',
                    'is_headquarter' => true,
                    'is_active' => true,
                ],
            );

            $offer = Offer::updateOrCreate(
                ['ref' => 'DEMO-TRIAL'],
                [
                    'name' => 'Essai entreprise démo',
                    'description' => 'Offre locale réservée aux tests du compte entreprise démo.',
                    'is_active' => true,
                    'price' => 0,
                    'duration_months' => 12,
                ],
            );

            EnterpriseSubscription::updateOrCreate(
                ['ref' => 'SUB-DEMO-TRIAL'],
                [
                    'offer_id' => $offer->id,
                    'site_id' => $site->id,
                    'start_date' => now(),
                    'expiration_date' => $trialEndsAt,
                    'is_active' => true,
                    'is_trial' => true,
                    'trial_ends_at' => $trialEndsAt,
                    'status' => 'trial',
                    'payment_status' => 'completed',
                    'subscription_type' => 'primary',
                ],
            );

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Administrateur Démo',
                    'first_name' => 'Administrateur',
                    'last_name' => 'Démo',
                    'username' => $username,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'phone' => '+229 00 00 00 00',
                    'address' => 'Cotonou, Bénin',
                    'user_type' => User::TYPE_COMPANY,
                    'enterprise_id' => $enterprise->id,
                    'site_id' => $site->id,
                    'role' => 'admin_entreprise',
                    'is_active' => true,
                    'must_change_password' => false,
                    'collaborator_approval_status' => 'activated',
                ],
            );

            $enterprise->forceFill([
                'owner_user_id' => $user->id,
                'created_by' => $user->id,
            ])->save();

            try {
                $user->syncRoles(['admin_entreprise']);
            } catch (\Throwable $exception) {
                // The role catalog may not yet have been seeded. The command
                // still provisions the account and reports the role warning.
                report($exception);
            }

            return [
                'email' => $email,
                'username' => $username,
                'password' => $password,
                'enterprise_id' => $enterprise->id,
                'site_id' => $site->id,
                'user_id' => $user->id,
            ];
        });
    }

    public function run(): void
    {
        $credentials = $this->provision();

        $this->command?->info('Compte entreprise démo provisionné dans Laravel.');
        $this->command?->line('Email: ' . $credentials['email']);
        $this->command?->line('Username: ' . $credentials['username']);
        $this->command?->line('Mot de passe: ' . $credentials['password']);
    }
}
