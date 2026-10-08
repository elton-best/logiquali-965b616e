# Documentation - Système de Nomenclature Flexible

## 📚 Index de la Documentation

### 📊 Vue d'ensemble
- **[Résumé du Projet](./project-summary.md)** - Vue globale, progression, métriques
- **[Architecture Système](./nomenclature-system-documentation.md)** - Documentation technique complète

---

## 🎯 Documentation par Phase

### Phase 1: Architecture & Database ✅
**Statut**: Terminée (100%)

**Fichiers**:
- Migrations: 4 fichiers dans `/backend/database/migrations/`
- Models: 4 fichiers dans `/backend/app/Models/`

**Concepts clés**:
- Tables: `document_type_configurations`, `code_structure_parts`, `code_sequences`
- Scopes de séquence: 6 types (global, by_type, by_type_process, etc.)
- Relations Eloquent

---

### Phase 2: Configuration Nomenclature ✅
**Statut**: Terminée (100%)

**Fichiers backend**:
- `DocumentTypeConfigurationService.php`
- `CodeGenerationService.php`
- `DocumentTypeConfigurationController.php`

**Fichiers frontend**:
- `ConfigurationList.vue`
- `ConfigurationWizard.vue`
- `StructureBuilder.vue`

**Fonctionnalités**:
- CRUD configurations
- Drag-and-drop structure builder
- 7 types d'éléments (type_code, process_code, year, month, sequence, separator, custom)
- Prévisualisation en temps réel

---

### Phase 3: Génération Automatique ✅
**Statut**: Terminée (100%)

**Fichiers modifiés**:
- `DocumentForm.vue`
- `useDocumentCodeGeneration.ts`

**Fonctionnalités**:
- Génération automatique lors de la création
- Prévisualisation en temps réel
- 3 niveaux de priorité (moderne, legacy, fallback)
- Recyclage automatique des codes

---

### Phase 4: Workflow Vérification/Approbation ✅
**Statut**: Terminée (100%)  
**Documentation**: [Phase 4 Completion](./phase4-workflow-completion.md)

**Fichiers backend**:
- `DocumentCodeWorkflowService.php`
- `DocumentCodeWorkflowController.php`
- `DocumentCodeWorkflowNotification.php`

**Fichiers frontend**:
- `WorkflowDashboard.vue`
- `CodeVerification.vue`
- `CodeApproval.vue`

**Fonctionnalités**:
- Workflow vérification → approbation
- Notifications automatiques (email + DB)
- Dashboard avec statistiques
- 5 types de notifications

**Permissions**:
- `verify_documents`
- `approve_documents`

---

### Phase 5: Import Documents Existants ✅
**Statut**: Terminée (100%)  
**Documentation**: [Phase 5 Completion](./phase5-import-completion.md)

**Fichiers backend**:
- `DocumentImportService.php`
- `DocumentImportController.php`
- Migration: `2026_04_30_000005_add_import_documents_permission.php`

**Fichiers frontend**:
- `DocumentImportWizard.vue`
- `DocumentImport.vue`
- `documentImport.ts`

**Fonctionnalités**:
- Analyse intelligente des documents
- Suggestions automatiques de mapping
- Préservation des codes existants
- Synchronisation des séquences
- Wizard 4 étapes

**Permissions**:
- `import_documents`

---

### Phase 6: Multi-vues & Visibilité ✅
**Statut**: Terminée (100%)  
**Documentation**: [Phase 6 Completion](./phase6-visibility-completion.md)

**Fichiers backend**:
- `DocumentTypeConfigurationVisibilityService.php`
- `DocumentTypeConfigurationController.php` (mis à jour)

**Fichiers frontend**:
- `AdvancedFilters.vue`
- `ShareConfigurationModal.vue`
- `ConfigurationList.vue` (mis à jour)
- `documentTypeConfiguration.ts` (API client étendu)

**Fonctionnalités**:
- Visibilité hiérarchique (super-admin, entreprise, site)
- Filtres avancés (10 critères)
- Partage avec sites/entreprises
- Duplication automatique lors du partage
- Statistiques de visibilité
- Permissions granulaires (canView, canEdit)

**Endpoints API**:
- `GET /visibility-stats`
- `GET /advanced-filters`
- `POST /{id}/share-with-sites`
- `POST /{id}/share-with-enterprises`
- `GET /{id}/available-sites-for-sharing`
- `GET /{id}/available-enterprises-for-sharing`

---

### Phase 7: Tests & Validation ⏳
**Statut**: Non démarrée (0%)

**Objectifs**:
- Tests unitaires (PHPUnit)
- Tests d'intégration (API)
- Tests E2E (Playwright)
- Validation complète

---

## 📖 Guides Utilisateur

