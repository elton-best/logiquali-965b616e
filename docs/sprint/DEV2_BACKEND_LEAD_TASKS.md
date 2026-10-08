# 👨‍💻 DEV 2 - BACKEND LEAD - Tâches Sprint 5 Jours

**Rôle:** Backend Lead (Focus API/BDD)  
**Sprint:** 10-14 février 2026  
**Charge:** 40 heures (8h/jour × 5 jours)

---

## 🎯 OBJECTIFS PERSONNELS

- ✅ Créer **3 migrations** Laravel (BDD Point 4)
- ✅ Développer **3 modèles Eloquent** complets
- ✅ Implémenter **API REST complète** (10+ endpoints)
- ✅ **Tests PHPUnit** > 80% coverage

---

## 📅 PLANNING DÉTAILLÉ

### JOUR 1 (Lundi) - MIGRATIONS & MODÈLES

**Matin (4h):**

```
9h00-9h15   Daily stand-up
9h15-10h30  Migration create_analysis_categories_table
10h30-11h30 Migration create_organization_contexts_table
11h30-13h00 Migration create_context_issues_table
```

**Après-midi (4h):**

```
14h00-15h00 Modèle AnalysisCategory.php
15h00-16h00 Modèle OrganizationContext.php
16h00-17h00 Modèle ContextIssue.php
17h00-18h00 Seeder catégories SWOT/PESTEL + test
```

**Livrables J1:** BDD Point 4 prête ✅

---

### JOUR 2 (Mardi) - CONTROLLERS & ROUTES

**Matin (4h):**

```
9h00-9h15   Daily stand-up
9h15-10h30  AnalysisCategoryController.php
10h30-11h30 ContextController.php (CRUD)
11h30-13h00 Routes API /analysis-categories + /contexts
```

**Après-midi (4h):**

```
14h00-15h30 ContextIssueController.php (CRUD)
15h30-16h30 Routes API /contexts/{id}/issues
16h30-17h30 Validation StoreContextIssueRequest
17h30-18h00 API Resources (ContextIssueResource)
```

**Livrables J2:** API complète et testable ✅

---

### JOUR 3 (Mercredi) - ENDPOINTS AVANCÉS

**Matin (4h):**

```
9h00-9h15   Daily stand-up
9h15-10h30  PATCH /issues/{id}/toggle-major
10h30-11h30 GET /issues/swot (filtré SWOT)
11h30-13h00 GET /issues/pestel (filtré PESTEL)
```

**Après-midi (4h):**

```
14h00-15h00 ContextIssuePolicy.php (permissions)
15h00-16h00 Middleware permissions sur routes
16h00-17h00 Tests Postman complets
17h00-18h00 Documentation API
```

**Livrables J3:** API enrichie + sécurisée ✅

**🎯 DEMO mi-sprint 15h:** API fonctionnelle

---

### JOUR 4 (Jeudi) - TESTS & OPTIMISATION

**Matin (4h):**

```
9h00-9h15   Daily stand-up
9h15-11h00  Tests PHPUnit (ContextController)
11h00-12h00 Tests PHPUnit (ContextIssueController)
12h00-13h00 Optimisation queries (eager loading)
```

**Après-midi (4h):**

```
14h00-15h00 Index BDD pour performance
15h00-16h00 Fixer bugs détectés
16h00-17h00 Seeders données démo complètes
17h00-18h00 Script reset BDD pour démo
```

**Livrables J4:** Backend stable et testé ✅

---

### JOUR 5 (Vendredi) - PRODUCTION READY

**Matin (4h):**

```
9h00-9h15   Daily stand-up
9h15-11h00  Tests API finaux (Postman)
11h00-12h00 Fixer derniers bugs
12h00-13h00 Optimisations finales
```

**Après-midi (4h):**

```
14h00-15h00 Backup BDD
15h00-16h00 Migration production (si déploiement)
16h00-17h00 Documentation API (Postman + README)
17h00-18h00 🎉 DÉMO FINALE
```

**Livrables J5:** Backend production-ready ✅

---

## 📋 CHECKLIST TÂCHES

### Migrations (Jour 1)

- [ ] `2026_02_10_000001_create_analysis_categories_table.php`
  - Colonnes: id, type, code, label, timestamps
- [ ] `2026_02_10_000002_create_organization_contexts_table.php`
  - Colonnes: id, enterprise_id, title, is_active, timestamps
- [ ] `2026_02_10_000003_create_context_issues_table.php`
  - Colonnes: id, context_id, category_id, description, sentiment, is_major, impact_score, timestamps

### Modèles Eloquent (Jour 1)

- [ ] `app/Models/Context/AnalysisCategory.php`
  - Relations, fillable, casts
- [ ] `app/Models/Context/OrganizationContext.php`
  - Relations: hasMany(issues), belongsTo(enterprise)
