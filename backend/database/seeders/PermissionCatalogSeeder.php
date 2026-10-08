<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * RBAC-1 — Catalogue complet des permissions LOGIQUALI
 *
 * Ce seeder crée les ~255 permissions du nouveau catalogue unifié.
 * Il est ADDITIF : il ne supprime pas les anciennes permissions.
 * Idempotent : utilise firstOrCreate, peut être relancé sans risque.
 *
 * Nomenclature : {module}.{sous_module}.{section}.{action}
 *
 * Actions standard :
 *   read            — Consulter / lister
 *   create          — Créer
 *   update          — Modifier
 *   delete          — Supprimer
 *   export          — Exporter (PDF, Excel, Word, DOCX)
 *   import          — Importer (Excel, CSV)
 *   validate        — Valider / approuver
 *   manage          — Accès complet (toutes les actions)
 *   evaluate        — Évaluer (risques, auditeurs, prestataires…)
 *   update_tracking — Mettre à jour le suivi (taux, statut)
 *   archive         — Archiver
 *   download        — Télécharger un fichier
 *   assign          — Assigner à un utilisateur
 *   manage_*        — Gérer une sous-ressource spécifique
 */
class PermissionCatalogSeeder extends Seeder
{
    /**
     * Catalogue complet des permissions.
     * Structuré par groupe pour la lisibilité.
     *
     * @return array<int, string>
     */
    private function getCatalog(): array
    {
        return array_merge(
            $this->getFixedTabsPermissions(),
            $this->getContextePermissions(),
            $this->getLeadershipPermissions(),
            $this->getPlanificationPermissions(),
            $this->getSupportPermissions(),
            $this->getRealisationPermissions(),
            $this->getEvaluationPermissions(),
            $this->getAmeliorationPermissions(),
            $this->getIso45001Permissions(),
            $this->getIso14001Permissions(),
            $this->getIso50001Permissions(),
            $this->getIso27001Permissions(),
            $this->getIso22000Permissions(),
            $this->getSystemPermissions(),
        );
    }

    // =========================================================================
    // ONGLETS FIXES (sidebar)
    // =========================================================================

    private function getFixedTabsPermissions(): array
    {
        return [
            // Mes Tâches et Bibliothèque des normes : accès libre, pas de permission
            // Sites
            'sites.read',
            'sites.create',
            'sites.update',
            'sites.delete',
            'sites.manage',
            // Vérification documentaire
            'verify_documents',
            // Approbation documentaire
            'approve_documents',
        ];
    }

    // =========================================================================
    // MODULE : Contexte de l'organisme (Point 4)
    // =========================================================================

    private function getContextePermissions(): array
    {
        return [
            // Sous-module : Compréhension de l'organisme — SWOT/PESTEL
            // (document unique par site — pas de create distinct)
            'contexte.comprehension_organisme.read',
            'contexte.comprehension_organisme.update',
            'contexte.comprehension_organisme.export',

            // Sous-module : Parties intéressées
            'contexte.parties_interessees.read',
            'contexte.parties_interessees.create',
            'contexte.parties_interessees.update',
            'contexte.parties_interessees.delete',
            'contexte.parties_interessees.export',

            // Sous-module : Domaine d'application
            // (document unique par site — pas de create distinct)
            'contexte.domaine_application.read',
            'contexte.domaine_application.update',
            'contexte.domaine_application.export',

            // Sous-module : Système de management — Cartographie des processus
            'contexte.systeme_management.read',
            'contexte.systeme_management.create',
            'contexte.systeme_management.update',
            'contexte.systeme_management.delete',
            'contexte.systeme_management.export',
            'contexte.systeme_management.manage_indicators',
            'contexte.systeme_management.manage_risks',
        ];
    }

    // =========================================================================
    // MODULE : Leadership (Point 5)
    // =========================================================================

