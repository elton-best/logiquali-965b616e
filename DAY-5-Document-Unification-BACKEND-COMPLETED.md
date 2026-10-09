# 🎯 PHASE 5 : UNIFICATION DOCUMENT ↔ DOCUMENTINVENTORY — IMPLÉMENTATION

**Date** : 29 Avril 2026  
**Status** : ✅ BACKEND TERMINÉ | ⏳ FRONTEND EN COURS  
**Durée** : ~1.5 heures

---

## 📋 RÉSUMÉ

Unification des deux modèles `Document` et `DocumentInventory` vers un seul modèle `Document` robuste et maintenable. Cette phase élimine le risque de divergence des données et simplifie la maintenance.

---

## ✅ LIVRABLES BACKEND (4 fichiers)

### 1. Migration Schéma
- ✅ `2026_04_29_122722_add_inventory_fields_to_documents_table.php`
  - Ajout champs : `processus`, `etat`, `periodicite_revision`, `date_revision`, `prochaine_revision`
  - Index pour performance
  - Mapping documenté des champs existants

### 2. Modèle Document
- ✅ Mise à jour `app/Models/Document.php`
  - Ajout des nouveaux champs dans `$fillable`
  - Ajout des casts pour les dates
  - Modèle unifié prêt

### 3. Service de Migration
- ✅ `app/Services/DocumentInventoryMigrationService.php`
  - Méthode `migrateAll()` : Migration complète avec dry-run
  - Méthode `migrateOne()` : Migration d'un document
  - Méthode `verifyMigration()` : Vérification de cohérence
  - Méthode `rollback()` : Annulation de la migration
  - Mapping automatique des statuts et états
  - Gestion des erreurs et logging

### 4. Commande Artisan
- ✅ `app/Console/Commands/MigrateDocumentInventory.php`
  - `php artisan documents:migrate-inventory` : Migration complète
  - `--dry-run` : Simulation sans modification
  - `--verify` : Vérification de cohérence
  - `--rollback` : Annulation de la migration
  - Interface CLI intuitive avec tableaux et couleurs

---

## 🔄 MAPPING DES CHAMPS

### Champs Directs
```
DocumentInventory          →  Document
─────────────────────────────────────────
nom                        →  title
fichier                    →  file_path
created_by                 →  author_id
validated_by               →  approver_id
validated_at               →  approved_at
nomenclature_id            →  nomenclature_template_id
date_creation              →  created_at
prochaine_revision         →  review_due_date
```

### Champs Ajoutés
```
DocumentInventory          →  Document (nouveau)
─────────────────────────────────────────
processus                  →  processus
etat                       →  etat
periodicite_revision       →  periodicite_revision
date_revision              →  date_revision
prochaine_revision         →  prochaine_revision
```

### Mapping des Statuts
```
DocumentInventory.statut   →  Document.status / workflow_status
─────────────────────────────────────────────────────────────
brouillon                  →  draft / draft
en_revision                →  pending_verification / pending_verification
valide                     →  approved / approved
```

### Mapping des États
```
DocumentInventory.etat     →  Document.etat
─────────────────────────────────────────
a_etablir                  →  a_etablir
en_cours                   →  en_cours
termine                    →  termine
```

---

## 🚀 UTILISATION

### 1. Simulation (Dry Run)
```bash
cd backend
php artisan documents:migrate-inventory --dry-run
```

### 2. Migration Réelle
```bash
php artisan documents:migrate-inventory
```

### 3. Vérification
```bash
php artisan documents:migrate-inventory --verify
```

### 4. Rollback (si nécessaire)
```bash
php artisan documents:migrate-inventory --rollback
```

---

## 📊 EXEMPLE DE SORTIE

```
🚀 Début de la migration...

✅ Migration terminée !

┌─────────────────────┬────────┐
│ Métrique            │ Valeur │
├─────────────────────┼────────┤
│ Total documents     │ 150    │
│ Migrés avec succès  │ 148    │
│ Ignorés             │ 0      │
│ Erreurs             │ 2      │
└─────────────────────┴────────┘

❌ Erreurs détectées :
  - Document #42 (PRC-01-042): Nomenclature introuvable
  - Document #87 (FOR-03-015): Site_id invalide

💡 Conseil : Exécutez "php artisan documents:migrate-inventory --verify" pour vérifier la cohérence.
```

