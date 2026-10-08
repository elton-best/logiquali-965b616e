# Système de Nomenclature Flexible - Documentation Complète

## Vue d'ensemble

Le système de nomenclature flexible permet de configurer dynamiquement la structure des codes de documents selon les besoins de chaque entreprise/site, avec génération automatique, recyclage des numéros et workflow de validation.

---

## Architecture

### Modèle de données (3 tables principales)

#### 1. `document_type_configurations`
Configuration des types de documents avec leur structure de code.

**Colonnes clés:**
- `type_code` : Code du type (POL, PRC, FOR, etc.)
- `type_label` : Libellé du type
- `is_active` : Statut actif/inactif
- `enterprise_id` / `site_id` : Portée de la configuration

#### 2. `code_structure_parts`
Parties composant la structure d'un code (ordre, type, longueur, séparateur).

**Types de parties:**
- `type_code` : Code du type de document
- `process_code` : Code du processus
- `year` : Année (YYYY)
- `month` : Mois (MM)
- `sequence` : Numéro séquentiel avec padding
- `separator` : Séparateur (-, _, /, etc.)
- `custom` : Valeur personnalisée fixe

**Colonnes clés:**
- `part_type` : Type de la partie
- `order` : Ordre dans la structure
- `length` : Longueur (pour séquence)
- `separator` : Caractère séparateur
- `sequence_scope` : Portée de la séquence

#### 3. `code_sequences`
Gestion des séquences avec recyclage automatique.

**Colonnes clés:**
- `current_number` : Numéro actuel
- `available_numbers` : JSON array des numéros libérés
- `scope` : Portée (global, by_type, by_type_process, etc.)

**Mécanisme de recyclage:**
1. Lors de la génération : utilise le plus petit numéro disponible dans `available_numbers`
2. Si aucun disponible : incrémente `current_number`
3. Lors de la libération : ajoute le numéro à `available_numbers`

---

## Backend

### Services

#### `DocumentTypeConfigurationService`
Gestion CRUD des configurations.

**Méthodes principales:**
- `createConfiguration()` : Crée une configuration avec validation
- `updateConfiguration()` : Met à jour une configuration
- `deleteConfiguration()` : Supprime (soft delete)
- `validateStructure()` : Valide la cohérence de la structure
- `previewCode()` : Génère un aperçu du code
- `duplicateConfiguration()` : Duplique une configuration existante

#### `CodeGenerationService`
Génération et gestion des codes.

**Méthodes principales:**
- `generateCode(typeConfigId, context)` : Génère un code complet
- `releaseCode(code, typeConfigId)` : Libère un code pour recyclage
- `reserveCode(code, documentId)` : Réserve un code temporairement
- `activateCode(code, typeConfigId)` : Active un code réservé
- `isCodeAvailable(code)` : Vérifie la disponibilité

**Contexte de génération:**
```php
[
    'site_id' => 1,
    'enterprise_id' => 1,
    'process_id' => 2,
    'year' => 2026,
    'month' => 4,
]
```

#### `DocumentCodeWorkflowService`
Workflow de vérification/activation.

**Méthodes principales:**
- `verifyCode(document, verifierId)` : Vérifie un code
- `activateCode(document, approverId)` : Active un code vérifié
- `releaseCode(document)` : Libère un code rejeté
- `getPendingVerification()` : Liste des codes à vérifier
- `getPendingApproval()` : Liste des codes à approuver

### Controllers

#### `DocumentTypeConfigurationController`
9 endpoints RESTful.

**Routes:**
```
GET    /document-type-configurations
POST   /document-type-configurations
GET    /document-type-configurations/{id}
PUT    /document-type-configurations/{id}
DELETE /document-type-configurations/{id}
POST   /document-type-configurations/preview-code
POST   /document-type-configurations/{id}/duplicate
POST   /document-type-configurations/validate-structure
POST   /document-type-configurations/{id}/toggle-active
```

**Permissions:**
- `configure_nomenclature` : Création/modification
- `view_nomenclature` : Lecture seule

#### `DocumentCodeWorkflowController`
5 endpoints pour le workflow.

