# Phase 4 RÉVISÉE : Amélioration Module Information Documentée

**Date:** 2026-04-29  
**Status:** 📋 PLAN D'ACTION  
**Effort estimé:** 8-10 jours  
**Décision:** PAS de fusion des modèles (Document + DocumentInventory restent séparés)

---

## ✅ Ce Qui Existe Déjà

### 1. Nomenclature Flexible ✅ EXISTE
**Localisation:** Modale "Nomenclatures" dans `/documents/index.vue`  
**Composant:** `NomenclatureManager.vue`

**Fonctionnalités actuelles:**
- ✅ Configuration par processus
- ✅ Définir code processus (2-12 caractères)
- ✅ Définir format avec tokens (TYPE, PROCESSUS, NUMERO, YEAR, MONTH)
- ✅ Ordre des parties configurable (drag & drop)
- ✅ Séparateurs personnalisables (/, -)
- ✅ Prévisualisation en temps réel
- ✅ Nomenclatures globales (sans processus)

**Ce qui manque:**
- ❌ Pas de configuration du nombre de caractères par partie
- ❌ Pas de validation stricte du format
- ❌ Pas de gestion des types de documents personnalisés par nomenclature

### 2. Workflow Vérification/Approbation ✅ EXISTE
**Pages:**
- `/documents/verification.vue` - Liste documents en attente de vérification
- `/documents/approbation.vue` - Liste documents en attente d'approbation

**Fonctionnalités actuelles:**
- ✅ Liste des documents en attente
- ✅ Actions Vérifier/Approuver/Rejeter
- ✅ Modale de rejet avec commentaire obligatoire
- ✅ Affichage auteur/vérificateur/approbateur
- ✅ Affichage processus et source

**Ce qui manque:**
- ❌ Pas de notifications temps réel
- ❌ Pas de notification aux autres vérificateurs quand l'un approuve
- ❌ Pas de gestion du code recycling sur rejet
- ❌ Pas de question "libérer le code ?" au soumissionnaire

### 3. Import de Documents ✅ EXISTE PARTIELLEMENT
**Composant:** `DocumentUploadFlow.vue`

**Ce qui manque:**
- ❌ Pas de reconstitution du code document
- ❌ Pas de validation code généré vs code sur document
- ❌ Pas de question "nécessite vérification ?"
- ❌ Workflow conditionnel pas implémenté

---

## 🎯 Plan d'Action Détaillé

### Semaine 1 : Backend Core (4 jours)

#### Jour 1 : Code Recycling System
**Objectif:** Gérer les codes vacants et leur réattribution

**Backend:**
```php
// Nouvelle table: document_code_pool
Schema::create('document_code_pool', function (Blueprint $table) {
    $table->id();
    $table->foreignId('site_id')->constrained();
    $table->string('code')->unique();
    $table->string('type'); // Type de document
    $table->enum('status', ['available', 'reserved', 'used'])->default('available');
    $table->timestamp('released_at')->nullable();
    $table->foreignId('released_by')->nullable()->constrained('users');
    $table->text('release_reason')->nullable();
    $table->timestamps();
});

// Service: CodePoolService
class CodePoolService
{
    public function releaseCode(string $code, int $userId, string $reason): void
    {
        DocumentCodePool::create([
            'site_id' => auth()->user()->site_id,
            'code' => $code,
            'type' => $this->extractTypeFromCode($code),
            'status' => 'available',
            'released_at' => now(),
            'released_by' => $userId,
            'release_reason' => $reason,
        ]);
    }
    
    public function getNextAvailableCode(int $siteId, string $type): ?string
    {
        return DocumentCodePool::where('site_id', $siteId)
            ->where('type', $type)
            ->where('status', 'available')
            ->oldest('released_at')
            ->value('code');
    }
}
```

**API Endpoints:**
- `POST /api/v1/documents/{id}/release-code` - Libérer un code
- `GET /api/v1/documents/available-codes?type={type}` - Codes disponibles

---

#### Jour 2 : Workflow Conditionnel & Questions Pré-Import
**Objectif:** Wizard d'import intelligent

**Backend:**
```php
// DocumentWorkflowController - Nouvelle méthode
public function confirmCodeAndWorkflow(Request $request, $id)
{
    $request->validate([
        'confirmed_code' => 'required|string',
        'needs_verification' => 'required|boolean',
        'code_matches_document' => 'required|boolean',
    ]);
    
    $document = Document::findOrFail($id);
    
    // Vérifier que le code correspond
    if (!$request->boolean('code_matches_document')) {
        return response()->json([
            'message' => 'Veuillez modifier le code sur le document physique avant de continuer.',
            'expected_code' => $document->code,
        ], 422);
    }
    
    // Workflow conditionnel
    if ($request->boolean('needs_verification')) {
        $document->update(['workflow_status' => 'pending_verification']);
        $this->notifyVerifiers($document);
    } else {
        $document->update(['workflow_status' => 'pending_approval']);
        $this->notifyApprovers($document);
    }
    
    return response()->json(['message' => 'Document soumis avec succès']);
}
```