---

## 🔒 SÉCURITÉ & ROBUSTESSE

### Implémenté
- ✅ Transaction DB pour chaque document
- ✅ Vérification des doublons (code + site_id)
- ✅ Logging détaillé de chaque opération
- ✅ Gestion des erreurs avec détails
- ✅ Métadonnées de traçabilité (`migrated_from_inventory`, `inventory_id`, `migration_date`)
- ✅ Mode dry-run pour simulation
- ✅ Rollback complet

### Garanties
- ✅ Aucune perte de données
- ✅ Idempotence (peut être exécuté plusieurs fois)
- ✅ Traçabilité complète
- ✅ Réversibilité (rollback)

---

## 📈 PROCHAINES ÉTAPES

### Phase 5.2 : Mise à Jour des Contrôleurs (2-3 jours)
- ⏳ Adapter `DocumentController` pour utiliser le modèle unifié
- ⏳ Déprécier `DocumentInventoryController`
- ⏳ Créer des alias pour compatibilité ascendante

### Phase 5.3 : Mise à Jour Frontend (1-2 jours)
- ⏳ Adapter les composables `useDocuments()`
- ⏳ Mettre à jour les types TypeScript
- ⏳ Adapter les pages documents

### Phase 5.4 : Tests & Validation (1 jour)
- ⏳ Tests unitaires du service de migration
- ⏳ Tests d'intégration
- ⏳ Tests E2E Playwright

### Phase 5.5 : Dépréciation DocumentInventory (1 jour)
- ⏳ Marquer le modèle comme `@deprecated`
- ⏳ Ajouter des warnings dans les logs
- ⏳ Documentation de migration

---

## 🎯 BÉNÉFICES DE L'UNIFICATION

### Technique
✅ **Un seul modèle** = une seule source de vérité  
✅ **Pas de synchronisation** = pas de risque de divergence  
✅ **Workflow unifié** = vérification/approbation natifs  
✅ **Maintenance simplifiée** = moins de code à maintenir  
✅ **Performance** = pas de double écriture  

### Business
✅ **Cohérence des données** garantie  
✅ **Évolutivité** facilitée  
✅ **Audit trail** complet  
✅ **Intégration** simplifiée avec autres modules  

---

## ⚠️ NOTES IMPORTANTES

### Avant la Migration
1. **Backup de la base de données** obligatoire
2. **Tester en dry-run** d'abord
3. **Vérifier les dépendances** (nomenclatures, sites, users)

### Pendant la Migration
1. **Pas d'accès concurrent** aux documents
2. **Monitoring des logs** recommandé
3. **Vérification immédiate** après migration

### Après la Migration
1. **Vérifier la cohérence** avec `--verify`
2. **Tester les fonctionnalités** critiques
3. **Garder DocumentInventory** en lecture seule pendant 1 sprint

---

## 📝 MÉTADONNÉES DE TRAÇABILITÉ

Chaque document migré contient :
```json
{
  "metadata": {
    "migrated_from_inventory": true,
    "inventory_id": 42,
    "migration_date": "2026-04-29T14:30:00.000000Z"
  }
}
```

---

## 🧪 TESTS À IMPLÉMENTER

### Tests Unitaires
- ⏳ `DocumentInventoryMigrationServiceTest`
  - Test `migrateOne()` avec différents cas
  - Test mapping des statuts
  - Test gestion des erreurs
  - Test rollback

### Tests d'Intégration
- ⏳ Migration complète sur base de test
- ⏳ Vérification de cohérence
- ⏳ Test rollback

### Tests E2E
- ⏳ Workflow complet après migration
- ⏳ Affichage des documents migrés
- ⏳ Édition/suppression

---

## ✅ VALIDATION

### Backend
```bash
cd backend
php artisan migrate
php artisan documents:migrate-inventory --dry-run
php artisan documents:migrate-inventory --verify
```

### Logs
```bash
tail -f storage/logs/laravel.log | grep "Migration"
```

---

**Prochaine session** : Phase 5.2 - Mise à jour des contrôleurs et services

**Statut global** : 🟢 Phase 5.1 (Backend) terminée avec succès
