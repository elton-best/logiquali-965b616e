# 🚀 SPRINT 5 JOURS - RÉSUMÉ EXÉCUTIF

**Projet:** Intégration design Logi → Client A (Vue.js)  
**Dates:** 10-14 février 2026 (Lundi → Vendredi)  
**Équipe:** 3 développeurs full-stack  
**Capacité:** 120 heures (3 × 5 jours × 8h)

---

## 🎯 OBJECTIF SPRINT

Livrer un **MVP fonctionnel** du module **Point 4 - Contexte de l'organisation** avec :

- ✅ Composants UI modernes (radix-vue + Tailwind)
- ✅ Page SWOT complète (4 quadrants interactifs)
- ✅ Page PESTEL complète (6 colonnes filtrables)
- ✅ API REST Laravel sécurisée
- ✅ Tests E2E Cypress fonctionnels

**🚨 CONTRAINTE:** Démo finale **vendredi 17h00** ⏰

---

## 👥 RÉPARTITION RÔLES

| Dev       | Rôle          | Focus Principal           | Charge |
| --------- | ------------- | ------------------------- | ------ |
| **DEV 1** | Frontend Lead | UI/UX + Composants Vue.js | 40h    |
| **DEV 2** | Backend Lead  | API Laravel + BDD         | 40h    |
| **DEV 3** | Full-Stack    | Intégration + Tests       | 40h    |

---

## 📦 LIVRABLES FINAUX

### 1️⃣ Composants UI (DEV 1)

- **Base UI:** Dialog, Input, Select, Tabs, Button, Card
- **Métier:** IssueCard, IssueForm, CategoryBadge
- **Layouts:** DataTable, EmptyState, LoadingSpinner

### 2️⃣ Pages Fonctionnelles (DEV 1 + DEV 3)

- **SWOT.vue** - Matrice 2×2 (Forces, Faiblesses, Opportunités, Menaces)
- **PESTEL.vue** - Grid 6 colonnes (Politique, Économique, Social, Techno, Écolo, Légal)
- **Stakeholders.vue** - Gestion parties intéressées (optionnel J5)

### 3️⃣ Backend API (DEV 2)

**Migrations:**

- `analysis_categories` - Catégories SWOT/PESTEL
- `organization_contexts` - Contextes organisation
- `context_issues` - Enjeux/issues

**Endpoints:**

```
GET    /api/client-a/analysis-categories
GET    /api/client-a/contexts
POST   /api/client-a/contexts
GET    /api/client-a/contexts/{id}
PUT    /api/client-a/contexts/{id}
DELETE /api/client-a/contexts/{id}
GET    /api/client-a/contexts/{id}/issues
POST   /api/client-a/contexts/{id}/issues
PUT    /api/client-a/issues/{id}
DELETE /api/client-a/issues/{id}
PATCH  /api/client-a/issues/{id}/toggle-major
```

### 4️⃣ Tests & Qualité (DEV 3)

- **Cypress E2E:** 5+ scénarios (CRUD SWOT/PESTEL)
- **PHPUnit:** Tests backend (> 80% coverage)
- **Lighthouse:** Score > 90
- **Accessibilité:** WCAG AA ✅

---

## 📅 PLANNING JOUR PAR JOUR

### 🔵 JOUR 1 (Lundi) - FONDATIONS

**Matin:**

- 9h00: Daily stand-up #1
- 9h15-13h: Setup + Composants UI base + Migrations BDD

**Après-midi:**

- 14h-18h: Composants UI finalisés + Modèles Eloquent + Stores Pinia

**🎯 Livrables J1:**

- ✅ 6 composants UI testés (DEV 1)
- ✅ BDD Point 4 migrée (DEV 2)
- ✅ Stores + Services ready (DEV 3)

---

### 🟢 JOUR 2 (Mardi) - API & COMPOSANTS MÉTIER

**Matin:**

- 9h00: Daily stand-up #2
- 9h15-13h: Composants métier + Controllers API + Tests Postman

**Après-midi:**

- 14h-18h: Layout pages + Routes API + Page index

**🎯 Livrables J2:**

- ✅ Composants métier prêts (DEV 1)
- ✅ API complète testable (DEV 2)
- ✅ API connectée frontend (DEV 3)

---

### 🟡 JOUR 3 (Mercredi) - PAGE SWOT

**Matin:**

- 9h00: Daily stand-up #3
- 9h15-13h: SWOTMatrix + Endpoints avancés + Intégration stores

**Après-midi:**

