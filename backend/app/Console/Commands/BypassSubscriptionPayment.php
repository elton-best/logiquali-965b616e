<?php

namespace App\Console\Commands;

use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Offer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BypassSubscriptionPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:bypass-payment 
                            {enterprise_id : L\'ID de l\'entreprise} 
                            {offer_id : L\'ID de l\'offre à activer} 
                            {--months=12 : Durée en mois de l\'abonnement gratuit}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Active ou renouvelle un abonnement entreprise sans paiement obligatoire (Usage Administrateur)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enterpriseId = $this->argument('enterprise_id');
        $offerId = $this->argument('offer_id');
        $months = (int) $this->option('months');

        try {
            $enterprise = Enterprise::findOrFail($enterpriseId);
            $offer = Offer::findOrFail($offerId);
            
            $site = $enterprise->sites()->where('is_headquarter', true)->first() 
                ?: $enterprise->sites()->first();

            if (!$site) {
                $this->error("Aucun site trouvé pour l'entreprise {$enterprise->name}");
                return 1;
            }

            $this->info("Activation d'une souscription gratuite pour: {$enterprise->name}");
            $this->line("Offre: {$offer->name}");
            $this->line("Site: {$site->name}");
            $this->line("Durée: {$months} mois");

            if (!$this->confirm('Voulez-vous continuer ?')) {
                return 0;
            }

            DB::transaction(function () use ($enterprise, $site, $offer, $months) {
                // 1. Désactiver les anciennes souscriptions conflictuelles pour cette offre sur ce site
                EnterpriseSubscription::where('site_id', $site->id)
                    ->where('offer_id', $offer->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'status' => 'expired'
                    ]);

                // 2. Créer la nouvelle souscription active
                $subscription = EnterpriseSubscription::create([
                    'site_id' => $site->id,
                    'offer_id' => $offer->id,
                    'start_date' => now(),
                    'expiration_date' => now()->addMonths($months),
                    'is_active' => true,
                    'is_trial' => false,
                    'status' => 'active',
                    'payment_status' => 'completed',
                    'subscription_type' => $site->hasActiveSubscription() ? 'addon' : 'primary',
                    'amount_paid' => $offer->price,
                    'payment_method' => 'admin_bypass',
                ]);

                // 3. Auto-configurer l'entreprise si nécessaire
                if (!$enterprise->domaine_activite_set) {
                    $enterprise->update(['domaine_activite_set' => true]);
                }

                $this->info("✅ Souscription {$subscription->ref} créée et activée jusqu'au " . $subscription->expiration_date->format('d/m/Y'));
            });

            return 0;

        } catch (\Exception $e) {
            $this->error("Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