    private function getLeadershipPermissions(): array
    {
        return [
            // Sous-module : Politique QHSE
            // (versionnée — create = nouvelle version)
            'leadership.politique.read',
            'leadership.politique.create',
            'leadership.politique.update',
            'leadership.politique.delete',
            'leadership.politique.validate',
            'leadership.politique.export',

            // Sous-module : Rôles et responsabilités
            // Section : Organigramme
            'leadership.roles_responsabilites.organigramme.read',
            'leadership.roles_responsabilites.organigramme.create',
            'leadership.roles_responsabilites.organigramme.update',
            'leadership.roles_responsabilites.organigramme.delete',

            // Section : Liste du personnel
            'leadership.roles_responsabilites.personnel.read',
            'leadership.roles_responsabilites.personnel.create',
            'leadership.roles_responsabilites.personnel.update',
            'leadership.roles_responsabilites.personnel.delete',
            'leadership.roles_responsabilites.personnel.manage_permissions',
            'leadership.roles_responsabilites.personnel.export',

            // Section : Fiches de poste
            'leadership.roles_responsabilites.fiche_poste.read',
            'leadership.roles_responsabilites.fiche_poste.create',
            'leadership.roles_responsabilites.fiche_poste.update',
            'leadership.roles_responsabilites.fiche_poste.delete',
            'leadership.roles_responsabilites.fiche_poste.export',

            // Section : Fiches de responsabilité
            'leadership.roles_responsabilites.fiche_responsabilite.read',
            'leadership.roles_responsabilites.fiche_responsabilite.create',
            'leadership.roles_responsabilites.fiche_responsabilite.update',
            'leadership.roles_responsabilites.fiche_responsabilite.delete',
            'leadership.roles_responsabilites.fiche_responsabilite.export',
        ];
    }

    // =========================================================================
    // MODULE : Planification (Point 6)
    // =========================================================================

    private function getPlanificationPermissions(): array
    {
        return [
            // Sous-module : Risques et opportunités
            'planification.risques_opportunites.read',
            'planification.risques_opportunites.create',
            'planification.risques_opportunites.update',
            'planification.risques_opportunites.delete',
            'planification.risques_opportunites.evaluate',
            'planification.risques_opportunites.export',

            // Sous-module : Objectifs qualité
            'planification.objectifs.read',
            'planification.objectifs.create',
            'planification.objectifs.update',
            'planification.objectifs.delete',
            'planification.objectifs.manage_actions',
            'planification.objectifs.update_tracking',
            'planification.objectifs.export',

            // Sous-module : Plans du SM
            'planification.plans_sm.read',
            'planification.plans_sm.create',
            'planification.plans_sm.update',
            'planification.plans_sm.delete',
            'planification.plans_sm.update_tracking',
            'planification.plans_sm.archive',
            'planification.plans_sm.export',
        ];
    }

    // =========================================================================
    // MODULE : Support (Point 7)
    // =========================================================================

    private function getSupportPermissions(): array
    {
        return [
            // Sous-module : Ressources / Équipements
            'support.ressources.read',
            'support.ressources.create',
            'support.ressources.update',
            'support.ressources.delete',
            'support.ressources.transfer',
            'support.ressources.manage_maintenance',
            'support.ressources.manage_calibration',
            'support.ressources.export',

            // Sous-module : Compétences / Formations
            'support.competences.read',
            'support.competences.create',
            'support.competences.update',
            'support.competences.delete',
            'support.competences.manage_participants',
            'support.competences.update_tracking',
            'support.competences.export',

            // Sous-module : Sensibilisation
            'support.sensibilisation.read',
            'support.sensibilisation.create',
            'support.sensibilisation.update',
            'support.sensibilisation.delete',
            'support.sensibilisation.update_tracking',

            // Sous-module : Communication
            'support.communication.read',
            'support.communication.create',
            'support.communication.update',
            'support.communication.delete',
            'support.communication.update_tracking',
            'support.communication.export',

            // Sous-module : Information documentée
            'support.documents.read',
            'support.documents.create',
            'support.documents.update',
            'support.documents.delete',
            'support.documents.download',
            'support.documents.import',
            'support.documents.export',
            'support.documents.configure_nomenclature',
            'support.documents.manage_types',
        ];
    }

    // =========================================================================
    // MODULE : Réalisation (Point 8)
    // =========================================================================