- 14h-15h: Intégration SWOT finale
- **15h00: 🎯 DEMO MI-SPRINT** (Montrer SWOT fonctionnel)
- 16h-18h: Tests + corrections bugs

**🎯 Livrables J3:**

- ✅ Page SWOT interactive (DEV 1)
- ✅ API enrichie + sécurisée (DEV 2)
- ✅ SWOT E2E fonctionnel (DEV 3)

---

### 🟠 JOUR 4 (Jeudi) - PAGE PESTEL

**Matin:**

- 9h00: Daily stand-up #4
- 9h15-13h: PESTELGrid + Tests PHPUnit + Intégration PESTEL

**Après-midi:**

- 14h-18h: Page complète + Optimisation + Tests E2E

**🎯 Livrables J4:**

- ✅ Page PESTEL complète (DEV 1)
- ✅ Backend stable testé (DEV 2)
- ✅ PESTEL + Stakeholders (DEV 3)

---

### 🔴 JOUR 5 (Vendredi) - TESTS & DÉMO

**Matin:**

- 9h00: Daily stand-up #5
- 9h15-13h: Tests E2E + Tests API finaux + Tests intégration

**Après-midi:**

- 14h-16h: Polish + Documentation + Build production
- 16h-17h: Préparation démo
- **17h00: 🎉 DÉMO FINALE**

**🎯 Livrables J5:**

- ✅ Frontend testé livré (DEV 1)
- ✅ Backend production-ready (DEV 2)
- ✅ Projet intégré et livré (DEV 3)

---

## 📊 MÉTRIQUES DE SUCCÈS

| Indicateur          | Objectif     | Critique |
| ------------------- | ------------ | -------- |
| Composants UI créés | 12+          | ✅       |
| Endpoints API       | 10+          | ✅       |
| Pages complètes     | 2+           | ✅       |
| Tests E2E Cypress   | 5+ scénarios | ✅       |
| Coverage PHPUnit    | > 80%        | ⚠️       |
| Score Lighthouse    | > 90         | 🟢       |
| Bugs critiques      | 0            | ✅       |
| Démo validée client | Oui          | ✅       |

---

## 🛠️ STACK TECHNIQUE

### Frontend

- **Framework:** Vue.js 3.5.21 + TypeScript
- **UI:** radix-vue 1.9.17 + Tailwind CSS 4.1 + Vuetify 3.10
- **State:** Pinia 3.0
- **Tests:** Cypress (E2E) + Vitest (unitaires)

### Backend

- **Framework:** Laravel 11
- **BDD:** PostgreSQL 17
- **Auth:** Sanctum
- **Tests:** PHPUnit

### DevOps

- **Versioning:** Git (branches: main → develop → feature/\*)
- **API Testing:** Postman
- **Build:** Vite 6

---

## 📁 FICHIERS CRÉÉS

### Documentation (Jour 0)

```
✅ SPRINT_5_JOURS_PLAN_AGILE.md      (17 KB)
✅ DEV1_FRONTEND_LEAD_TASKS.md       (Checklist DEV 1)
✅ DEV2_BACKEND_LEAD_TASKS.md        (Checklist DEV 2)
✅ DEV3_FULLSTACK_TASKS.md           (Checklist DEV 3)
✅ RESUME_SPRINT_5_JOURS.md          (Ce fichier)
```

### Code (Jour 1-5)

```
frontend/src/
├── components/ui/
│   ├── Dialog.vue
│   ├── Input.vue
│   ├── Select.vue
│   ├── Tabs.vue
│   ├── Button.vue
│   └── Card.vue
├── components/context/
│   ├── IssueCard.vue
│   ├── IssueForm.vue
│   ├── CategoryBadge.vue
│   ├── SWOTMatrix.vue
│   ├── SWOTQuadrant.vue
│   ├── PESTELGrid.vue
│   └── PESTELColumn.vue
├── pages/context/
│   ├── index.vue
│   ├── SWOT.vue
│   ├── PESTEL.vue
│   └── Stakeholders.vue
├── stores/
│   ├── contextIssueStore.ts
│   └── categoryStore.ts
├── composables/
│   ├── useContext.ts
│   └── useCategories.ts
└── services/api/
    └── contextService.ts

backend/
├── database/migrations/
│   ├── 2026_02_10_000001_create_analysis_categories_table.php
│   ├── 2026_02_10_000002_create_organization_contexts_table.php
│   └── 2026_02_10_000003_create_context_issues_table.php
├── database/seeders/
│   └── AnalysisCategorySeeder.php
├── app/Models/Context/
│   ├── AnalysisCategory.php
│   ├── OrganizationContext.php
│   └── ContextIssue.php
└── app/Http/Controllers/API/ClientA/
    ├── AnalysisCategoryController.php
    ├── ContextController.php
    └── ContextIssueController.php
```