**Frontend:**
```vue
<!-- DocumentImportWizard.vue -->
<v-stepper>
  <v-stepper-header>
    <v-stepper-item value="1">Upload</v-stepper-item>
    <v-stepper-item value="2">Code</v-stepper-item>
    <v-stepper-item value="3">Workflow</v-stepper-item>
  </v-stepper-header>
  
  <v-stepper-window>
    <!-- Étape 1: Upload fichier -->
    <!-- Étape 2: Validation code -->
    <v-stepper-window-item value="2">
      <v-alert type="info">
        Code généré: <strong>{{ generatedCode }}</strong>
      </v-alert>
      <v-checkbox
        v-model="codeMatchesDocument"
        label="Le code sur le document correspond au code généré"
      />
      <v-alert v-if="!codeMatchesDocument" type="warning">
        Veuillez modifier le code sur le document physique
      </v-alert>
    </v-stepper-window-item>
    
    <!-- Étape 3: Workflow -->
    <v-stepper-window-item value="3">
      <v-radio-group v-model="needsVerification">
        <v-radio :value="true" label="Nécessite vérification puis approbation" />
        <v-radio :value="false" label="Approbation directe (sans vérification)" />
      </v-radio-group>
    </v-stepper-window-item>
  </v-stepper-window>
</v-stepper>
```

---

#### Jour 3 : Notifications Enrichies
**Objectif:** Notifications temps réel + peer validators

**Backend:**
```php
// DocumentWorkflowController - Amélioration
private function notifyPeerValidators(Document $document, User $actor, string $action)
{
    $permission = $action === 'verify' ? 'verify_documents' : 'approve_documents';
    
    $peers = User::where('site_id', $document->site_id)
        ->where('id', '!=', $actor->id)
        ->permission($permission)
        ->get();
    
    foreach ($peers as $peer) {
        $peer->notify(new DocumentWorkflowCompletedNotification(
            document: $document,
            completedBy: $actor,
            action: $action
        ));
    }
}

// Notification
class DocumentWorkflowCompletedNotification extends Notification
{
    public function toArray($notifiable)
    {
        return [
            'type' => 'document_workflow_completed',
            'document_id' => $this->document->id,
            'document_code' => $this->document->code,
            'document_title' => $this->document->title,
            'completed_by' => $this->completedBy->name,
            'action' => $this->action, // 'verify' ou 'approve'
            'message' => "{$this->completedBy->name} a {$this->getActionLabel()} le document {$this->document->code}",
        ];
    }
}
```

**Frontend:**
```typescript
// composables/useNotifications.ts
export function useNotifications() {
  const notifications = ref<Notification[]>([])
  
  // WebSocket ou polling
  const connectToNotifications = () => {
    // Echo.private(`user.${userId}`)
    //   .notification((notification) => {
    //     notifications.value.unshift(notification)
    //     showToast(notification.message)
    //   })
  }
  
  return { notifications, connectToNotifications }
}
```

---

#### Jour 4 : Sync Bidirectionnelle Modules ↔ Documents
**Objectif:** Documents créés dans modules apparaissent dans Info Documentée

**Backend:**
```php
// Service: DocumentSyncService
class DocumentSyncService
{
    public function syncFromModule(
        string $moduleType,
        int $moduleId,
        array $documentData
    ): Document {
        // Créer/mettre à jour dans table documents
        $document = Document::updateOrCreate(
            [
                'module_type' => $moduleType,
                'module_id' => $moduleId,
            ],
            [
                'site_id' => $documentData['site_id'],
                'code' => $documentData['code'],
                'title' => $documentData['title'],
                'file_path' => $documentData['file_path'],
                'source_module' => $documentData['source_module'],
                'source_submodule' => $documentData['source_submodule'],
                // ...
            ]
        );
        
        // Créer aussi dans document_inventory pour compatibilité
        $this->syncToInventory($document);
        
        return $document;
    }
    
    private function syncToInventory(Document $document): void
    {
        DocumentInventory::updateOrCreate(
            ['code' => $document->code],
            [
                'site_id' => $document->site_id,
                'nom' => $document->title,
                'type' => $document->type,
                'fichier' => $document->file_path,
                // Mapping des champs
            ]
        );
    }
}

// Event Listener
class DocumentCreatedListener
{
    public function handle(DocumentCreated $event)
    {
        app(DocumentSyncService::class)->syncFromModule(
            $event->moduleType,
            $event->moduleId,
            $event->documentData
        );
    }
}
```