    private function getRealisationPermissions(): array
    {
        return [
            // Sous-module : Planification et maîtrise opérationnelle
            'realisation.planification_operationnelle.read',
            'realisation.planification_operationnelle.create',
            'realisation.planification_operationnelle.update',
            'realisation.planification_operationnelle.delete',
            'realisation.planification_operationnelle.update_tracking',
            'realisation.planification_operationnelle.export',

            // Sous-module : Obligations de conformité
            'realisation.obligations_conformite.read',
            'realisation.obligations_conformite.create',
            'realisation.obligations_conformite.update',
            'realisation.obligations_conformite.delete',
            'realisation.obligations_conformite.export',

            // Sous-module : Conception et développement
            'realisation.conception_developpement.read',
            'realisation.conception_developpement.create',
            'realisation.conception_developpement.update',
            'realisation.conception_developpement.delete',
            'realisation.conception_developpement.export',

            // Sous-module : Gestion des prestataires
            'realisation.prestataires.read',
            'realisation.prestataires.create',
            'realisation.prestataires.update',
            'realisation.prestataires.delete',
            'realisation.prestataires.evaluate',
            'realisation.prestataires.manage_documents',
            'realisation.prestataires.export',

            // Sous-module : Production et prestation de service
            'realisation.production.read',
            'realisation.production.create',
            'realisation.production.update',
            'realisation.production.delete',

            // Sous-module : Libération des produits et services
            'realisation.liberation.read',
            'realisation.liberation.create',
            'realisation.liberation.update',
            'realisation.liberation.delete',
            'realisation.liberation.validate',

            // Sous-module : Maîtrise des sorties non conformes
            'realisation.sorties_non_conformes.read',
            'realisation.sorties_non_conformes.create',
            'realisation.sorties_non_conformes.update',
            'realisation.sorties_non_conformes.delete',
            'realisation.sorties_non_conformes.manage_actions',
            'realisation.sorties_non_conformes.update_tracking',
            'realisation.sorties_non_conformes.manage_complaints',
        ];
    }

    // =========================================================================
    // MODULE : Évaluation des performances (Point 9)
    // =========================================================================

    private function getEvaluationPermissions(): array
    {
        return [
            // Sous-module : Évaluations PIP (Surveillance)
            'evaluation.pip.read',
            'evaluation.pip.create',
            'evaluation.pip.update',
            'evaluation.pip.delete',
            'evaluation.pip.define_criteria',
            'evaluation.pip.generate_link',
            'evaluation.pip.view_submissions',
            'evaluation.pip.export',

            // Sous-module : Revue Processus
            'evaluation.revue_processus.read',
            'evaluation.revue_processus.create',
            'evaluation.revue_processus.update',
            'evaluation.revue_processus.validate',

            // Sous-module : Audits internes
            'evaluation.audits.read',
            'evaluation.audits.create',
            'evaluation.audits.update',
            'evaluation.audits.delete',
            'evaluation.audits.add_findings',
            'evaluation.audits.evaluate_auditors',
            'evaluation.audits.generate_report',
            'evaluation.audits.export',

            // Sous-module : Revue de direction
            'evaluation.revue_direction.read',
            'evaluation.revue_direction.create',
            'evaluation.revue_direction.update',
            'evaluation.revue_direction.delete',
            'evaluation.revue_direction.validate',
            'evaluation.revue_direction.export',
        ];
    }

    // =========================================================================
    // MODULE : Amélioration (Point 10)
    // =========================================================================

    private function getAmeliorationPermissions(): array
    {
        return [
            // Sous-module : Non-conformités et actions correctives
            'amelioration.non_conformites.read',
            'amelioration.non_conformites.create',
            'amelioration.non_conformites.update',
            'amelioration.non_conformites.delete',
            'amelioration.non_conformites.analyze_causes',
            'amelioration.non_conformites.manage_actions',
            'amelioration.non_conformites.validate_closure',
            'amelioration.non_conformites.update_tracking',
            'amelioration.non_conformites.export',

            // Sous-module : Amélioration continue
            'amelioration.amelioration_continue.read',
            'amelioration.amelioration_continue.create',
            'amelioration.amelioration_continue.update',
            'amelioration.amelioration_continue.delete',
            'amelioration.amelioration_continue.validate',
        ];
    }

    // =========================================================================
    // ISO 45001 — Santé et Sécurité au Travail (spécifique)
    // =========================================================================

