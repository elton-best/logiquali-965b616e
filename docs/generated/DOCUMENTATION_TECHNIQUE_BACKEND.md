# Documentation Technique — Backend BestQHSE

Stack : Laravel 12 · PHP 8.2 · PostgreSQL · Redis · Laravel Reverb (WebSocket)

---

## 1. Stack technique

| Composant       | Technologie                        | Version      |
| --------------- | ---------------------------------- | ------------ |
| Framework       | Laravel                            | ^12.0        |
| Langage         | PHP                                | ^8.2         |
| Auth API        | Laravel Sanctum                    | ^4.2         |
| Permissions     | Spatie Permission                  | ^6.24        |
| Queue / Worker  | Laravel Horizon                    | ^5.43        |
| WebSocket       | Laravel Reverb                     | ^1.7         |
| Recherche       | Laravel Scout                      | ^10.23       |
| PDF             | barryvdh/laravel-dompdf            | ^3.1         |
| Excel/Word      | phpoffice/phpspreadsheet + phpword | ^1.30 / ^1.4 |
| QR Code         | simplesoftwareio/simple-qrcode     | ^4.2         |
| IA              | openai-php/laravel                 | ^0.12.0      |
| Cache / Session | Redis (predis)                     | ^3.3         |
| Logs activité   | spatie/laravel-activitylog         | ^4.10        |

---

## 2. Architecture générale

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/       # Tous les controllers REST
│   │   ├── Middleware/            # Auth, rate-limit, MFA, subscription
│   │   ├── Requests/              # Form Requests (validation)
│   │   └── Resources/             # API Resources (JSON:API)
│   ├── Models/                    # Eloquent Models
│   ├── Services/                  # Logique métier
│   ├── Events/ + Listeners/       # Event-driven (notifications)
│   ├── Jobs/                      # Queue jobs (exports, imports, reminders)
│   ├── Notifications/             # Notifications email + in-app
│   ├── Policies/                  # Autorisation par ressource
│   ├── Observers/                 # Hooks Eloquent
│   └── Console/Commands/          # Commandes planifiées (scheduler)
├── routes/api.php                 # Toutes les routes API v1
└── database/
    ├── migrations/
    ├── seeders/
    └── factories/
```

---

## 3. Authentification et sécurité

### 3.1 Types d'utilisateurs

Définis dans `User::TYPE_*` :

| Constante          | Valeur        | Rôle                                          |
| ------------------ | ------------- | --------------------------------------------- |
| `TYPE_SUPER_ADMIN` | `super_admin` | Administrateur plateforme                     |
| `TYPE_COMPANY`     | `company`     | Utilisateur entreprise (admin, collaborateur) |
| `TYPE_CLIENT_B`    | `clientb`     | Client final (portail dédié)                  |

### 3.2 Flux d'authentification

```
POST /api/v1/auth/login
  → AuthController::login()
  → Si MFA activé → POST /api/v1/auth/mfa/verify
  → Token Sanctum retourné
```

Routes publiques :

- `POST /auth/register/enterprise` — inscription entreprise
- `POST /auth/register/client` — inscription client B
- `POST /auth/forgot-password` / `POST /auth/reset-password`
- `GET /auth/verify-email/{id}/{hash}`

### 3.3 Middleware de sécurité

| Middleware                | Rôle                                |
| ------------------------- | ----------------------------------- |
| `auth:sanctum`            | Vérification token                  |
| `superadmin`              | Accès réservé super admin           |
| `force.password`          | Force changement mot de passe       |
| `force.signature`         | Force upload signature              |
| `force.company`           | Force configuration entreprise      |
| `check.subscription`      | Vérifie abonnement actif            |
| `ensure.ownership`        | Vérifie appartenance à l'entreprise |
| `mfa.stepup`              | MFA step-up pour actions sensibles  |
| `AdvancedRateLimit`       | Rate limiting avancé par IP/user    |
| `IpBlockingMiddleware`    | Blocage IP                          |
| `SecurityAuditMiddleware` | Journalisation sécurité             |

### 3.4 Système de permissions (Spatie)

Les permissions suivent le pattern `{code}.{action}` :

```
risks.read / risks.create / risks.update / risks.delete
documents.read / documents.manage
dashboard.read
...
```

Méthodes clés sur `User` :

- `canAccessModule(string $moduleCode, string $action)` — vérifie accès module
- `canAccessSubModule(string $subModuleCode, string $action)` — vérifie accès sous-module + abonnement
- `canAccessSection(string $sectionCode, string $action)` — vérifie accès section
- `getEffectivePermissionNames()` — permissions RBAC + catalogue abonnement
- `isEnterpriseAdmin()` — rôle `admin_entreprise`
- `isSiteManager()` — rôle `site_manager` ou manager du site

---

## 4. Modèle de données principal

### 4.1 Hiérarchie organisationnelle

```
Enterprise (1)
  └── Site (N)          ← unité de gestion (siège, filiale, etc.)
        └── EnterpriseSubscription (N)   ← abonnement par site
              └── Module → SubModule → SubModuleSection
