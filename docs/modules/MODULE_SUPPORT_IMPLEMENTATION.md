# Module Support - Implémentation complète

## Résumé

Le module Support a été implémenté avec succès avec les sous-modules suivants :
- **Ressources** (Inventaire des équipements + Plan de maintenance)
- **Compétences** (Page vide - à développer)
- **Communication et sensibilisation** (Page vide - à développer)
- **Information documentée** (Page vide - à développer)

## Backend

### Migrations créées
1. `2026_02_06_000001_create_codification_elements_table.php`
   - Stocke les catégories et localisations pour la codification
   - Types: categorie, localisation
   - Champs: type, code, libelle, description, actif

2. `2026_02_06_000002_create_equipements_table.php`
   - Inventaire des équipements
   - Génération automatique du code complet (BEG/CAT/NOM/LOC/IND/ANNEE)
   - Champs: code_complet, categorie_id, localisation_id, nom_commun, état, etc.

3. `2026_02_06_000003_create_maintenances_table.php`
   - Plan de maintenance et d'étalonnage
   - Types: preventive, corrective, etalonnage
   - Statuts: planifie, en_cours, realise, reporte, annule

4. `2026_02_06_000004_create_maintenance_suivis_table.php`
   - Historique des suivis de maintenance
   - Actions: realise, reporte
   - Upload de preuves (documents)

### Models créés
- `CodificationElement.php` - Gestion des éléments de codification
- `Equipement.php` - Gestion des équipements avec génération automatique de code
- `Maintenance.php` - Gestion des maintenances avec système d'alertes
- `MaintenanceSuivi.php` - Historique des suivis

### Controllers créés
- `CodificationController.php` - CRUD complet pour la codification
- `EquipementController.php` - CRUD équipements + génération d'indice
- `MaintenanceController.php` - CRUD maintenances + suivi + alertes

### Routes API
```
/api/v1/codifications (GET, POST, PUT, DELETE)
/api/v1/equipements (GET, POST, PUT, DELETE)
/api/v1/equipements/prochain-indice (GET)
/api/v1/maintenances (GET, POST, PUT, DELETE)
/api/v1/maintenances/{id}/suivre (POST)
/api/v1/maintenances/alertes (GET)
```

### Seeder
- `CodificationSeeder.php` - Données initiales basées sur le document Excel
  - 4 catégories: MOB, INF, MEN, PRO
  - 11 localisations: MAG, SFO, CEO, COO, CON, ADM, DEC, SEC, COU, GUR, CUI

## Frontend

### Services
- `supportService.ts` - Service API pour toutes les opérations Support

### Store
- `supportStore.ts` - Gestion d'état Pinia pour le module Support

### Pages créées
1. `/pages/support/index.vue` - Page d'accueil du module avec navigation
2. `/pages/support/ressources.vue` - Page Ressources avec tabs
3. `/pages/support/competences.vue` - Page vide (à développer)
4. `/pages/support/communication.vue` - Page vide (à développer)
5. `/pages/support/information-documentee.vue` - Page vide (à développer)

### Composants créés

#### Inventaire des équipements
- `InventaireEquipements.vue` - Composant principal
  - Affichage des règles de codification
  - Formulaire d'ajout d'équipement
  - Liste des équipements avec filtres
  - Gestion de l'état (Très bon, Bon, Mauvais)

- `CodificationDialog.vue` - Dialog de gestion de la codification
  - Ajout de catégories et localisations
  - Liste et suppression des éléments

#### Plan de maintenance
- `PlanMaintenance.vue` - Composant principal
  - Tableau des maintenances planifiées
  - Système d'alertes visuelles (7j, 3j, veille, aujourd'hui, dépassée)
  - Bouton "Suivi" activé/désactivé selon la date
  - Codes couleur pour les alertes

- `AddMaintenanceDialog.vue` - Dialog d'ajout de maintenance
  - Sélection d'équipement
  - Type de maintenance
  - Date prévue et responsable

- `SuiviMaintenanceDialog.vue` - Dialog de suivi
  - Action: Réalisé ou Reporté
  - Upload de preuve (tous types de fichiers)
  - Reprogrammation si reporté
  - Historique des suivis

