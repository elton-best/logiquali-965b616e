# CHANGELOG - Critères d'évaluation & Module Audits

## Session du 24 Mars 2026

### Phase 1: Modèle EvaluationCriteria

**Fichiers créés:**

- `backend/database/migrations/2026_03_24_100000_create_evaluation_criteria_table.php`
- `backend/app/Models/EvaluationCriteria.php`
- `backend/app/Http/Controllers/Api/EvaluationCriteriaController.php`

**Routes API:**

- `/api/v1/evaluation-criteria` - CRUD complet
- `/api/v1/evaluation-criteria/defaults` - Critères par défaut
- `/api/v1/evaluation-criteria/initialize-defaults` - Initialisation
- `/api/v1/evaluation-criteria/reorder` - Réorganisation
- `/api/v1/evaluation-criteria/{id}/duplicate` - Duplication
- `/api/v1/evaluation-criteria/{id}/toggle-active` - Activation

**Statut:**  Complété

---

### Phase 2: Interface utilisateur critères

**Fichiers créés:**

- `frontend/src/api/services/evaluationCriteria.ts` - Service API TypeScript
- `frontend/src/modules/clienta/pages/performance/criteria.vue` - Page complète

**Fonctionnalités:**

- Drag & drop pour réorganiser
- Filtres par type/catégorie/statut
- CRUD complet avec dialogs
- Critères par défaut

**Route:** `/company/performance/criteria`

**Statut:**  Complété

---

### Phase 3: Boutons "Définir critères"

**Fichiers modifiés:**

- `frontend/src/modules/clienta/pages/performance/satisfaction.vue`
- `frontend/src/modules/clienta/pages/performance/evaluations.vue`
- `frontend/src/modules/clienta/pages/performance/audits/evaluation.vue`

**Statut:**  Complété

---

### Phase 4: Demandes d'évaluation (Email + Lien unique)

**Fichiers créés:**

- `backend/database/migrations/2026_03_24_110000_create_evaluation_requests_tables.php`
- `backend/app/Models/EvaluationRequest.php` - Gestion tokens UUID
- `backend/app/Models/EvaluationResponse.php` - Réponses avec scores
- `backend/app/Http/Controllers/Api/EvaluationRequestController.php`
- `backend/app/Mail/EvaluationRequestMail.php`
- `backend/resources/views/emails/evaluation-request.blade.php`
- `frontend/src/api/services/evaluationRequests.ts`
- `frontend/src/modules/clienta/pages/performance/evaluation-requests.vue`
- `frontend/src/pages/public/EvaluationForm.vue` - Formulaire public

**Routes API:**

- `/api/v1/evaluation-requests` - CRUD
- `/api/v1/evaluation-requests/{id}/send` - Envoi email
- `/api/v1/evaluation-requests/{id}/remind` - Relance
- `/api/v1/evaluation-requests/{id}/cancel` - Annulation
- `/api/v1/evaluation-requests/statistics` - Statistiques
- `/api/v1/public/evaluation/{token}` - Formulaire public (GET/POST)

**Routes Frontend:**

- `/company/performance/evaluation-requests` - Gestion demandes
- `/evaluation/:token` - Formulaire public

**Statut:**  Complété

---

### Phase 5: Compléments d'audits

**Fichiers créés:**

- `backend/database/migrations/2026_03_24_120000_enhance_audits_table.php`
- `backend/app/Models/AuditReminder.php`
- `backend/app/Models/AuditNormInput.php` - Clauses ISO 9001/14001/45001
- `backend/app/Jobs/SendAuditReminders.php`
- `backend/app/Notifications/AuditReminderNotification.php`

**Fichiers modifiés:**

- `backend/app/Models/Audit.php` - Relations reminders, normInputs
- `backend/app/Http/Controllers/Api/AuditController.php` - Nouvelles méthodes
- `backend/routes/api.php` - Nouvelles routes
- `backend/routes/console.php` - Job planifié

**Nouvelles colonnes `audits`:**

- `additional_info` (JSON)
- `input_elements` (JSON)
- `specific_points` (JSON)
- `reminder_enabled` (boolean)
- `reminder_days_before` (JSON)
- `sm_synthesis` (JSON)

**Tables créées:**

- `audit_reminders` - Rappels programmés
- `audit_norm_inputs` - Éléments d'entrée normatifs

**Routes API ajoutées:**

- `PUT /api/v1/audits/{id}/complements`
- `PUT /api/v1/audits/{id}/norm-inputs`
- `GET /api/v1/audits/{id}/synthesis`
- `POST /api/v1/audits/{id}/reminders`

**Statut:**  Complété

---

### Phase 6: Planification et rappels

**Fonctionnalités:**

- Job `SendAuditReminders` exécuté quotidiennement à 8h
- Rappels: 1 mois, 1 semaine, 1 jour avant, et audits en retard
- Notification par email et base de données
- Clauses ISO pré-configurées (9001, 14001, 45001)

**Statut:**  Complété

---

## Résumé des migrations

```bash
php artisan migrate
```

Tables créées/modifiées:

1. `evaluation_criteria` - Critères personnalisables
2. `evaluation_criteria_form` - Pivot polymorphique
3. `evaluation_requests` - Demandes avec tokens UUID
4. `evaluation_responses` - Réponses aux évaluations
5. `audit_reminders` - Rappels programmés
6. `audit_norm_inputs` - Éléments d'entrée normatifs
7. `audits` (modifiée) - Nouveaux champs JSON

---

## Commandes de test

```bash
# Backend
cd backend
php artisan route:list --path=evaluation
php artisan route:list --path=audits

# Frontend
cd frontend
npm run type-check
```

---

## Fichiers créés (total: 18)

### Backend (13 fichiers)

- 3 migrations
- 5 modèles (EvaluationCriteria, EvaluationRequest, EvaluationResponse, AuditReminder, AuditNormInput)
- 2 contrôleurs
- 1 job (SendAuditReminders)
- 1 notification (AuditReminderNotification)
- 1 mailable + 1 template email

### Frontend (5 fichiers)

- 2 services API
- 3 pages Vue (criteria, evaluation-requests, EvaluationForm public)

---

## Prochaines étapes suggérées

1. Créer l'interface d'édition des compléments d'audit (frontend)
2. Ajouter la vue synthèse SM au détail audit
3. Interface de configuration des rappels par audit
4. Tests end-to-end du flux complet
