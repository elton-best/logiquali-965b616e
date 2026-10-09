# Day 3 - Enhanced Notifications ✅

**Date**: 2026-04-29  
**Status**: COMPLETED  
**Dépendances**: Day 1 (Code Recycling), Day 2 (Import Wizard)

---

## Implémentation

### 1. Backend - Event System

#### **Event**: `DocumentWorkflowEvent`

**Fichier**: `app/Events/DocumentWorkflowEvent.php`

**Propriétés**:
```php
public Document $document
public string $action
public ?User $actor
public ?string $reason
```

**Actions supportées**:
- `imported` - Document importé avec besoin de vérification
- `submitted_for_verification` - Document soumis pour vérification
- `verified` - Document vérifié, en attente d'approbation
- `approved` - Document approuvé et publié
- `rejected` - Document rejeté
- `code_released` - Code libéré pour réutilisation

**Méthode**:
```php
getNotificationData(): array
```
Retourne les données formatées pour la notification.

---

#### **Listener**: `SendDocumentWorkflowNotification`

**Fichier**: `app/Listeners/SendDocumentWorkflowNotification.php`

**Responsabilités**:
1. Détermine les destinataires selon l'action
2. Construit le message de notification
3. Crée les enregistrements `UserNotification`
4. Log les notifications envoyées

**Logique de routage**:

| Action | Destinataires |
|--------|---------------|
| `imported`, `submitted_for_verification` | Utilisateurs avec permission `verify_documents` |
| `verified` | Utilisateurs avec permission `approve_documents` |
| `approved` | Auteur du document |
| `rejected` | Auteur du document |
| `code_released` | Gestionnaires de documents |

**Méthodes privées**:
- `getRecipients()` - Détermine les destinataires
- `getVerifiers()` - Récupère les vérificateurs
- `getApprovers()` - Récupère les approbateurs
- `getDocumentManagers()` - Récupère les gestionnaires
- `buildNotificationData()` - Construit titre/message/URL

**Exemples de notifications**:

```php
// Imported
[
  'title' => 'Nouveau document à vérifier',
  'message' => 'Jean Dupont a importé le document "Manuel Qualité" (DOC-001) qui nécessite votre vérification.',
  'action_url' => '/documents/verification?document_id=123',
]

// Verified
[
  'title' => 'Document vérifié - Approbation requise',
  'message' => 'Marie Martin a vérifié le document "Procédure Audit" (PR-002). Votre approbation est requise.',
  'action_url' => '/documents/approbation?document_id=124',
]

// Approved
[
  'title' => 'Document approuvé',
  'message' => 'Pierre Durand a approuvé votre document "Manuel Qualité" (DOC-001). Il est maintenant publié.',
  'action_url' => '/documents/123',
]

// Rejected
[
  'title' => 'Document rejeté',
  'message' => 'Sophie Lefebvre a rejeté votre document "Formulaire Audit" (FOR-003). Raison: Format non conforme',
  'action_url' => '/documents/125',
]
```

---

#### **Integration dans Controller**

**Fichier**: `app/Http/Controllers/Api/DocumentWorkflowController.php`

**Modifications**:

1. **Import de l'event**:
```php
use App\Events\DocumentWorkflowEvent;
```

2. **Dispatch dans `confirmCode()`**:
```php
event(new DocumentWorkflowEvent($document, 'submitted_for_verification', $user));
```

3. **Dispatch dans `verify()`**:
```php
event(new DocumentWorkflowEvent($document, 'verified', $user));
```

4. **Dispatch dans `approve()`**:
```php
event(new DocumentWorkflowEvent($document, 'approved', $user));
```

5. **Dispatch dans `reject()`**:
```php
event(new DocumentWorkflowEvent($document, 'rejected', $user, $request->input('rejection_reason')));
```

---

#### **Registration dans EventServiceProvider**

