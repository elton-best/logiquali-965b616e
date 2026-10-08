# 📚 INDEX - SPRINT 5 JOURS

**Sprint:** Intégration design Logi → Client A (Vue.js)  
**Dates:** 10-14 février 2026  
**Équipe:** 3 développeurs full-stack

---

## 🚀 DÉMARRAGE RAPIDE

**Nouveau sur le projet ?** Lisez dans cet ordre :

1. 📖 [RESUME_SPRINT_5_JOURS.md](#1-résumé-sprint) - Vue d'ensemble (5 min)
2. 👤 Votre checklist personnelle selon votre rôle (10 min)
   - [DEV1_FRONTEND_LEAD_TASKS.md](#2-checklists-par-développeur)
   - [DEV2_BACKEND_LEAD_TASKS.md](#2-checklists-par-développeur)
   - [DEV3_FULLSTACK_TASKS.md](#2-checklists-par-développeur)
3. 📋 [KANBAN_SPRINT_5_JOURS.md](#3-kanban-board) - Suivre les tâches

**En cours de sprint ?** Consultez :

- 📋 [KANBAN_SPRINT_5_JOURS.md](#3-kanban-board) - Mettre à jour vos tâches
- 📄 [SPRINT_5_JOURS_PLAN_AGILE.md](#4-plan-sprint-détaillé) - Détails techniques

---

## 📁 FICHIERS DU SPRINT

### 1️⃣ RÉSUMÉ SPRINT

**Fichier:** `RESUME_SPRINT_5_JOURS.md` (10 KB)  
**Audience:** Tous (Product Owner, Scrum Master, Devs)  
**Contenu:**

- 🎯 Objectifs du sprint
- 👥 Répartition des rôles
- 📦 Livrables finaux
- 📅 Planning jour par jour
- 📊 Métriques de succès
- 🛠️ Stack technique
- 🚦 Risques & mitigation

**Quand lire :** Avant de commencer le sprint, pour avoir la vue d'ensemble.

---

### 2️⃣ CHECKLISTS PAR DÉVELOPPEUR

#### DEV 1 - Frontend Lead

**Fichier:** `DEV1_FRONTEND_LEAD_TASKS.md`  
**Rôle:** Frontend Lead (UI/UX)  
**Charge:** 40h (8h/jour × 5 jours)  
**Focus:** Composants Vue.js + Pages SWOT/PESTEL  
**Contenu:**

- 🎯 Objectifs personnels (12+ composants, 2 pages)
- 📅 Planning détaillé heure par heure
- 📋 Checklist avec cases à cocher
- 🛠️ Commandes npm utiles
- 📚 Ressources (radix-vue, Tailwind, Cypress)
- 🤝 Coordination avec DEV 2/3
- ✅ Definition of Done

**Livrables clés:**

- Dialog.vue, Input.vue, Select.vue, Tabs.vue
- IssueCard.vue, IssueForm.vue, CategoryBadge.vue
- SWOTMatrix.vue, PESTELGrid.vue
- Tests Cypress E2E

---

#### DEV 2 - Backend Lead

**Fichier:** `DEV2_BACKEND_LEAD_TASKS.md`  
**Rôle:** Backend Lead (API/BDD)  
**Charge:** 40h (8h/jour × 5 jours)  
**Focus:** Laravel API + PostgreSQL  
**Contenu:**

- 🎯 Objectifs personnels (3 migrations, 10+ endpoints)
- 📅 Planning détaillé heure par heure
- 📋 Checklist migrations/modèles/controllers
- 🛠️ Commandes artisan utiles
- 📚 Ressources Laravel 11
- 🤝 Coordination avec DEV 1/3
- ✅ Definition of Done

**Livrables clés:**

- Migrations: analysis_categories, organization_contexts, context_issues
- Modèles Eloquent: AnalysisCategory, OrganizationContext, ContextIssue
- Controllers: AnalysisCategoryController, ContextController, ContextIssueController
- 10+ endpoints API REST
- Tests PHPUnit > 80% coverage

---

#### DEV 3 - Full-Stack

**Fichier:** `DEV3_FULLSTACK_TASKS.md`  
**Rôle:** Full-Stack (Intégration)  
**Charge:** 40h (8h/jour × 5 jours)  
**Focus:** Stores Pinia + Intégration frontend ↔ backend  
**Contenu:**

- 🎯 Objectifs personnels (2 stores, intégration E2E)
- 📅 Planning détaillé heure par heure
- 📋 Checklist stores/services/tests
- 🛠️ Commandes Pinia/Cypress
- 📚 Ressources Pinia, VueUse
- 🤝 Coordination avec DEV 1/2
- ✅ Definition of Done

**Livrables clés:**

- contextIssueStore.ts, categoryStore.ts (Pinia)
- useContext.ts, useCategories.ts (composables)
- Connecter SWOT.vue + PESTEL.vue aux API
- Tests E2E Cypress (5+ scénarios)
- Documentation technique

---

### 3️⃣ KANBAN BOARD

**Fichier:** `KANBAN_SPRINT_5_JOURS.md`  
**Audience:** Tous les développeurs  
**Contenu:**

- 🔴 TODO (52 tâches initiales)
- 🟡 IN PROGRESS (tâches en cours)
- 🟢 DONE (tâches terminées)
- 📊 Métriques vélocité
- 📈 Burndown chart
- 🔄 Workflow Git
- 🚨 Risques identifiés

**Mise à jour:** 2× par jour (matin après daily, soir avant fin journée)

**Utilisation:**

```bash
# Déplacer une tâche TODO → IN PROGRESS
1. Ouvrir KANBAN_SPRINT_5_JOURS.md
2. Déplacer ligne de "TODO" vers "IN PROGRESS"
3. Commit + push

# Déplacer une tâche IN PROGRESS → DONE
1. Cocher la case [x]
2. Déplacer ligne vers section "DONE"
3. Mettre à jour % complétion
```

---

### 4️⃣ PLAN SPRINT DÉTAILLÉ

**Fichier:** `SPRINT_5_JOURS_PLAN_AGILE.md` (17 KB)  
**Audience:** Scrum Master, Product Owner, Devs (référence)  
**Contenu:**

- 📝 User stories complètes
- 🎯 Objectifs MVP vs hors-scope
- 📅 Planning détaillé jour par jour
- 👥 Répartition tâches par dev
- 🏁 Critères d'acceptation
- 📊 Métriques & KPIs
- 🚧 Risques & plan de contingence
- 📞 Format daily stand-up
- 🎉 Scénario démo finale

**Quand consulter :** Pour détails techniques, arbitrage scope, questions architecturales.

---

## 📊 DOCUMENTATION TECHNIQUE (Contexte)

### Analyse & Recommandations (Phase 0)

Ces documents ont servi à préparer le sprint. Utiles pour contexte mais pas nécessaires pendant le sprint.

| Document                                    | Taille | Description                             |
| ------------------------------------------- | ------ | --------------------------------------- |
| `ANALYSE_INTEGRATION_LOGI_CLIENTA.md`       | 28 KB  | Analyse complète des 3 projets          |
| `RECOMMANDATIONS_TECHNIQUES_INTEGRATION.md` | 40 KB  | Recommandations React (obsolète)        |
| `PLAN_INTEGRATION_VUE_LOGI.md`              | 11 KB  | Roadmap 10 semaines (vision long-terme) |
| `QUESTIONS_DECISIONS_CLES.md`               | 14 KB  | 15 questions stratégiques               |
| `RESUME_EXECUTIF_INTEGRATION.md`            | 9 KB   | Résumé pour stakeholders                |

### Schéma BDD

**Fichier:** `Logi/SCHÉMA BASE DE DONNÉES COMPLET - L.sql` (1313 lignes)  
**Description:** Source de vérité pour le schéma de base de données

**Tables Point 4 (lignes 37-120):**

- `analysis_categories` - Catégories SWOT/PESTEL
- `organization_contexts` - Contextes organisation
- `context_issues` - Enjeux/issues
- `stakeholders` - parties intéressées

---

## 🛠️ COMMANDES ESSENTIELLES

### Setup initial (Jour 1)

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

# Git
git checkout -b feature/ui-components  # DEV 1
git checkout -b feature/backend-context  # DEV 2
git checkout -b feature/frontend-swot  # DEV 3
```

### Tests

```bash
# Frontend
npm run test              # Vitest (unitaires)
npm run test:e2e         # Cypress (E2E)
npm run test:coverage    # Coverage

# Backend
php artisan test
php artisan test --filter ContextControllerTest
php artisan test --coverage
```

### Git workflow quotidien

```bash
# Matin
git checkout develop
git pull origin develop
git checkout feature/my-feature
git merge develop

# Soir
git add .
git commit -m "feat: implement IssueCard component"
git push origin feature/my-feature
# Créer PR vers develop
```

---

## 📅 CALENDRIER SPRINT

| Jour   | Date   | Événements                                    | Documents clés                     |
| ------ | ------ | --------------------------------------------- | ---------------------------------- |
| **J0** | 9 fév  | 🛠️ Préparation sprint                         | Tous les INDEX, CHECKLISTS, KANBAN |
| **J1** | 10 fév | 🔵 Daily #1 (9h00)                            | DEV1_TASKS, DEV2_TASKS, DEV3_TASKS |
| **J2** | 11 fév | 🟢 Daily #2 (9h00)                            | KANBAN (mise à jour)               |
| **J3** | 12 fév | 🟡 Daily #3 (9h00)<br>🎯 DEMO mi-sprint (15h) | KANBAN + slides démo               |
| **J4** | 13 fév | 🟠 Daily #4 (9h00)                            | KANBAN (mise à jour)               |
| **J5** | 14 fév | 🔴 Daily #5 (9h00)<br>🎉 DEMO finale (17h)    | SPRINT_PLAN + démo live            |

---

## 🎯 CHECKLIST DÉMARRAGE (Jour 1 matin)

### Avant le daily (8h00-9h00)

- [ ] Lire `RESUME_SPRINT_5_JOURS.md`
- [ ] Lire votre checklist personnelle (DEV1/DEV2/DEV3)
- [ ] Cloner repo + checkout branche develop
- [ ] Vérifier environnement dev fonctionne
  - [ ] Frontend: `npm run dev` OK
  - [ ] Backend: `php artisan serve` OK
  - [ ] BDD: connexion PostgreSQL OK

### Pendant le daily (9h00-9h15)

- [ ] Présenter ce que vous allez faire aujourd'hui
- [ ] Signaler si blocage environnement

### Après le daily (9h15-10h00)

- [ ] Créer votre feature branch Git
- [ ] Installer packages supplémentaires (si DEV 1)
- [ ] Démarrer première tâche du Jour 1

---

## 📞 CONTACTS & COMMUNICATION

### Daily Stand-up

- **Timing:** 9h00-9h15 (15 min max)
- **Format:** 3 questions par dev
- **Lieu:** Visio ou présentiel

### Slack

- **Channel:** `#sprint-integration-logi`
- **Utilisation:**
  - Annoncer quand API prête (DEV 2)
  - Blocker critique (tous)
  - Demande code review (tous)
  - Merge conflicts (tous)

### Code Reviews

- **Timing:** < 2h délai réponse
- **Reviewers:** Au moins 1 autre dev
- **Outil:** GitHub Pull Requests

---

## 🏆 CRITÈRES DE SUCCÈS SPRINT

### Must-Have (Critique)

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

---

## 🚨 EN CAS DE PROBLÈME

### Blocage technique (> 30min)

1. Chercher dans documentation (INDEX, SPRINT_PLAN)
2. Demander sur Slack `#sprint-integration-logi`
3. Escalader au daily si critique

### Retard sur planning

1. Vérifier KANBAN - ajuster priorités
2. Informer équipe au daily
3. Négocier scope avec Product Owner

### Merge conflict

1. `git pull origin develop`
2. Résoudre conflits localement
3. Tester que ça fonctionne
4. Commit + push

---

## 📚 RESSOURCES EXTERNES

### Documentation officielle

- **Vue.js 3:** https://vuejs.org/guide/
- **radix-vue:** https://www.radix-vue.com/
- **Tailwind CSS:** https://tailwindcss.com/docs
- **Pinia:** https://pinia.vuejs.org/
- **Laravel 11:** https://laravel.com/docs/11.x
- **Cypress:** https://docs.cypress.io/

### Design system (référence)

- **Figma:** `Logi/SaaS QHSE Platform Design` (fichiers React à adapter)

---

## ✅ NAVIGATION RAPIDE

| Besoin                   | Document                                                |
| ------------------------ | ------------------------------------------------------- |
| 📖 Vue d'ensemble sprint | [RESUME_SPRINT_5_JOURS.md](#1-résumé-sprint)            |
| 👤 Mes tâches (DEV 1)    | [DEV1_FRONTEND_LEAD_TASKS.md](#dev-1---frontend-lead)   |
| 👤 Mes tâches (DEV 2)    | [DEV2_BACKEND_LEAD_TASKS.md](#dev-2---backend-lead)     |
| 👤 Mes tâches (DEV 3)    | [DEV3_FULLSTACK_TASKS.md](#dev-3---full-stack)          |
| 📋 Suivre les tâches     | [KANBAN_SPRINT_5_JOURS.md](#3-kanban-board)             |
| 📄 Détails techniques    | [SPRINT_5_JOURS_PLAN_AGILE.md](#4-plan-sprint-détaillé) |
| 🗄️ Schéma BDD            | `Logi/SCHÉMA BASE DE DONNÉES COMPLET - L.sql`           |
| 🎨 Design                | `Logi/SaaS QHSE Platform Design/`                       |

---

**Créé le:** 9 février 2026  
**Mis à jour:** 9 février 2026  
**Version:** 1.0

**🚀 Bon sprint !**