#### Dashboard
- `MaintenanceAlertes.vue` - Widget d'alertes pour le dashboard principal
  - Affichage des maintenances à venir (7 jours)
  - Codes couleur selon l'urgence
  - Lien vers la page de gestion
  - Rafraîchissement automatique toutes les 5 minutes

### Routes
```
/support - Page d'accueil du module
/support/ressources - Inventaire et maintenance
/support/competences - À développer
/support/communication - À développer
/support/information-documentee - À développer
```

### Menu Sidebar
Le module Support a été ajouté dans la sidebar avec 4 sous-menus :
- Ressources (icône: package-variant)
- Compétences (icône: school)
- Communication (icône: bullhorn)
- Information documentée (icône: file-document)

## Fonctionnalités implémentées

### Codification des équipements
✅ Format: BEG/Catégorie/NomCommun/Localisation/Indice/Année
✅ Exemple: BEG/INF/ECR/CEO/001/2025
✅ Génération automatique du code complet
✅ Calcul automatique de l'indice (001, 002, 003...)
✅ Gestion des catégories et localisations

### Inventaire des équipements
✅ Formulaire complet d'ajout
✅ Sélection catégorie et localisation
✅ États: Très bon (neuf), Bon, Mauvais
✅ Informations: marque, modèle, numéro de série, valeur
✅ Option maintenance avec fréquence
✅ Liste avec affichage du code complet
✅ Suppression d'équipements

### Plan de maintenance
✅ Planification de maintenances (préventive, corrective, étalonnage)
✅ Système d'alertes à 7j, 3j, veille, jour J
✅ Bouton "Suivi" grisé avant la date, actif après
✅ Suivi avec deux options:
  - Maintenance effectuée + upload de preuve
  - Maintenance reportée + nouvelle date
✅ Historique complet des suivis
✅ Codes couleur visuels pour les alertes
✅ Statuts: planifié, en cours, réalisé, reporté, annulé

### Dashboard
✅ Widget d'alertes de maintenance
✅ Affichage des maintenances urgentes
✅ Codes couleur selon l'urgence
✅ Lien direct vers la gestion
✅ Rafraîchissement automatique

## Prochaines étapes

### À faire pour compléter le module
1. **Compétences**
   - Gestion des compétences requises
   - Plan de formation
   - Évaluation des compétences

2. **Communication et sensibilisation**
   - Plan de communication
   - Campagnes de sensibilisation
   - Suivi des actions

3. **Information documentée**
   - Gestion documentaire avancée
   - Contrôle des versions
   - Archives

### Commandes pour déployer

```bash
# Backend
cd backend
php artisan migrate
php artisan db:seed --class=CodificationSeeder

# Frontend
cd frontend
npm install
npm run dev
```

## Permissions recommandées

Créer les permissions suivantes dans Spatie:
- `support.view` - Voir le module Support
- `support.ressources.view` - Voir les ressources
- `support.ressources.manage` - Gérer les ressources
- `support.codification.manage` - Gérer la codification
- `support.equipements.view` - Voir les équipements
- `support.equipements.create` - Créer des équipements
- `support.equipements.edit` - Modifier des équipements
- `support.equipements.delete` - Supprimer des équipements
- `support.maintenances.view` - Voir les maintenances
- `support.maintenances.create` - Créer des maintenances
- `support.maintenances.manage` - Gérer les maintenances
- `support.maintenances.suivre` - Faire le suivi des maintenances

## Notes techniques

### Système d'alertes
Les alertes sont calculées côté backend dans le model `Maintenance`:
- `niveau_alerte` (attribute) - Calcule le niveau d'alerte
- `suivi_active` (attribute) - Détermine si le bouton suivi est actif
- `avecAlertes()` (scope) - Filtre les maintenances avec alertes

### Upload de fichiers
Les preuves de maintenance sont stockées dans `storage/app/public/maintenances/preuves/`

### Codes couleur
- Rouge: Dépassée
- Orange: Aujourd'hui
- Jaune: Veille / 3 jours
- Bleu: 7 jours

## Fichiers créés

### Backend (11 fichiers)
- 4 migrations
- 4 models
- 3 controllers
- 1 seeder

### Frontend (13 fichiers)
- 1 service
- 1 store
- 5 pages
- 5 composants
- 1 module de routes

Total: **24 fichiers créés**