**Fichier**: `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    // ...
    \App\Events\DocumentWorkflowEvent::class => [
        \App\Listeners\SendDocumentWorkflowNotification::class,
    ],
];
```

---

### 2. Frontend - Composable

#### **Composable**: `useNotifications`

**Fichier**: `frontend/src/composables/useNotifications.ts`

**Interfaces**:

```typescript
interface UserNotification {
  id: number
  type: string
  title: string
  message: string
  data?: Record<string, any>
  action_url?: string
  is_read: boolean
  created_at: string
  read_at?: string
}

interface NotificationStats {
  unread_count: number
  total_count: number
}
```

**State**:
- `notifications: Ref<UserNotification[]>` - Liste des notifications
- `stats: Ref<NotificationStats>` - Statistiques
- `loading: Ref<boolean>` - État de chargement
- `error: Ref<string | null>` - Erreur éventuelle
- `pollingInterval: Ref<number | null>` - ID de l'intervalle de polling

**Computed**:
- `unreadCount` - Nombre de notifications non lues
- `hasUnread` - Booléen si notifications non lues
- `unreadNotifications` - Filtre notifications non lues
- `readNotifications` - Filtre notifications lues

**Méthodes principales**:

#### `fetchNotifications(params?): Promise<void>`
Récupère les notifications depuis l'API.
- Endpoint: `GET /notifications`
- Params: `{ page?, per_page? }`
- Met à jour `notifications` et `stats`

#### `markAsRead(notificationId): Promise<void>`
Marque une notification comme lue.
- Endpoint: `POST /notifications/{id}/mark-as-read`
- Met à jour localement + stats

#### `markAsUnread(notificationId): Promise<void>`
Marque une notification comme non lue.
- Endpoint: `POST /notifications/{id}/mark-as-unread`
- Met à jour localement + stats

#### `markAllAsRead(): Promise<void>`
Marque toutes les notifications comme lues.
- Endpoint: `POST /notifications/mark-all-as-read`
- Met à jour toutes localement + stats

#### `deleteNotification(notificationId): Promise<void>`
Supprime une notification.
- Endpoint: `DELETE /notifications/{id}`
- Retire de la liste locale + stats

#### `startPolling(intervalMs = 30000): void`
Démarre le polling automatique.
- Intervalle par défaut: 30 secondes
- Appelle `fetchNotifications()` périodiquement

#### `stopPolling(): void`
Arrête le polling automatique.

**Méthodes utilitaires**:

#### `getNotificationIcon(type): string`
Retourne l'icône Material Design selon le type.

| Type | Icône |
|------|-------|
| `document_workflow` | `mdi-file-document-check` |
| `action_assigned` | `mdi-clipboard-check` |
| `audit_scheduled` | `mdi-calendar-check` |
| `complaint_received` | `mdi-alert-circle` |
| `subscription_expiring` | `mdi-alert` |
| default | `mdi-bell` |

#### `getNotificationColor(type): string`
Retourne la couleur selon le type.

| Type | Couleur |
|------|---------|
| `document_workflow` | `primary` |
| `action_assigned` | `warning` |
| `audit_scheduled` | `info` |
| `complaint_received` | `error` |
| `subscription_expiring` | `warning` |
| default | `grey` |

#### `formatRelativeTime(dateString): string`
Formate la date en temps relatif.

**Exemples**:
- `< 1 min` → "À l'instant"
- `< 60 min` → "Il y a 15 min"
- `< 24h` → "Il y a 3h"
- `< 7j` → "Il y a 2j"
- `>= 7j` → "15 nov"

**Lifecycle**:
- `onMounted()` - Fetch initial + start polling
- `onUnmounted()` - Stop polling

---

### 3. Frontend - Components

#### **Component**: `NotificationCenter.vue`

**Fichier**: `frontend/src/components/notifications/NotificationCenter.vue`