```

### 4.2 Modèles clés

**Enterprise** — champs notables :

- `approval_status` : `pending | approved | rejected | suspended`
- `status` : `active | suspended`
- `brand_colors`, `header_footer_config`, `legal_profile_config` (JSON)
- `trial_ends_at` — fin de période d'essai
- Méthodes : `canAccessPlatform()`, `hasUsedTrial()`, `isApproved()`

**User** — champs notables :

- `user_type` : `super_admin | company | clientb`
- `enterprise_id`, `site_id`
- `must_change_password`, `signature_path`, `signature_uploaded_at`
- Méthodes : `isSuperAdmin()`, `isEnterpriseAdmin()`, `isSiteManager()`, `anonymize()`

**EnterpriseSubscription** — abonnement par site :

- `is_trial`, `is_active`, `status`, `expiration_date`, `trial_ends_at`
- Méthodes : `canAccessSubModule()`, `canAccessSection()`, `getAccessibleModules()`

### 4.3 Modules QHSE (principaux modèles)

| Module        | Modèles principaux                                                              |
| ------------- | ------------------------------------------------------------------------------- |
| Contexte      | `Context`, `ContextIssue`, `Stakeholder`, `StakeholderNeed`, `ApplicationScope` |
| Leadership    | `QhsePolicy`, `OrgChart`, `JobDescription`, `Responsibility`                    |
| Planification | `Risk`, `Opportunity`, `Objective`, `PlanAction`                                |
| Support       | `Document`, `Formation`, `Communication`, `Equipement`, `Habilitation`          |
| Opérations    | `OperationalProject`, `ProviderPartner`, `ComplianceObligation`                 |
| Performance   | `Audit`, `AuditProgram`, `AuditFinding`, `Indicateur`, `ManagementReview`       |
| Amélioration  | `NonConformity`, `Action`, `Reclamation`, `ImprovementSuggestion`               |
| ISO 14001     | `AspectEnvironnemental`, `ObligationConformiteEnvironnementale`                 |
| ISO 45001     | `Habilitation`, `EpiCatalogue`, `EpiStock`, `VerificationReglementaire`         |
| ISO 50001     | `ConsommationEnergie`, `Ipe`, `IpeValeur`                                       |
| Client B      | `Complaint`, `SatisfactionSurvey`, `ClientSatisfactionForm`, `SupportTicket`    |

---

## 5. Routes API (v1)

Base URL : `/api/v1/`

### 5.1 Routes publiques

```
POST   /auth/register/enterprise
POST   /auth/register/client
POST   /auth/login
POST   /auth/mfa/verify
POST   /auth/forgot-password
POST   /auth/reset-password
GET    /auth/verify-email/{id}/{hash}
GET    /geo/countries
GET    /geo/countries/{code}/cities
GET    /public/offers
GET    /verify/{hash}                    ← QR Code vérification
```

### 5.2 Routes protégées (auth:sanctum)

**Dashboard**

```
GET  /dashboard/stats
GET  /dashboard/kpis
GET  /dashboard/charts/{chartType}
GET  /dashboard/layout
POST /dashboard/layout
```

**Processus**

```
GET    /processes/statistics
GET    /processes/{id}/export-docx
GET    /processes-cartography
POST   /processes/{id}/verify
POST   /processes/{id}/validate
POST   /processes/{id}/reject
CRUD   /processes
```

**Risques & Opportunités**

```
GET    /risks/heatmap
GET    /risks/matrix
GET    /risks/statistics
GET    /risks/export-docx
POST   /risks/{id}/assess
POST   /risks/{id}/treat
POST   /risks/{id}/mitigate
CRUD   /risks
CRUD   /opportunities
```

**Audits**

```
POST   /audits/{id}/start
POST   /audits/{id}/checklist
POST   /audits/{id}/finding
POST   /audits/{id}/finalize
POST   /audits/{id}/complete
GET    /audits/{id}/download-report
GET    /audits/statistics
CRUD   /audits
CRUD   /audit-programs
```

**Documents**

```
POST   /documents/{id}/versions
GET    /documents/{id}/download/{versionId?}
POST   /documents/{id}/submit-for-approval
POST   /document-approvals/{id}/process
GET    /documents/pyramide/stats
GET    /documents/pyramide/export/{level}
CRUD   /documents
CRUD   /documents-inventory
```

**Non-conformités**

```
POST   /non-conformities/{id}/analyze
POST   /non-conformities/{id}/validate
POST   /non-conformities/{id}/verify
POST   /non-conformities/{id}/close
GET    /non-conformities/statistics
CRUD   /non-conformities
```

**SuperAdmin** (middleware `superadmin` + MFA step-up pour mutations)

```
GET    /superadmin/stats
GET    /superadmin/enterprises
POST   /superadmin/enterprises/{id}/approve
POST   /superadmin/enterprises/{id}/reject
POST   /superadmin/enterprises/{id}/suspend
CRUD   /superadmin/norms
CRUD   /superadmin/offers
GET    /superadmin/subscriptions
GET    /superadmin/users
```

**Client B**

```
GET    /clientb/dashboard/stats
GET    /clientb/dashboard/recent-activity
PUT    /clientb/profile
POST   /clientb/profile/change-password
CRUD   /clientb/support/tickets
CRUD   /complaints
CRUD   /satisfaction-surveys
CRUD   /client-satisfaction-forms
```

---

## 6. Services métier

| Service                       | Responsabilité                                |
| ----------------------------- | --------------------------------------------- |
| `RiskService`                 | Évaluation, traitement, matrice risques       |
| `AuditService`                | Cycle de vie audit, génération rapport        |
| `DocumentService`             | Versioning, workflow approbation, branding    |
| `NonConformityService`        | Analyse causes, actions correctives           |
| `SubscriptionService`         | Gestion abonnements, trial, expiration        |
| `PermissionService`           | Synchronisation permissions RBAC              |
| `MfaService`                  | Génération/vérification OTP                   |
| `PdfGeneratorService`         | Export PDF avec en-têtes personnalisés        |
| `RiskDocxGenerator`           | Export plan de maîtrise DOCX                  |
| `ProcessDocxGenerator`        | Export fiche processus DOCX                   |
| `QRCodeService`               | Génération et vérification QR codes           |
| `SignatureWorkflowService`    | Workflow de signatures électroniques          |
| `NotificationReminderService` | Rappels automatiques (échéances, expirations) |
| `TrialService`                | Gestion période d'essai                       |

---

## 7. Système d'événements

### 7.1 Événements principaux

| Événement                        | Déclencheur                 |
| -------------------------------- | --------------------------- |
| `ActionAssigned`                 | Attribution d'une action    |
| `ActionDeadlineApproaching`      | Échéance proche (scheduler) |
| `DocumentSubmittedForApproval`   | Soumission document         |
| `DocumentPublished`              | Publication document        |
| `EnterpriseApproved / Rejected`  | Décision superadmin         |
| `SubscriptionExpiring / Expired` | Expiration abonnement       |
| `ComplaintSubmitted / Replied`   | Cycle réclamation           |

### 7.2 Commandes planifiées (scheduler)

```
CheckActionDeadlines          — vérification échéances actions
CheckDocumentRevisions        — révisions documentaires dues
CheckHabilitationExpiry       — habilitations expirant
CheckSubscriptionExpiry       — abonnements expirant
CheckTrialExpirations         — fins de période d'essai
CheckFormationReminders       — rappels formations
CheckMaintenanceReminders     — rappels maintenances
CheckCommunicationReminders   — rappels communications
CheckRiskReassessments        — réévaluations risques
```

---

## 8. Génération de documents

### 8.1 Exports DOCX (phpoffice/phpword)

| Générateur                      | Document produit                   |
| ------------------------------- | ---------------------------------- |
| `RiskDocxGenerator`             | Plan de maîtrise des risques       |
| `ProcessDocxGenerator`          | Fiche processus (diagramme tortue) |
| `StakeholderDocxGenerator`      | Registre parties intéressées       |
| `QhsePolicyDocxGenerator`       | Politique QHSE                     |
| `NonConformityDocxGenerator`    | Rapport non-conformité             |
| `ManagementReviewDocxGenerator` | Compte-rendu revue de direction    |
| `ContextDocxGenerator`          | Contexte de l'organisme            |
| `ApplicationScopeDocxGenerator` | Domaine d'application              |

### 8.2 Exports PDF (dompdf)

- Politique QHSE
- Évaluation collaborateur (PIP)
- Évaluation prestataire
- Formulaire satisfaction client
- Rapport d'amélioration continue

### 8.3 Exports Excel (phpspreadsheet)

- Plan d'actions (import/export)
- Inventaire documentaire
- Plan de formation
- Obligations de conformité
- Consommations énergie (import CSV)

---

## 9. WebSocket (Laravel Reverb)

Canal : `DocumentChannel` — collaboration temps réel sur les documents.

Routes collaboration :

```
POST /documents/{id}/collaboration/join
POST /documents/{id}/collaboration/leave
GET  /documents/{id}/collaboration/state
POST /documents/{id}/collaboration/sync
POST /documents/{id}/collaboration/save
GET  /documents/{id}/collaboration/users
```

---

## 10. Observers Eloquent

| Observer                | Modèle surveillé | Actions                       |
| ----------------------- | ---------------- | ----------------------------- |
| `RiskObserver`          | Risk             | Notifications, journalisation |
| `DocumentObserver`      | Document         | Workflow approbation, QR code |
| `AuditObserver`         | Audit            | Notifications participants    |
| `NonConformityObserver` | NonConformity    | Suivi statut                  |
| `ActionObserver`        | Action           | Rappels, notifications        |
| `UserObserver`          | User             | Bienvenue, permissions        |
| `ProcessObserver`       | Process          | Versioning                    |
| `StakeholderObserver`   | Stakeholder      | Journalisation                |

---

## 11. Traits réutilisables

| Trait                     | Fonctionnalité                                  |
| ------------------------- | ----------------------------------------------- |
| `BelongsToEnterprise`     | Scope automatique par enterprise_id             |
| `HasAuditFields`          | `created_by`, `updated_by` automatiques         |
| `HasReference`            | Génération référence unique (`ref`)             |
| `HasActions`              | Relation polymorphe vers actions                |
| `HasWorkflowStates`       | Machine à états workflow                        |
| `HasAxes`                 | Relation vers axes stratégiques                 |
| `NotifiesSiteUsers`       | Notification broadcast aux utilisateurs du site |
| `CreatesUserNotification` | Création notification in-app                    |

---

## 12. Sécurité avancée

- **Rate limiting** : throttle par route (`login`, `search`), blocage IP via `AdvancedRateLimit`
- **MFA** : OTP par email, step-up MFA pour actions sensibles superadmin
- **Audit log** : `SecurityAuditLog` + `spatie/laravel-activitylog`
- **Upload sécurisé** : `SecureFileUpload` (Rule) + `UploadSecurityService`
- **RGPD** : `ConsentLog`, anonymisation `User::anonymize()`, révocation consentements
- **QR Code** : vérification intégrité documents via hash signé
- **Soft deletes** : sur tous les modèles sensibles
- **Ownership** : middleware `EnsureEnterpriseOwnership` sur toutes les routes protégées