    private function getIso45001Permissions(): array
    {
        return [
            // Consultation et participation des travailleurs
            'sst.consultation_participation.read',
            'sst.consultation_participation.create',
            'sst.consultation_participation.update',
            'sst.consultation_participation.delete',

            // DUERP
            'sst.duerp.read',
            'sst.duerp.create',
            'sst.duerp.update',
            'sst.duerp.delete',
            'sst.duerp.export',

            // Identification des dangers SST
            'sst.identification_dangers.read',
            'sst.identification_dangers.create',
            'sst.identification_dangers.update',
            'sst.identification_dangers.delete',
            'sst.identification_dangers.evaluate',

            // Exigences légales SST
            'sst.exigences_legales.read',
            'sst.exigences_legales.create',
            'sst.exigences_legales.update',
            'sst.exigences_legales.delete',

            // Habilitations
            'sst.habilitations.read',
            'sst.habilitations.create',
            'sst.habilitations.update',
            'sst.habilitations.delete',
            'sst.habilitations.manage_alerts',

            // EPI (Équipements de Protection Individuelle)
            'sst.epi.read',
            'sst.epi.manage_stock',
            'sst.epi.assign',
            'sst.epi.view_movements',

            // VGP (Vérifications Générales Périodiques)
            'sst.vgp.read',
            'sst.vgp.create',
            'sst.vgp.update',
            'sst.vgp.delete',
            'sst.vgp.validate',
        ];
    }

    // =========================================================================
    // ISO 14001 — Management Environnemental (spécifique)
    // =========================================================================

    private function getIso14001Permissions(): array
    {
        return [
            // Aspects environnementaux significatifs
            'environnement.aspects.read',
            'environnement.aspects.create',
            'environnement.aspects.update',
            'environnement.aspects.delete',
            'environnement.aspects.evaluate',

            // Revue environnementale
            'environnement.revue_environnementale.read',
            'environnement.revue_environnementale.create',
            'environnement.revue_environnementale.update',

            // Maîtrise opérationnelle environnementale
            'environnement.maitrise_operationnelle.read',
            'environnement.maitrise_operationnelle.create',
            'environnement.maitrise_operationnelle.update',
            'environnement.maitrise_operationnelle.delete',

            // Préparation aux urgences environnementales
            'environnement.urgences.read',
            'environnement.urgences.create',
            'environnement.urgences.update',
            'environnement.urgences.delete',
        ];
    }

    // =========================================================================
    // ISO 50001 — Management de l'Énergie (spécifique)
    // =========================================================================

    private function getIso50001Permissions(): array
    {
        return [
            // Revue énergétique
            'energie.revue_energetique.read',
            'energie.revue_energetique.create',
            'energie.revue_energetique.update',

            // Indicateurs de performance énergétique (IPÉ)
            'energie.ipe.read',
            'energie.ipe.create',
            'energie.ipe.update',
            'energie.ipe.delete',
            'energie.ipe.calculate',

            // Consommations d'énergie
            'energie.consommations.read',
            'energie.consommations.create',
            'energie.consommations.update',
            'energie.consommations.delete',
            'energie.consommations.import',

            // Objectifs et cibles énergétiques
            'energie.objectifs_energetiques.read',
            'energie.objectifs_energetiques.create',
            'energie.objectifs_energetiques.update',
            'energie.objectifs_energetiques.delete',
        ];
    }

    // =========================================================================
    // ISO 27001 — Sécurité de l'Information (spécifique)
    // =========================================================================

    private function getIso27001Permissions(): array
    {
        return [
            // Évaluation des risques SI
            'si.evaluation_risques.read',
            'si.evaluation_risques.create',
            'si.evaluation_risques.update',
            'si.evaluation_risques.delete',
            'si.evaluation_risques.evaluate',

            // Déclaration d'applicabilité (SoA)
            'si.declaration_applicabilite.read',
            'si.declaration_applicabilite.create',
            'si.declaration_applicabilite.update',

            // Inventaire des actifs informationnels
            'si.inventaire_actifs.read',
            'si.inventaire_actifs.create',
            'si.inventaire_actifs.update',
            'si.inventaire_actifs.delete',

            // Contrôles d'accès
            'si.controles_acces.read',
            'si.controles_acces.create',
            'si.controles_acces.update',
            'si.controles_acces.delete',

            // Gestion des incidents de sécurité
            'si.incidents_si.read',
            'si.incidents_si.create',
            'si.incidents_si.update',
            'si.incidents_si.close',
        ];
    }