---

## 🚦 RISQUES & MITIGATION

| Risque           | Impact      | Probabilité | Mitigation                   |
| ---------------- | ----------- | ----------- | ---------------------------- |
| API pas prête J3 | 🔴 Élevé    | Moyen       | DEV 2 priorité absolue J1-J2 |
| Bugs intégration | 🟡 Moyen    | Élevé       | Daily sync + tests fréquents |
| Scope creep      | 🔴 Élevé    | Moyen       | **PAS de features hors MVP** |
| Dev malade       | 🟡 Moyen    | Faible      | Cross-training J1-J2         |
| Démo échoue      | 🔴 Critique | Faible      | Répétition démo J5 matin     |

**🚨 Règle d'or:** Si blocage > 30min → Demander aide équipe

---

## ✅ CRITÈRES D'ACCEPTATION SPRINT

### Must-Have (Critique pour démo)

- [x] BDD migrée avec données de test
- [x] API REST fonctionnelle (testée Postman)
- [x] Page SWOT avec CRUD complet
- [x] Page PESTEL avec CRUD complet
- [x] Au moins 1 test E2E fonctionnel
- [x] Démo sans crash

### Should-Have (Important)

- [x] 6+ composants UI réutilisables
- [x] Gestion erreurs + loading states
- [x] Permissions/sécurité API
- [x] Tests PHPUnit backend
- [x] Score Lighthouse > 90

### Could-Have (Bonus)

- [ ] Page Stakeholders
- [ ] Export PDF
- [ ] Filtres avancés
- [ ] Animations CSS

### Won't-Have (Phase 2)

- ❌ Points ISO 5-7
- ❌ IA Grok suggestions
- ❌ Multi-langue i18n
- ❌ Dark mode

---

## 📞 COMMUNICATION

### Daily Stand-up (15 min)

**Timing:** Chaque jour 9h00-9h15

**Format 3 questions:**

1. Qu'ai-je fait hier ?
2. Que vais-je faire aujourd'hui ?
3. Ai-je des blocages ?

### Démos

- **Mi-sprint:** Mercredi 15h (30 min)
- **Finale:** Vendredi 17h (1h)

### Code Reviews

- **Fréquence:** Avant chaque merge
- **Reviewers:** Au moins 1 autre dev
- **Timing:** < 2h délai réponse

---

## 🔥 COMMANDES RAPIDES

### Setup projet (Jour 1)

```bash
# Frontend
cd frontend
npm install @headlessui/vue @vueuse/core @vueuse/motion vee-validate yup
npm run dev

# Backend
cd backend
php artisan migrate
php artisan db:seed --class=AnalysisCategorySeeder
php artisan serve
```

### Tests

```bash
# Frontend
npm run test              # Vitest
npm run test:e2e         # Cypress

# Backend
php artisan test
php artisan test --coverage
```

### Git workflow

```bash
# Créer feature branch
git checkout -b feature/ui-components

# Daily merge
git checkout develop
git pull origin develop
git merge feature/ui-components
git push origin develop
```

---

## 📖 DOCUMENTATION RÉFÉRENCES

| Document                                      | Taille      | Description         |
| --------------------------------------------- | ----------- | ------------------- |
| `SPRINT_5_JOURS_PLAN_AGILE.md`                | 17 KB       | Plan complet sprint |
| `PLAN_INTEGRATION_VUE_LOGI.md`                | 11 KB       | Roadmap 10 semaines |
| `ANALYSE_INTEGRATION_LOGI_CLIENTA.md`         | 28 KB       | Analyse technique   |
| `Logi/SCHÉMA BASE DE DONNÉES COMPLET - L.sql` | 1313 lignes | Schéma BDD          |

---

## 🎉 SUCCÈS = DÉMO VENDREDI 17H

**Scénario démo (5 min):**

1. Montrer page SWOT vide
2. Créer 2 enjeux (Force + Menace)
3. Marquer 1 enjeu "majeur"
4. Basculer vers page PESTEL
5. Créer 1 enjeu Politique
6. Filtrer par catégorie
7. ✅ Valider que tout fonctionne E2E

**Question finale:** "Votre organisation est-elle prête à déployer ce MVP ?" 🚀

---

**Créé le:** 9 février 2026  
**Mis à jour:** 9 février 2026  
**Version:** 1.0