---

### Semaine 2 : Frontend & UX (4 jours)

#### Jour 5 : Wizard Import Intelligent
**Composant:** `DocumentImportWizard.vue`

**Features:**
- Étape 1: Upload fichier
- Étape 2: Sélection processus + type → génération code
- Étape 3: Validation code (checkbox "correspond au document")
- Étape 4: Question "nécessite vérification ?"
- Étape 5: Confirmation et soumission

---

#### Jour 6 : Amélioration Pages Vérification/Approbation
**Fichiers:** `verification.vue`, `approbation.vue`

**Améliorations:**
- Afficher notifications en temps réel
- Badge compteur documents en attente
- Filtres avancés (processus, type, date)
- Vue détaillée document avant action
- Historique des actions

---

#### Jour 7 : Modale Rejet avec Code Recycling
**Nouveau composant:** `DocumentRejectionModal.vue`

```vue
<v-dialog v-model="show" max-width="600">
  <v-card>
    <v-card-title>Rejeter le document</v-card-title>
    <v-card-text>
      <v-textarea
        v-model="rejectionReason"
        label="Motif du rejet *"
        required
      />
      
      <v-divider class="my-4" />
      
      <v-alert type="info">
        Le soumissionnaire sera notifié et devra décider s'il souhaite :
        <ul>
          <li>Libérer le code (disponible pour un autre document)</li>
          <li>Conserver le code (pour une nouvelle soumission)</li>
        </ul>
      </v-alert>
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn @click="show = false">Annuler</v-btn>
      <v-btn color="error" @click="confirmReject">Rejeter</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>
```

---

#### Jour 8 : Page Décision Soumissionnaire
**Nouveau:** `/documents/rejection-decision/[id].vue`

```vue
<template>
  <v-card>
    <v-card-title>Votre document a été rejeté</v-card-title>
    <v-card-text>
      <v-alert type="error">
        <strong>Motif:</strong> {{ document.rejection_reason }}
      </v-alert>
      
      <v-alert type="info" class="mt-4">
        <strong>Code du document:</strong> {{ document.code }}
      </v-alert>
      
      <v-radio-group v-model="decision">
        <v-radio value="release">
          <template #label>
            <div>
              <strong>Libérer le code</strong>
              <div class="text-caption">
                Le code sera disponible pour un autre document du même type
              </div>
            </div>
          </template>
        </v-radio>
        <v-radio value="keep">
          <template #label>
            <div>
              <strong>Conserver le code</strong>
              <div class="text-caption">
                Je vais corriger et soumettre à nouveau avec ce code
              </div>
            </div>
          </template>
        </v-radio>
      </v-radio-group>
      
      <v-textarea
        v-model="comment"
        label="Commentaire (optionnel)"
        rows="2"
      />
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn color="primary" @click="submitDecision">Confirmer</v-btn>
    </v-card-actions>
  </v-card>
</template>
```

---

### Semaine 3 : Tests & Polish (2 jours optionnels)

#### Jour 9-10 : Tests & Documentation
- Tests E2E workflow complet
- Tests code recycling
- Tests notifications
- Documentation utilisateur
- Vidéos tutoriels

---

## 📋 Checklist de Livraison

### Backend
- [ ] Migration `document_code_pool`
- [ ] Service `CodePoolService`
- [ ] API `release-code` + `available-codes`
- [ ] Méthode `confirmCodeAndWorkflow`
- [ ] Notifications enrichies
- [ ] Service `DocumentSyncService`
- [ ] Event listeners sync modules

### Frontend
- [ ] Composant `DocumentImportWizard`
- [ ] Amélioration `verification.vue`
- [ ] Amélioration `approbation.vue`
- [ ] Composant `DocumentRejectionModal`
- [ ] Page `rejection-decision/[id].vue`
- [ ] Composable `useNotifications`
- [ ] Badge compteur sidebar

### Tests
- [ ] Tests unitaires backend
- [ ] Tests E2E workflow
- [ ] Tests code recycling
- [ ] Tests notifications

---

## 🚀 Prochaine Étape

**Veux-tu que je commence par :**

1. **Jour 1 : Code Recycling System** (migration + service + API)
2. **Jour 2 : Wizard Import** (frontend d'abord pour valider UX)
3. **Autre priorité ?**

**Confirme et je commence l'implémentation !** 🎯