- [ ] `app/Models/Context/ContextIssue.php`
  - Relations: belongsTo(context, category)
  - Scopes: swot(), pestel(), major()

### Seeders (Jour 1)

- [ ] `AnalysisCategorySeeder.php`
  - SWOT: Forces, Faiblesses, Opportunités, Menaces
  - PESTEL: Politique, Économique, Social, Techno, Écolo, Légal

### Controllers (Jour 2)

- [ ] `AnalysisCategoryController.php`
  - index() - Liste catégories
- [ ] `ContextController.php`
  - index(), store(), show(), update(), destroy()
- [ ] `ContextIssueController.php`
  - index(), store(), show(), update(), destroy()

### Routes API (Jour 2)

```php
// routes/api.php
Route::prefix('client-a')->middleware(['auth:sanctum'])->group(function () {
    Route::get('analysis-categories', [AnalysisCategoryController::class, 'index']);
    Route::apiResource('contexts', ContextController::class);
    Route::apiResource('contexts.issues', ContextIssueController::class);
});
```

### Endpoints Avancés (Jour 3)

- [ ] PATCH `/contexts/{id}/issues/{issueId}/toggle-major`
- [ ] GET `/contexts/{id}/issues?filter=swot`
- [ ] GET `/contexts/{id}/issues?filter=pestel`

### Tests PHPUnit (Jour 4)

- [ ] `ContextControllerTest.php`
  - test_can_list_contexts()
  - test_can_create_context()
  - test_can_update_context()
  - test_can_delete_context()
- [ ] `ContextIssueControllerTest.php`
  - test_can_list_issues()
  - test_can_create_issue()
  - test_can_toggle_major_issue()
  - test_can_filter_swot_issues()

### Documentation (Jour 5)

- [ ] Collection Postman complète
- [ ] README API avec exemples
- [ ] Schéma ERD (optionnel)

---

## 🛠️ COMMANDES UTILES

### Migrations

```bash
cd backend
php artisan make:migration create_analysis_categories_table
php artisan migrate
php artisan migrate:rollback
php artisan db:seed --class=AnalysisCategorySeeder
```

### Modèles

```bash
php artisan make:model Context/AnalysisCategory
php artisan make:model Context/OrganizationContext
php artisan make:model Context/ContextIssue
```

### Controllers

```bash
php artisan make:controller API/ClientA/ContextController --api
php artisan make:controller API/ClientA/ContextIssueController --api
```

### Tests

```bash
php artisan make:test ContextControllerTest
php artisan test
php artisan test --filter ContextControllerTest
php artisan test --coverage
```

### Lancer serveur

```bash
php artisan serve
# API accessible sur http://localhost:8000
```

---

## 📚 RESSOURCES TECHNIQUES

### Documentation

- Laravel 11: https://laravel.com/docs/11.x
- Eloquent ORM: https://laravel.com/docs/11.x/eloquent
- API Resources: https://laravel.com/docs/11.x/eloquent-resources
- Testing: https://laravel.com/docs/11.x/testing

### Schéma BDD

Voir: `/Logi/SCHÉMA BASE DE DONNÉES COMPLET - L.sql` (lignes 37-120)

### Postman

Importer: `BestQHSE_Audits_Module.postman_collection.json` (existant)

---

## 🤝 COORDINATION AVEC L'ÉQUIPE

### Dépendances

**DEV 1 (Frontend):**

- 📤 Fournir API `/contexts` dès J2 matin
- 📤 Fournir API `/issues` dès J2 après-midi

**DEV 3 (Full-Stack):**

- 🤝 Collaboration tests API (J2-J3)
- 📤 Fournir collection Postman (J2)

### Communication

- **Slack:** Annoncer quand API prête
- **Daily:** Bloquer si besoin aide
- **Demo mi-sprint:** Mercredi 15h (montrer Postman)

---

## ✅ DEFINITION OF DONE (DoD)

Une API est "Done" quand:

- [ ] Migration créée et testée
- [ ] Modèle Eloquent avec relations
- [ ] Controller avec validation FormRequest
- [ ] Routes définies dans `api.php`
- [ ] Test Postman OK (200/201)
- [ ] Test PHPUnit écrit (si temps)
- [ ] Policy permissions (si sensible)
- [ ] Code review par DEV 3
- [ ] Merged dans `develop`

---

## �� KPIs PERSONNELS

| Métrique          | Objectif | Suivi |
| ----------------- | -------- | ----- |
| Migrations créées | 3        | \_\_  |
| Modèles créés     | 3        | \_\_  |
| Endpoints API     | 10+      | \_\_  |
| Tests PHPUnit     | > 80%    | \_\_  |
| Postman tests     | 100% OK  | \_\_  |
| Bugs critiques    | 0        | \_\_  |

---

**Bon sprint ! 🚀**