**Features**:
- Badge avec count non lues
- Dropdown menu avec liste notifications
- Tabs "Toutes" / "Non lues"
- Actions: marquer lu/non lu, supprimer
- Bouton "Tout marquer lu"
- Link vers page notifications complète

**Structure**:

```
┌─────────────────────────────────────┐
│  🔔 Notifications [3]               │
│                    [Tout marquer lu]│
├─────────────────────────────────────┤
│  [Toutes (15)]  [Non lues (3)]      │
├─────────────────────────────────────┤
│                                     │
│  📄 Nouveau document à vérifier     │
│     Jean a importé "Manuel..."      │
│     Il y a 5 min              [⋮]   │
│                                     │
│  ✅ Document vérifié                │
│     Marie a vérifié "Procédure..."  │
│     Il y a 1h                 [⋮]   │
│                                     │
│  ✓ Document approuvé                │
│     Pierre a approuvé votre...      │
│     Il y a 2h                 [⋮]   │
│                                     │
├─────────────────────────────────────┤
│  [Voir toutes les notifications]    │
└─────────────────────────────────────┘
```

**Props**: Aucune

**Events**: Aucun (navigation interne)

**State local**:
- `menuOpen: Ref<boolean>` - État du dropdown
- `activeTab: Ref<'all' | 'unread'>` - Tab actif

**Méthodes**:
- `handleNotificationClick()` - Marque lu + navigation
- `handleMarkAllAsRead()` - Marque toutes lues
- `handleDelete()` - Supprime notification
- `goToNotificationsPage()` - Navigation vers page complète

---

#### **Component**: `NotificationItem.vue`

**Fichier**: `frontend/src/components/notifications/NotificationItem.vue`

**Features**:
- Avatar avec icône colorée selon type
- Titre + message (2 lignes max)
- Temps relatif
- Indicateur "non lu" (point bleu)
- Menu actions (marquer lu/non lu, supprimer)
- Hover effect
- Click pour navigation

**Props**:
```typescript
{
  notification: UserNotification
}
```

**Events**:
```typescript
{
  click: []
  'mark-read': []
  'mark-unread': []
  delete: []
}
```

**Styling**:
- Background bleu clair si non lu
- Hover effect
- Actions visibles au hover
- Responsive

---

## Flux complet

### Scénario 1: Import document avec vérification

```
1. User A importe document via wizard
   ↓
2. Backend: DocumentWorkflowEvent('imported', document, userA)
   ↓
3. Listener: Trouve vérificateurs (permission verify_documents)
   ↓
4. Crée UserNotification pour chaque vérificateur
   ↓
5. Frontend: Polling détecte nouvelle notification
   ↓
6. Badge count s'incrémente
   ↓
7. Vérificateur B ouvre dropdown
   ↓
8. Voit "Nouveau document à vérifier"
   ↓
9. Click → Marque lu + Navigation vers /documents/verification
```

### Scénario 2: Vérification document

```
1. Vérificateur B vérifie document
   ↓
2. Backend: DocumentWorkflowEvent('verified', document, userB)
   ↓
3. Listener: Trouve approbateurs (permission approve_documents)
   ↓
4. Crée UserNotification pour chaque approbateur
   ↓
5. Frontend: Polling détecte nouvelle notification
   ↓
6. Approbateur C voit "Document vérifié - Approbation requise"
   ↓
7. Click → Navigation vers /documents/approbation
```

### Scénario 3: Approbation document

```
1. Approbateur C approuve document
   ↓
2. Backend: DocumentWorkflowEvent('approved', document, userC)
   ↓
3. Listener: Trouve auteur (document.author)
   ↓
4. Crée UserNotification pour auteur
   ↓
5. Frontend: Polling détecte nouvelle notification
   ↓
6. Auteur A voit "Document approuvé"
   ↓
7. Click → Navigation vers /documents/{id}
```

### Scénario 4: Rejet document

