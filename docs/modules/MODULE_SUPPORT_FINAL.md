# Module Support - Implémentation complète ✅

## Résumé

Le module Support a été implémenté avec succès dans le cadre du **Point 7 - Support (ISO 9001)**.

### Structure
- **Équipements** : Inventaire complet avec codification automatique
- **Maintenance** : Plan de maintenance avec système d'alertes
- **Formation** : Page placeholder (à développer)
- **Communication** : Page placeholder (à développer)
- **Inventaire documentaire** : Page placeholder (à développer)

## URLs d'accès

Les pages sont accessibles via :
- **Équipements** : `http://localhost:3000/company/iso/support/equipment`
- **Maintenance** : `http://localhost:3000/company/iso/support/maintenance`
- **Formation** : `http://localhost:3000/company/iso/support/training`
- **Communication** : `http://localhost:3000/company/iso/support/communication`
- **Inventaire documentaire** : `http://localhost:3000/company/iso/support/document-inventory`

## Menu Sidebar

Le module est accessible dans la sidebar sous **"7 - Support"** avec les sous-menus :
- Formation (icône: school)
- Équipements (icône: toolbox) ✅ IMPLÉMENTÉ
- Inventaire documentaire (icône: folder-multiple)
- Communication (icône: bullhorn)
- Maintenance (icône: wrench) ✅ IMPLÉMENTÉ

## Backend ✅

### Migrations (4 tables)
✅ `codification_elements` - Catégories et localisations
✅ `equipements` - Inventaire des équipements
✅ `maintenances` - Plan de maintenance
✅ `maintenance_suivis` - Historique des suivis

### Models (4)
✅ `CodificationElement.php`
✅ `Equipement.php` - Avec génération automatique de code
✅ `Maintenance.php` - Avec système d'alertes
✅ `MaintenanceSuivi.php`

### Controllers (3)
✅ `CodificationController.php`
✅ `EquipementController.php`
✅ `MaintenanceController.php`

### Routes API
```
GET    /api/v1/codifications
POST   /api/v1/codifications
PUT    /api/v1/codifications/{id}
DELETE /api/v1/codifications/{id}

GET    /api/v1/equipements
POST   /api/v1/equipements
PUT    /api/v1/equipements/{id}
DELETE /api/v1/equipements/{id}
GET    /api/v1/equipements/prochain-indice

GET    /api/v1/maintenances
POST   /api/v1/maintenances
PUT    /api/v1/maintenances/{id}
DELETE /api/v1/maintenances/{id}
POST   /api/v1/maintenances/{id}/suivre
GET    /api/v1/maintenances/alertes
```

### Seeder
✅ `CodificationSeeder.php` - Données initiales
- 4 catégories : MOB, INF, MEN, PRO
- 11 localisations : MAG, SFO, CEO, COO, CON, ADM, DEC, SEC, COU, GUR, CUI

## Frontend ✅

### Services & Store
✅ `supportService.ts` - Service API
✅ `supportStore.ts` - Store Pinia

### Pages
✅ `/pages/iso/support/Equipment.vue` - Inventaire des équipements
✅ `/pages/iso/support/Maintenance.vue` - Plan de maintenance
⏳ `/pages/iso/support/Training.vue` - Placeholder
⏳ `/pages/iso/support/Communication.vue` - Placeholder
⏳ `/pages/iso/support/DocumentInventory.vue` - Placeholder

### Composants (6)
✅ `InventaireEquipements.vue` - Composant principal inventaire
✅ `CodificationDialog.vue` - Gestion de la codification
✅ `PlanMaintenance.vue` - Composant principal maintenance
✅ `AddMaintenanceDialog.vue` - Ajout de maintenance
✅ `SuiviMaintenanceDialog.vue` - Suivi avec upload
✅ `MaintenanceAlertes.vue` - Widget dashboard

## Fonctionnalités implémentées

### 1. Codification automatique ✅
- Format : **BEG/Catégorie/NomCommun/Localisation/Indice/Année**
- Exemple : **BEG/INF/ECR/CEO/001/2025**
- Génération automatique du code complet
- Calcul automatique de l'indice (001, 002, 003...)
- Gestion des catégories et localisations via dialog

### 2. Inventaire des équipements ✅
- Formulaire complet d'ajout
- Sélection catégorie et localisation depuis les éléments de codification
- États : Très bon (neuf), Bon, Mauvais
- Informations : marque, modèle, numéro de série, valeur d'acquisition
- Option maintenance avec fréquence en jours
- Liste complète avec affichage du code complet
- Suppression d'équipements
- Affichage des règles de codification