### Pour Administrateurs
- **[Guide de Démarrage Rapide](./nomenclature-quick-start-guide.md)** - Configuration initiale
- **[Guide d'Import](./import-user-guide.md)** - Import de documents existants

### Pour Utilisateurs
- **[Guide de Démarrage Rapide](./nomenclature-quick-start-guide.md)** - Section "Utilisateurs"
- Création de documents avec codes automatiques

### Pour Vérificateurs
- **[Guide de Démarrage Rapide](./nomenclature-quick-start-guide.md)** - Section "Vérificateurs"
- Workflow de vérification

### Pour Approbateurs
- **[Guide de Démarrage Rapide](./nomenclature-quick-start-guide.md)** - Section "Approbateurs"
- Workflow d'approbation

---

## 🔧 Documentation Technique

### Architecture
- **[Documentation Système](./nomenclature-system-documentation.md)** - Architecture complète
- **[Résumé Projet](./project-summary.md)** - Vue d'ensemble technique

### API Endpoints

#### Configuration Nomenclature
```
GET    /api/v1/document-type-configurations
POST   /api/v1/document-type-configurations
GET    /api/v1/document-type-configurations/{id}
PUT    /api/v1/document-type-configurations/{id}
DELETE /api/v1/document-type-configurations/{id}
POST   /api/v1/document-type-configurations/preview-code
POST   /api/v1/document-type-configurations/{id}/duplicate
POST   /api/v1/document-type-configurations/validate-structure
POST   /api/v1/document-type-configurations/{id}/toggle-active
```

#### Workflow
```
GET  /api/v1/document-code-workflow/stats
GET  /api/v1/document-code-workflow/pending-verification
GET  /api/v1/document-code-workflow/pending-approval
POST /api/v1/document-code-workflow/documents/{document}/verify
POST /api/v1/document-code-workflow/documents/{document}/activate
POST /api/v1/document-code-workflow/documents/{document}/release
```

#### Import
```
POST /api/v1/document-import/analyze
POST /api/v1/document-import/preview
POST /api/v1/document-import/execute
```

### Modèles de Données

#### DocumentTypeConfiguration
```php
- id: integer
- enterprise_id: integer (nullable)
- site_id: integer (nullable)
- document_type_id: integer (nullable)
- type_code: string
- type_label: string
- is_active: boolean
- created_at: timestamp
- updated_at: timestamp
```

#### CodeStructurePart
```php
- id: integer
- document_type_configuration_id: integer
- part_type: enum (type_code, process_code, year, month, sequence, separator, custom)
- order: integer
- length: integer (nullable)
- separator: string (nullable)
- custom_value: string (nullable)
- sequence_scope: enum (nullable)
- created_at: timestamp
- updated_at: timestamp
```

#### CodeSequence
```php
- id: integer
- document_type_configuration_id: integer
- document_type_id: integer (nullable)
- process_id: integer (nullable)
- year: integer (nullable)
- month: integer (nullable)
- current_number: integer
- available_numbers: json
- created_at: timestamp
- updated_at: timestamp
```

---

## 🔐 Permissions

| Permission | Description | Phases |
|------------|-------------|--------|
| `configure_nomenclature` | Configurer les nomenclatures | 2 |
| `view_nomenclature` | Voir les nomenclatures | 2 |
| `view_documents` | Voir les documents | 2 |
| `create_documents` | Créer des documents | 2 |
| `verify_documents` | Vérifier les codes documents | 4 |
| `approve_documents` | Approuver les codes documents | 4 |
| `import_documents` | Importer des documents existants | 5 |

---

## 🚀 Démarrage Rapide

### Installation

1. **Migrations**:
```bash
php artisan migrate
```

2. **Seeders** (optionnel):
```bash
php artisan db:seed --class=DocumentTypeConfigurationSeeder
```

3. **Permissions**:
```bash
php artisan permission:cache-reset
```

### Configuration Initiale

1. Accédez à **Documents > Nomenclatures**
2. Créez votre première configuration
3. Définissez la structure du code
4. Activez la configuration
5. Créez un document pour tester

### Import de Documents Existants

1. Accédez à **Documents > Import**
2. Analysez les documents
3. Configurez les mappings
4. Prévisualisez
5. Lancez l'import

---

## 📊 Métriques

### Code
- **Backend**: ~4,500 lignes (5 services, 3 controllers, 4 models)
- **Frontend**: ~3,800 lignes (5 pages, 6 components)
- **Documentation**: ~70 pages

### Fonctionnalités
- **Endpoints API**: 24
- **Pages frontend**: 5
- **Components**: 6
- **Permissions**: 7
- **Notifications**: 5 types

---

## 🐛 Dépannage

### Problèmes Courants

**Erreur: "Configuration not found"**
- Vérifiez que la configuration est active
- Vérifiez les permissions

**Erreur: "Sequence not found"**
- La séquence est créée automatiquement
- Vérifiez les logs Laravel

**Import échoue**
- Vérifiez les mappings
- Consultez les erreurs détaillées
- Vérifiez les permissions

### Logs

**Backend**:
```bash
tail -f storage/logs/laravel.log
```

**Frontend**:
```javascript
console.log() // Dans la console navigateur
```

---

## 📞 Support

### Documentation
- Guides utilisateur: `/docs/*-user-guide.md`
- Documentation technique: `/docs/nomenclature-system-documentation.md`
- Phases: `/docs/phase*-completion.md`

### Code
- Backend: `/backend/app/Services/`
- Frontend: `/frontend/src/`
- API: `/backend/routes/api.php`

---

## 🗺️ Roadmap

- [x] Phase 1: Architecture & Database (100%)
- [x] Phase 2: Configuration Nomenclature (100%)
- [x] Phase 3: Génération Automatique (100%)
- [x] Phase 4: Workflow Vérification/Approbation (100%)
- [x] Phase 5: Import Documents Existants (100%)
- [x] Phase 6: Multi-vues & Visibilité (100%)
- [ ] Phase 7: Tests & Validation (0%)

**Progression globale**: 86% (6/7 phases)

---

**Dernière mise à jour**: 2024  
**Version**: 1.6.0