```
1. Vérificateur/Approbateur rejette document
   ↓
2. Backend: DocumentWorkflowEvent('rejected', document, user, reason)
   ↓
3. Listener: Trouve auteur
   ↓
4. Crée UserNotification avec raison de rejet
   ↓
5. Frontend: Polling détecte nouvelle notification
   ↓
6. Auteur voit "Document rejeté - Raison: ..."
   ↓
7. Click → Navigation vers document pour correction
```

---

## Configuration

### Polling interval

**Default**: 30 secondes

**Personnalisation**:
```typescript
const { startPolling } = useNotifications()

// Polling toutes les 15 secondes
startPolling(15000)

// Polling toutes les minutes
startPolling(60000)
```

### Permissions requises

**Backend** (Laravel Spatie):
- `verify_documents` - Pour recevoir notifications de vérification
- `approve_documents` - Pour recevoir notifications d'approbation

**Roles suggérés**:
- `document_verifier` - Inclut `verify_documents`
- `document_approver` - Inclut `approve_documents`
- `document_manager` - Inclut les deux + gestion codes

---

## API Endpoints utilisés

### GET /notifications
Liste les notifications de l'utilisateur.

**Query params**:
- `page?: number`
- `per_page?: number` (default: 50)

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "type": "document_workflow",
      "title": "Nouveau document à vérifier",
      "message": "Jean a importé...",
      "data": {
        "document_id": 123,
        "document_code": "DOC-001",
        "action": "imported",
        "actor_name": "Jean Dupont"
      },
      "action_url": "/documents/verification?document_id=123",
      "is_read": false,
      "created_at": "2026-04-29T10:30:00Z",
      "read_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 15
  }
}
```

### POST /notifications/{id}/mark-as-read
Marque une notification comme lue.

**Response**: `200 OK`

### POST /notifications/{id}/mark-as-unread
Marque une notification comme non lue.

**Response**: `200 OK`

### POST /notifications/mark-all-as-read
Marque toutes les notifications comme lues.

**Response**: `200 OK`

### DELETE /notifications/{id}
Supprime une notification.

**Response**: `204 No Content`

---

## Performance

### Optimisations

1. **Polling intelligent**:
   - Intervalle configurable
   - Stop automatique au unmount
   - Pas de polling si page inactive (future)

2. **Cache local**:
   - Notifications en mémoire
   - Mise à jour optimiste (mark read/unread)
   - Pas de re-fetch inutile

3. **Lazy loading**:
   - Composants chargés à la demande
   - Dropdown ne render que si ouvert

4. **Batch operations**:
   - `markAllAsRead()` en une requête
   - Pas de boucle côté frontend

---

## Tests recommandés

### Backend

1. **Event dispatch**:
   - Vérifier event dispatché sur chaque action workflow
   - Vérifier données event correctes

2. **Listener**:
   - Vérifier destinataires corrects selon action
   - Vérifier création UserNotification
   - Vérifier contenu notification

3. **Permissions**:
   - Vérifier seuls users avec permission reçoivent notification
   - Vérifier isolation par enterprise

### Frontend

1. **Composable**:
   - Fetch notifications
   - Mark read/unread
   - Delete notification
   - Polling start/stop

2. **Components**:
   - Badge count correct
   - Tabs fonctionnels
   - Actions menu
   - Navigation

3. **Integration**:
   - Polling détecte nouvelles notifications
   - Click marque lu + navigue
   - Badge se met à jour

---

## Prochaines étapes (Day 4+)

**Améliorations possibles**:
- WebSocket pour notifications temps réel (vs polling)
- Push notifications navigateur
- Filtres avancés (par type, date)
- Recherche dans notifications
- Archivage automatique anciennes notifications
- Préférences utilisateur (types notifications)

---

**Temps d'implémentation**: ~2h30  
**Complexité**: Moyenne-Haute (event system + real-time polling)  
**Qualité**: Production-ready avec polling optimisé
