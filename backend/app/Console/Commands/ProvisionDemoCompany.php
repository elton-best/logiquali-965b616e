<?php

namespace App\Console\Commands;

use Database\Seeders\DemoCompanySeeder;
use Illuminate\Console\Command;

class ProvisionDemoCompany extends Command
{
    protected $signature = 'demo:provision {--show-only : Affiche les identifiants configurés sans modifier la base}';

    protected $description = 'Crée ou met à jour le compte entreprise démo dans la base Laravel';

    public function handle(DemoCompanySeeder $seeder): int
    {
        if ($this->option('show-only')) {
            $this->line('Email: ' . env('DEMO_COMPANY_EMAIL', 'demo.entreprise@logiquali.test'));
            $this->line('Username: ' . env('DEMO_COMPANY_USERNAME', 'demo.entreprise'));
            $this->line('Mot de passe: ' . env('DEMO_COMPANY_PASSWORD', 'DemoLogiQuali2026!'));
            return self::SUCCESS;
        }

        try {
            $credentials = $seeder->provision();
        } catch (\Throwable $exception) {
            $this->error('Provisionnement impossible: ' . $exception->getMessage());
            report($exception);
            return self::FAILURE;
        }

        $this->info('Compte entreprise démo provisionné dans la base Laravel.');
        $this->line('Email: ' . $credentials['email']);
        $this->line('Username: ' . $credentials['username']);
        $this->line('Mot de passe: ' . $credentials['password']);
        $this->line('Enterprise ID: ' . $credentials['enterprise_id']);
        $this->line('Site ID: ' . $credentials['site_id']);
        $this->warn('En local, activez MFA_EXPOSE_OTP_FOR_E2E=true pour afficher le code MFA dans la réponse de connexion.');

        return self::SUCCESS;
    }
}
