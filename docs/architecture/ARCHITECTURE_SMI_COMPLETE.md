# ARCHITECTURE SYSTÈME DE MANAGEMENT INTÉGRÉ (SMI)

## BestQHSE - Documentation Complète

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture de la base de données](#architecture-de-la-base-de-données)
3. [Système de notifications](#système-de-notifications)
4. [Interactions entre modules](#interactions-entre-modules)
5. [Workflows automatisés](#workflows-automatisés)
6. [Bonnes pratiques d'implémentation](#bonnes-pratiques-dimplémentation)

---

## 🎯 VUE D'ENSEMBLE

### Objectifs du système

- **Multi-tenant** : Support de plusieurs entreprises avec isolation des données
- **Multi-sites** : Gestion de plusieurs sites par entreprise
- **Scalable** : Architecture évolutive pour supporter la croissance
- **Notifications intelligentes** : Système d'alertes contextuelles et personnalisables
- **Traçabilité complète** : Audit trail de toutes les actions
- **Conformité ISO** : Support des normes ISO 9001, 14001, 45001, 50001

### Modules principaux

1. **Gestion des entreprises & utilisateurs**
2. **Abonnements & facturation**
3. **Contexte de l'organisme (ISO §4)**
4. **Processus (ISO §4.4)**
5. **Risques & opportunités (ISO §6.1)**
6. **Objectifs & indicateurs (ISO §6.2)**
7. **Documents & GED**
8. **Audits (ISO §9.2)**
9. **Non-conformités & actions (ISO §10.2)**
10. **Réclamations clients**
11. **Formation & compétences**
12. **Équipements & maintenance**

---

## 🗄️ ARCHITECTURE DE LA BASE DE DONNÉES

### Principes de conception

#### 1. Multi-tenancy

```
Enterprise (Tenant)
  └── Sites (Multi-sites)
       └── Toutes les données métier
```

**Isolation des données** :

- Chaque table métier a un `site_id` ou `enterprise_id`
- Les requêtes sont automatiquement filtrées par tenant
- Sécurité renforcée avec Row Level Security (RLS)

#### 2. Références uniques

Toutes les entités ont un champ `ref` unique pour :

- Identification humaine (ex: OBJ-2024-001)
- Traçabilité
- Intégrations externes

#### 3. Soft deletes

- Champ `deleted_at` sur toutes les tables principales
- Conservation de l'historique
- Possibilité de restauration

#### 4. Audit trail

- Table `activity_log` (Spatie Activity Log)
- Enregistrement de toutes les actions
- Traçabilité complète : qui, quoi, quand

---

## 🔔 SYSTÈME DE NOTIFICATIONS

### Architecture des notifications

#### 1. Types de notifications

**Notifications in-app** (table `notifications`)

```json
{
  "type": "App\\Notifications\\DocumentExpiring",
  "data": {
    "document_id": 123,
    "document_title": "Procédure PRO-001",
    "expiration_date": "2024-03-15",
    "days_remaining": 7
  }
}
```

**Alertes système** (table `system_alerts`)

- Alertes automatiques basées sur des règles
- Niveaux de sévérité : info, warning, error, critical
- Résolution trackée

**Préférences utilisateur** (table `notification_preferences`)

- Personnalisation par canal (email, SMS, push, in-app)
- Personnalisation par type d'événement
- Opt-in/opt-out granulaire

#### 2. Événements déclencheurs

**Documents**

- ✅ Document expire dans 30 jours → Warning
- ✅ Document expire dans 7 jours → Error
- ✅ Document expiré → Critical
- ✅ Nouveau document créé → Info
- ✅ Document approuvé → Info

**Audits**

- ✅ Audit planifié dans 7 jours → Warning
- ✅ Audit aujourd'hui → Info
- ✅ Audit en retard → Error
- ✅ Constat d'audit créé → Info

**Non-conformités**

- ✅ NC assignée → Info
- ✅ NC échéance dans 7 jours → Warning
- ✅ NC en retard → Error
- ✅ NC fermée → Info

**Actions**

- ✅ Action assignée → Info
- ✅ Action échéance dans 3 jours → Warning
- ✅ Action en retard → Error
- ✅ Action terminée → Info

**Objectifs**

- ✅ Objectif non atteint → Warning
- ✅ Saisie mensuelle manquante → Warning
- ✅ Objectif atteint → Info

**Abonnements**

- ✅ Abonnement expire dans 30 jours → Warning
- ✅ Abonnement expire dans 7 jours → Error
- ✅ Abonnement expiré → Critical

**Formations**

- ✅ Formation planifiée dans 7 jours → Info
- ✅ Certificat expire dans 30 jours → Warning

**Équipements**

- ✅ Maintenance due dans 7 jours → Warning
- ✅ Maintenance en retard → Error
- ✅ Garantie expire dans 30 jours → Warning

#### 3. Implémentation des notifications

**Backend (Laravel)**

```php
// Notification Laravel
class DocumentExpiringNotification extends Notification
{
    public function via($notifiable)
    {
        $preferences = $notifiable->notificationPreferences()
            ->where('event_type', 'document_expiring')
            ->where('is_enabled', true)
            ->pluck('channel')
            ->toArray();

        return $preferences; // ['mail', 'database', 'broadcast']
    }

    public function toArray($notifiable)
    {
        return [
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'expiration_date' => $this->document->expiration_date,
            'days_remaining' => $this->daysRemaining,
            'severity' => $this->getSeverity(),
            'action_url' => route('documents.show', $this->document)
        ];
    }
}

// Déclenchement
$users = User::whereHas('roles', function($q) {
    $q->where('name', 'quality_manager');
})->get();

foreach ($users as $user) {
    $user->notify(new DocumentExpiringNotification($document));
}
```

**Frontend (Vue.js)**

```typescript
// Store Pinia pour notifications
export const useNotificationStore = defineStore("notifications", {
  state: () => ({
    notifications: [],
    unreadCount: 0,
  }),

  actions: {
    async fetchNotifications() {
      const response = await api.get("/notifications");
      this.notifications = response.data.data;
      this.unreadCount = this.notifications.filter((n) => !n.read_at).length;
    },

    async markAsRead(notificationId) {
      await api.post(`/notifications/${notificationId}/read`);
      const notification = this.notifications.find(
        (n) => n.id === notificationId,
      );
      if (notification) {
        notification.read_at = new Date();
        this.unreadCount--;
      }
    },

    // WebSocket listener
    setupRealtimeNotifications() {
      Echo.private(`App.Models.User.${userId}`).notification((notification) => {
        this.notifications.unshift(notification);
        this.unreadCount++;
        this.showToast(notification);
      });
    },
  },
});
```

---

## 🔄 INTERACTIONS ENTRE MODULES

### 1. Processus → Risques → Actions

```
Processus identifié
  ↓
Analyse des risques associés
  ↓
Création d'actions de traitement
  ↓
Suivi et évaluation de l'efficacité
```

### 2. Audit → Constats → Non-conformités → Actions

```
Audit planifié
  ↓
Constats d'audit (NC majeure/mineure)
  ↓
Création de non-conformités
  ↓
Actions correctives/préventives
  ↓
Vérification de l'efficacité
```

### 3. Réclamation → Non-conformité → Action

```
Réclamation client reçue
  ↓
Analyse et création NC si nécessaire
  ↓
Actions correctives
  ↓
Retour client et satisfaction
```

### 4. Objectif → Indicateurs → Actions

```
Objectif défini
  ↓
Saisie périodique des réalisations
  ↓
Calcul du taux d'atteinte
  ↓
Si non atteint → Actions d'amélioration
```

### 5. Document → Processus → Formation

```
Document créé/modifié
  ↓
Lié à un processus
  ↓
Formation du personnel concerné
  ↓
Évaluation de la compréhension
```

---

## ⚙️ WORKFLOWS AUTOMATISÉS

### 1. Workflow de validation de document

```
Création (draft)
  ↓
Revue (review) → Notification au reviewer
  ↓
Approbation (approved) → Notification aux utilisateurs concernés
  ↓
Effectif (effective_date)
  ↓
Alerte 30j avant expiration
  ↓
Alerte 7j avant expiration
  ↓
Document obsolète → Notification pour renouvellement
```

### 2. Workflow de traitement NC

```
Détection NC
  ↓
Création et assignation → Notification au responsable
  ↓
Analyse des causes
  ↓
Actions correctives → Notifications aux impliqués
  ↓
Mise en œuvre
  ↓
Vérification efficacité
  ↓
Clôture → Notification de clôture
```

### 3. Workflow d'audit

```
Planification audit → Notification équipe audit + audités
  ↓
Rappel 7j avant
  ↓
Rappel 1j avant
  ↓
Réalisation audit
  ↓
Constats → Notifications responsables processus
  ↓
Actions correctives
  ↓
Rapport final → Notification direction
```

### 4. Workflow de formation

```
Identification besoin
  ↓
Planification formation → Notification participants
  ↓
Rappel 7j avant
  ↓
Rappel 1j avant
  ↓
Réalisation
  ↓
Évaluation
  ↓
Émission certificat → Notification participant
  ↓
Alerte expiration certificat (si applicable)
```

---

## 🎯 BONNES PRATIQUES D'IMPLÉMENTATION

### 1. Notifications intelligentes

**Éviter le spam**

- Grouper les notifications similaires
- Digest quotidien/hebdomadaire optionnel
- Seuils de criticité configurables

**Personnalisation**

```php
// Exemple de préférences par défaut
$defaultPreferences = [
    'document_expiring' => ['email', 'in_app'],
    'nc_assigned' => ['email', 'in_app', 'push'],
    'audit_reminder' => ['email', 'in_app'],
    'action_late' => ['email', 'push'],
    'objective_not_met' => ['in_app'],
];
```

**Priorisation**

```php
// Ordre de priorité des notifications
$priorities = [
    'critical' => 1, // Rouge, son, push immédiat
    'error' => 2,    // Orange, push
    'warning' => 3,  // Jaune, in-app
    'info' => 4,     // Bleu, in-app seulement
];
```

### 2. Performance & scalabilité

**Indexation**

- Index sur tous les foreign keys
- Index sur les champs de recherche fréquents
- Index composites pour les requêtes complexes

**Caching**

```php
// Cache des données fréquemment accédées
Cache::remember("site.{$siteId}.processes", 3600, function() use ($siteId) {
    return Process::where('site_id', $siteId)->get();
});
```

**Queue jobs**

```php
// Notifications en arrière-plan
dispatch(new SendNotificationJob($users, $notification));

// Génération de rapports
dispatch(new GenerateAuditReportJob($audit))->onQueue('reports');
```

### 3. Sécurité

**Row Level Security**

```php
// Scope global pour isolation multi-tenant
protected static function booted()
{
    static::addGlobalScope('site', function (Builder $builder) {
        if (auth()->check() && auth()->user()->site_id) {
            $builder->where('site_id', auth()->user()->site_id);
        }
    });
}
```

**Permissions granulaires**

```php
// Permissions par module
$permissions = [
    'documents.view',
    'documents.create',
    'documents.edit',
    'documents.delete',
    'documents.approve',
    'audits.view',
    'audits.create',
    'audits.conduct',
    'nc.view',
    'nc.create',
    'nc.assign',
    'nc.close',
];
```

### 4. Traçabilité

**Activity Log**

```php
activity()
    ->performedOn($document)
    ->causedBy(auth()->user())
    ->withProperties(['old' => $old, 'new' => $new])
    ->log('Document updated');
```

**Audit trail dans l'UI**

```vue
<v-timeline density="compact">
  <v-timeline-item
    v-for="activity in activities"
    :key="activity.id"
    :dot-color="getActivityColor(activity.event)"
  >
    <template #icon>
      <v-icon>{{ getActivityIcon(activity.event) }}</v-icon>
    </template>
    <div>
      <strong>{{ activity.causer.name }}</strong>
      {{ activity.description }}
      <div class="text-caption">{{ formatDate(activity.created_at) }}</div>
    </div>
  </v-timeline-item>
</v-timeline>
```

### 5. UX/UI pour notifications

**Badge de notification**

```vue
<v-badge
  :content="notificationStore.unreadCount"
  :model-value="notificationStore.unreadCount > 0"
  color="error"
  overlap
>
  <v-btn icon @click="openNotifications">
    <v-icon>mdi-bell</v-icon>
  </v-btn>
</v-badge>
```

**Toast notifications**

```typescript
// Notification toast pour actions immédiates
toast.success("Document approuvé avec succès", {
  action: {
    label: "Voir",
    onClick: () => router.push(`/documents/${documentId}`),
  },
});
```

**Centre de notifications**

```vue
<v-menu max-width="400">
  <template #activator="{ props }">
    <v-btn icon v-bind="props">
      <v-badge :content="unreadCount" color="error">
        <v-icon>mdi-bell</v-icon>
      </v-badge>
    </v-btn>
  </template>
  
  <v-card>
    <v-card-title>Notifications</v-card-title>
    <v-list max-height="400">
      <v-list-item
        v-for="notif in notifications"
        :key="notif.id"
        :class="{ 'bg-blue-lighten-5': !notif.read_at }"
        @click="handleNotificationClick(notif)"
      >
        <template #prepend>
          <v-avatar :color="getSeverityColor(notif.severity)">
            <v-icon>{{ getNotificationIcon(notif.type) }}</v-icon>
          </v-avatar>
        </template>
        <v-list-item-title>{{ notif.data.title }}</v-list-item-title>
        <v-list-item-subtitle>{{ notif.data.message }}</v-list-item-subtitle>
        <template #append>
          <span class="text-caption">{{ formatRelativeTime(notif.created_at) }}</span>
        </template>
      </v-list-item>
    </v-list>
  </v-card>
</v-menu>
```

---

## 📊 MÉTRIQUES & KPIs

### Métriques système à tracker

1. **Performance**
   - Temps de réponse API
   - Temps de chargement pages
   - Taux d'erreur

2. **Utilisation**
   - Utilisateurs actifs (DAU/MAU)
   - Fonctionnalités les plus utilisées
   - Taux d'adoption par module

3. **Qualité**
   - Nombre de NC ouvertes/fermées
   - Taux de conformité audits
   - Taux d'atteinte objectifs

4. **Engagement**
   - Taux de lecture notifications
   - Taux de complétion actions
   - Temps moyen de résolution NC

---

## 🚀 ROADMAP D'IMPLÉMENTATION

### Phase 1 : Fondations (Semaines 1-2)

- ✅ Base de données complète
- ✅ Authentification & permissions
- ✅ Multi-tenancy
- ✅ Système de notifications de base

### Phase 2 : Modules Core (Semaines 3-6)

- ✅ Contexte organisme (SWOT, PESTEL, Parties intéressées)
- ✅ Processus
- ✅ Risques & opportunités
- ✅ Objectifs & indicateurs

### Phase 3 : Modules Opérationnels (Semaines 7-10)

- ✅ Documents & GED
- ✅ Audits
- ✅ Non-conformités & actions
- ✅ Réclamations

### Phase 4 : Modules Support (Semaines 11-12)

- ✅ Formation
- ✅ Équipements & maintenance
- ✅ Rapports & analytics

### Phase 5 : Optimisation (Semaines 13-14)

- Notifications avancées
- Workflows automatisés
- Intégrations externes
- Mobile app

---

## 📝 CONCLUSION

Cette architecture fournit une base solide et scalable pour un système de management intégré moderne. Les points clés :

✅ **Multi-tenant sécurisé** avec isolation des données
✅ **Notifications intelligentes** personnalisables par utilisateur
✅ **Traçabilité complète** de toutes les actions
✅ **Interactions fluides** entre tous les modules
✅ **Performance optimisée** avec caching et indexation
✅ **UX moderne** avec feedback temps réel
✅ **Conformité ISO** intégrée dans la structure

Le système est conçu pour faciliter l'appropriation par les entreprises grâce à :

- Interface intuitive et moderne
- Notifications contextuelles pertinentes
- Workflows guidés
- Tableaux de bord visuels
- Documentation intégrée