**Routes:**
```
GET  /document-code-workflow/pending-verification
GET  /document-code-workflow/pending-approval
POST /document-code-workflow/documents/{document}/verify
POST /document-code-workflow/documents/{document}/activate
POST /document-code-workflow/documents/{document}/release
```

**Permissions:**
- `verify_documents` : Vérification
- `approve_documents` : Approbation
- `configure_nomenclature` : Libération

---

## Frontend

### Pages

#### `ConfigurationList.vue`
Liste des configurations avec actions.

**Fonctionnalités:**
- Filtrage par statut (actif/inactif)
- Aperçu de la structure
- Actions : Activer/Désactiver, Modifier, Dupliquer, Supprimer
- Ouverture du wizard de création/modification

#### `ConfigurationWizard.vue`
Wizard en 3 étapes.

**Étapes:**
1. **Informations** : Code, libellé, description
2. **Structure** : Construction drag & drop
3. **Validation** : Aperçu et activation

#### `StructureBuilder.vue`
Composant drag & drop pour construire la structure.

**Fonctionnalités:**
- Ajout d'éléments depuis la palette
- Réorganisation par glisser-déposer
- Configuration contextuelle (longueur, portée, séparateurs)
- Aperçu en temps réel

#### `CodeVerification.vue`
Page pour les vérificateurs.

**Fonctionnalités:**
- Liste des documents en attente de vérification
- Informations : code, titre, type, auteur
- Action : Vérifier

#### `CodeApproval.vue`
Page pour les approbateurs.

**Fonctionnalités:**
- Liste des documents vérifiés en attente d'approbation
- Informations : code, titre, type, vérificateur
- Actions : Approuver, Rejeter (libère le code)

### Composants

#### `DocumentForm.vue` (modifié)
Formulaire de création avec génération automatique.

**Nouvelles fonctionnalités:**
- Aperçu du code en temps réel
- Mise à jour automatique selon type/processus
- Indication de chargement
- Support du `siteId` en prop

### Composables

#### `useDocumentTypeConfigurations.ts`
Gestion d'état pour les configurations.

**Méthodes:**
- `loadConfigurations(filters)`
- `getConfiguration(id)`
- `createConfiguration(data)`
- `updateConfiguration(id, data)`
- `deleteConfiguration(id)`
- `toggleActive(id)`

#### `useDocumentCodeGeneration.ts`
Génération de codes avec aperçu.

**Méthodes:**
- `generateCodePreview(context)` : Génère un aperçu
- `clearPreview()` : Efface l'aperçu

**Contexte:**
```typescript
{
  siteId?: number;
  type: string;
  processId?: number;
  processus?: string;
}
```

### Services API

#### `documentTypeConfiguration.ts`
Client API TypeScript pour les configurations.

**Interfaces:**
```typescript
interface CodeStructurePart {
  part_type: 'type_code' | 'process_code' | 'year' | 'month' | 'sequence' | 'separator' | 'custom';
  order: number;
  length?: number;
  separator?: string;
  custom_value?: string;
  sequence_scope?: string;
  padding_char?: string;
}

interface DocumentTypeConfiguration {
  id?: number;
  type_code: string;
  type_label: string;
  description?: string;
  code_structure: CodeStructurePart[];
  is_active: boolean;
}
```

#### `documentWorkflow.ts`
Client API pour le workflow.

**Méthodes:**
- `getPendingVerification(siteId?)`
- `getPendingApproval(siteId?)`
- `verifyCode(documentId)`
- `activateCode(documentId)`
- `releaseCode(documentId)`

---

## Workflow complet

### 1. Configuration (Admin)
```
Admin → ConfigurationList → Wizard (3 étapes) → Sauvegarde
```

### 2. Création de document (Utilisateur)
```
Utilisateur → DocumentForm
  ↓ Sélection type/processus
  ↓ Aperçu code automatique
  ↓ Création
Backend → Génération code (réservé)
  ↓ Recherche configuration active
  ↓ Résolution des parties
  ↓ Obtention séquence (recyclage si disponible)
  ↓ Assemblage code
Document créé avec code_status='reserved'
```

### 3. Vérification (Vérificateur)
```
Vérificateur → CodeVerification
  ↓ Sélection document
  ↓ Vérification
Backend → Update verified_at, verified_by
Document passe en attente d'approbation
```