### 3. Plan de maintenance ✅
- Planification de maintenances (préventive, corrective, étalonnage)
- Système d'alertes automatiques :
  - 🔴 **DÉPASSÉE** : Date dépassée
  - 🟠 **AUJOURD'HUI** : Maintenance prévue aujourd'hui
  - 🟡 **DEMAIN** : Maintenance prévue demain
  - 🟡 **3 JOURS** : Dans 3 jours
  - 🔵 **7 JOURS** : Dans 7 jours
- Bouton "Suivi" :
  - ⚪ Grisé AVANT la date de maintenance
  - 🟢 Actif APRÈS la date de maintenance
- Codes couleur visuels pour les lignes du tableau
- Statuts : planifié, en cours, réalisé, reporté, annulé

### 4. Suivi de maintenance ✅
- Deux options :
  - ✅ **Maintenance effectuée** :
    - Date d'action
    - Commentaire
    - Upload de preuve (tous types de fichiers)
  - 📅 **Maintenance reportée** :
    - Date d'action
    - Nouvelle date prévue
    - Commentaire
- Historique complet des suivis
- Affichage des informations de l'équipement
- Upload de fichiers (PDF, images, Word, etc.)

### 5. Widget Dashboard ✅
- Affichage des alertes de maintenance
- Codes couleur selon l'urgence
- Lien direct vers la page de gestion
- Rafraîchissement automatique toutes les 5 minutes
- Affichage des maintenances à venir (7 jours)

## État des migrations et données

✅ Migrations exécutées
✅ Données de codification chargées
✅ Tables créées et opérationnelles

## Prochaines étapes

### À développer
1. **Formation** (Training.vue)
   - Plan de formation annuel
   - Gestion des compétences
   - Suivi des sessions

2. **Communication** (Communication.vue)
   - Plan de communication
   - Campagnes de sensibilisation

3. **Inventaire documentaire** (DocumentInventory.vue)
   - Gestion documentaire avancée
   - Contrôle des versions

### Améliorations possibles
- Notifications push pour les alertes de maintenance
- Export Excel de l'inventaire
- Statistiques et graphiques
- Filtres avancés
- Recherche globale
- Import Excel pour l'inventaire

## Permissions recommandées

À créer dans Spatie :
```
support.view
support.equipements.view
support.equipements.create
support.equipements.edit
support.equipements.delete
support.codification.manage
support.maintenances.view
support.maintenances.create
support.maintenances.manage
support.maintenances.suivre
```

## Notes techniques

### Système d'alertes
Les alertes sont calculées côté backend dans le model `Maintenance` :
- `niveau_alerte` (attribute) - Calcule le niveau d'alerte
- `suivi_active` (attribute) - Détermine si le bouton suivi est actif
- `avecAlertes()` (scope) - Filtre les maintenances avec alertes

### Upload de fichiers
Les preuves de maintenance sont stockées dans :
`storage/app/public/maintenances/preuves/`

### Codes couleur
- 🔴 Rouge : Dépassée
- 🟠 Orange : Aujourd'hui
- 🟡 Jaune : Veille / 3 jours
- 🔵 Bleu : 7 jours

## Fichiers créés

### Backend (11 fichiers)
- 4 migrations
- 4 models
- 3 controllers
- 1 seeder

### Frontend (8 fichiers)
- 1 service
- 1 store
- 2 pages (Equipment.vue, Maintenance.vue modifiées)
- 6 composants

**Total : 19 fichiers créés/modifiés**

## Test de l'implémentation

1. Accéder à `http://localhost:3000/company/iso/support/equipment`
2. Cliquer sur "Gérer la codification" pour ajouter des catégories/localisations
3. Ajouter un équipement
4. Accéder à `http://localhost:3000/company/iso/support/maintenance`
5. Planifier une maintenance
6. Attendre que la date soit passée pour activer le bouton "Suivi"
7. Faire le suivi (réalisé ou reporté)

## Support

Pour toute question ou problème :
- Vérifier que les migrations sont exécutées
- Vérifier que le seeder a été exécuté
- Vérifier les permissions utilisateur
- Consulter les logs Laravel : `storage/logs/laravel.log`
- Consulter la console navigateur pour les erreurs frontend