    // =========================================================================
    // ISO 22000 — Sécurité des Denrées Alimentaires (spécifique)
    // =========================================================================

    private function getIso22000Permissions(): array
    {
        return [
            // Analyse des dangers (HACCP)
            'alimentaire.analyse_dangers.read',
            'alimentaire.analyse_dangers.create',
            'alimentaire.analyse_dangers.update',
            'alimentaire.analyse_dangers.delete',

            // Points critiques de contrôle (CCP)
            'alimentaire.ccp.read',
            'alimentaire.ccp.create',
            'alimentaire.ccp.update',
            'alimentaire.ccp.delete',
            'alimentaire.ccp.monitor',

            // Plan HACCP
            'alimentaire.plan_haccp.read',
            'alimentaire.plan_haccp.create',
            'alimentaire.plan_haccp.update',
            'alimentaire.plan_haccp.validate',

            // Traçabilité alimentaire
            'alimentaire.tracabilite.read',
            'alimentaire.tracabilite.create',
            'alimentaire.tracabilite.update',
            'alimentaire.tracabilite.delete',
        ];
    }

    // =========================================================================
    // PERMISSIONS SYSTÈME (admin entreprise / gestion des rôles)
    // =========================================================================

    private function getSystemPermissions(): array
    {
        return [
            // Gestion des rôles
            'roles.read',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign',
            'roles.copy_to_site',

            // Gestion des permissions des collaborateurs
            'permissions.manage',

            // Dashboard
            'dashboard.read',

            // Paramètres
            'settings.read',
            'settings.update',

            // Journal de sécurité
            'security_audit.read',

            // Abonnements
            'subscriptions.read',
            'subscriptions.create',
            'subscriptions.update',
            'subscriptions.renew',
            'subscriptions.cancel',

            // Utilisateurs (gestion interne)
            'users.read',
            'users.create',
            'users.update',
            'users.delete',
            'users.manage',

            // Bibliothèque des normes (lecture seule pour tous)
            'norm_library.read',
        ];
    }

    // =========================================================================
    // EXÉCUTION
    // =========================================================================

    public function run(): void
    {
        $this->command?->info('🚀 RBAC-1 — Création du catalogue de permissions (309 permissions)...');

        $catalog = $this->getCatalog();

        // Dédupliquer au cas où
        $catalog = array_values(array_unique($catalog));

        $created = 0;
        $existing = 0;

        foreach ($catalog as $permissionName) {
            $permission = Permission::withoutGlobalScopes()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission) {
                // Restaurer si soft-deleted
                if (
                    array_key_exists('deleted_at', $permission->getAttributes())
                    && $permission->getAttribute('deleted_at') !== null
                ) {
                    $permission->forceFill(['deleted_at' => null])->save();
                    $created++;
                } else {
                    $existing++;
                }
                continue;
            }

            Permission::create([
                'name'       => $permissionName,
                'guard_name' => 'web',
            ]);
            $created++;
        }

        $total = count($catalog);

        $this->command?->info("✅ Catalogue créé : {$total} permissions au total");
        $this->command?->info("   ✓ Nouvelles : {$created}");
        $this->command?->info("   ✓ Déjà existantes : {$existing}");
        $this->command?->info('');
        $this->command?->info('   Répartition :');
        $this->command?->info('   - Onglets fixes (Sites, Vérification, Approbation) : 9');
        $this->command?->info('   - Contexte de l\'organisme : 17');
        $this->command?->info('   - Leadership : 22');
        $this->command?->info('   - Planification : 20');
        $this->command?->info('   - Support : 28');
        $this->command?->info('   - Réalisation : 35');
        $this->command?->info('   - Évaluation des performances : 26');
        $this->command?->info('   - Amélioration : 14');
        $this->command?->info('   - ISO 45001 (SST) : 25');
        $this->command?->info('   - ISO 14001 (Environnement) : 16');
        $this->command?->info('   - ISO 50001 (Énergie) : 14');
        $this->command?->info('   - ISO 27001 (Sécurité SI) : 20');
        $this->command?->info('   - ISO 22000 (Alimentaire) : 16');
        $this->command?->info('   - Système (rôles, permissions, dashboard…) : 22');
    }
}