### 4. Approbation (Approbateur)
```
Approbateur → CodeApproval
  ↓ Sélection document
  ↓ Approuver OU Rejeter
Backend → Si approuvé:
  ↓   - code_status='active'
  ↓   - workflow_status='approved'
  ↓ Si rejeté:
  ↓   - code_status='released'
  ↓   - Numéro ajouté à available_numbers
Document finalisé ou code recyclé
```

---

## Exemples de structures

### Structure simple
```
POL-001
```
**Configuration:**
- type_code
- separator: -
- sequence (length: 3, scope: by_type)

### Structure complète
```
PRC/QUA/2026/001
```
**Configuration:**
- type_code
- separator: /
- process_code
- separator: /
- year
- separator: /
- sequence (length: 3, scope: by_type_process_year)

### Structure avec mois
```
FOR-RH-2026-04-0012
```
**Configuration:**
- type_code
- separator: -
- process_code
- separator: -
- year
- separator: -
- month
- separator: -
- sequence (length: 4, scope: by_type_process_year_month)

---

## Permissions requises

### Backend (Spatie Laravel Permission)
```php
'configure_nomenclature'  // Gérer les configurations
'view_nomenclature'       // Voir les configurations
'verify_documents'        // Vérifier les codes
'approve_documents'       // Approuver les codes
'view_documents'          // Voir les documents
'create_documents'        // Créer des documents
```

### Frontend (Routes protégées)
```typescript
'/documents/nomenclature/configurations' → configure_nomenclature
'/documents/workflow/verification'       → verify_documents
'/documents/workflow/approval'           → approve_documents
```

---

## Tests recommandés

### Backend
1. **Génération de codes**
   - Avec différentes structures
   - Avec recyclage de numéros
   - Avec différentes portées de séquence

2. **Workflow**
   - Vérification → Approbation → Activation
   - Vérification → Rejet → Recyclage
   - Permissions correctes

3. **Validation**
   - Structure invalide (sans séquence)
   - Codes dupliqués
   - Configuration inactive

### Frontend
1. **Wizard**
   - Création complète
   - Modification
   - Duplication

2. **Drag & drop**
   - Ajout d'éléments
   - Réorganisation
   - Configuration contextuelle

3. **Aperçu**
   - Mise à jour en temps réel
   - Gestion des erreurs

---

## Migration depuis l'ancien système

Le système est **rétrocompatible** avec l'ancien système de nomenclature :

1. Les anciennes nomenclatures (`document_nomenclatures`) continuent de fonctionner
2. Le service `DocumentNomenclatureService` utilise une **priorité en cascade** :
   - Nouvelles configurations (si disponibles)
   - Anciennes nomenclatures (fallback)
   - Code par défaut (dernier recours)

**Stratégie de migration:**
1. Créer les nouvelles configurations pour les types prioritaires
2. Tester en parallèle
3. Migrer progressivement les types restants
4. Désactiver les anciennes nomenclatures une fois migrées

---

## Maintenance

### Nettoyage des séquences
Les séquences peuvent accumuler des numéros disponibles. Script de nettoyage recommandé :

```php
// Supprimer les numéros disponibles > current_number
CodeSequence::chunk(100, function ($sequences) {
    foreach ($sequences as $sequence) {
        $available = $sequence->available_numbers ?? [];
        $cleaned = array_filter($available, fn($n) => $n < $sequence->current_number);
        $sequence->update(['available_numbers' => array_values($cleaned)]);
    }
});
```

### Monitoring
Surveiller :
- Nombre de codes réservés non vérifiés (> 7 jours)
- Nombre de codes vérifiés non approuvés (> 7 jours)
- Taille des `available_numbers` (> 100 éléments)

---

## Support

Pour toute question ou problème :
1. Consulter cette documentation
2. Vérifier les logs Laravel (`storage/logs/laravel.log`)
3. Vérifier la console navigateur (erreurs frontend)
4. Contacter l'équipe de développement

---

**Version:** 1.0.0  
**Date:** 2026-04-30  
**Auteur:** Équipe Dev LOGIQUALI
