# 🚀 SPRINT 5 JOURS - Plan Agile pour 3 Développeurs Full-Stack

**Date de démarrage:** 10 février 2026  
**Date de livraison:** 14 février 2026  
**Équipe:** 3 développeurs full-stack  
**Objectif:** MVP Module Contexte (Point 4 ISO) + Composants UI Base

---

## 📊 CAPACITÉ & SCOPE

### Capacité Disponible
- **3 développeurs** × 5 jours × 8h = **120 heures**
- **Vélocité cible:** 120 heures (vs 300h plan initial)

### Scope Réduit (MVP)
Focus sur **2 modules prioritaires** :
1. ✅ **Composants UI Base** (réutilisables)
2. ✅ **Point 4 - Contexte Organisme** (SWOT/PESTEL seulement)

**Exclu du sprint (Phase 2):**
- ❌ Point 5 (Leadership)
- ❌ Point 6 (DUERP) - sera fait après
- ❌ Point 7 (Support)
- ❌ IA Grok (optionnel si temps)
- ❌ Génération PDF (manuel pour MVP)

---

## 👥 RÉPARTITION DES RÔLES

### DEV 1 - "Frontend Lead" (Focus UI/UX)
**Responsabilités:**
- Composants UI radix-vue
- Pages Vue (SWOT, PESTEL)
- Intégration Tailwind/design
- Tests E2E (Cypress)

### DEV 2 - "Backend Lead" (Focus API/BDD)
**Responsabilités:**
- Migrations Laravel
- Modèles Eloquent
- Controllers API
- Seeders de données
- Tests API (PHPUnit)

### DEV 3 - "Full-Stack" (Support/Intégration)
**Responsabilités:**
- Stores Pinia
- Services API frontend
- Composants métier (SWOT Matrix)
- Connexion frontend ↔ backend
- Documentation

---

## 📅 PLANNING JOUR PAR JOUR

### 🗓️ JOUR 1 (Lundi) - SETUP & FONDATIONS

#### DEV 1 - Frontend Lead
**Matin (4h):**
- [ ] Installer packages npm additionnels
- [ ] Créer `/components/ui/Dialog.vue` (radix-vue)
- [ ] Créer `/components/ui/Input.vue` (radix-vue)
- [ ] Créer `/components/ui/Select.vue` (radix-vue)

**Après-midi (4h):**
- [ ] Créer `/components/ui/Tabs.vue` (radix-vue)
- [ ] Créer `/components/ui/Button.vue` (améliorer existant)
- [ ] Créer `/components/ui/Card.vue` (améliorer existant)
- [ ] Tests unitaires composants UI

**Livrables J1 DEV1:** 6 composants UI fonctionnels

---

#### DEV 2 - Backend Lead
**Matin (4h):**
- [ ] Migration `create_analysis_categories_table`
- [ ] Migration `create_organization_contexts_table`
- [ ] Migration `create_context_issues_table`
- [ ] Modèle `AnalysisCategory.php`

**Après-midi (4h):**
- [ ] Modèle `OrganizationContext.php`
- [ ] Modèle `ContextIssue.php`
- [ ] Seeder catégories SWOT/PESTEL (données de base)
- [ ] Tester migrations + seeders

**Livrables J1 DEV2:** BDD Point 4 prête + données test

---

#### DEV 3 - Full-Stack
**Matin (4h):**
- [ ] Créer `/stores/contextIssueStore.ts` (Pinia)
- [ ] Enrichir `/services/api/contextService.ts` (si besoin)
- [ ] Créer types TypeScript `/types/context.ts`
- [ ] Setup Postman collection API

**Après-midi (4h):**
- [ ] Créer `/composables/useContext.ts`
- [ ] Créer `/composables/useCategories.ts`
- [ ] Préparer structure pages `/pages/context/`
- [ ] Documentation API (README)

**Livrables J1 DEV3:** Stores + Services + Types ready

---

### 🗓️ JOUR 2 (Mardi) - BACKEND API + COMPOSANTS MÉTIER

#### DEV 1 - Frontend Lead
**Matin (4h):**
- [ ] Créer `/components/context/IssueCard.vue`
- [ ] Créer `/components/context/IssueForm.vue`
- [ ] Créer `/components/context/CategoryBadge.vue`
- [ ] Tests composants métier

**Après-midi (4h):**
- [ ] Créer `/components/shared/DataTable.vue` (améliorer existant)
- [ ] Créer `/components/shared/EmptyState.vue`
- [ ] Créer `/components/shared/LoadingSpinner.vue`
- [ ] Préparer layout pages Context

**Livrables J2 DEV1:** Composants métier Context prêts

---

#### DEV 2 - Backend Lead
**Matin (4h):**
- [ ] Controller `AnalysisCategoryController.php`
- [ ] Controller `ContextController.php` (CRUD contexts)
- [ ] Routes API `/api/client-a/analysis-categories`
- [ ] Routes API `/api/client-a/contexts`

**Après-midi (4h):**
- [ ] Controller `ContextIssueController.php` (CRUD issues)
- [ ] Routes API `/api/client-a/contexts/{id}/issues`
- [ ] Validation FormRequest (StoreContextIssueRequest)
- [ ] API Resources (ContextIssueResource)

**Livrables J2 DEV2:** API Point 4 complète et testée

---

#### DEV 3 - Full-Stack
**Matin (4h):**
- [ ] Tester API avec Postman (toutes les routes)
- [ ] Connecter `contextService.ts` aux vraies API
- [ ] Créer `/stores/categoryStore.ts` (Pinia)
- [ ] Tester stores avec données API

**Après-midi (4h):**
- [ ] Créer `/pages/context/index.vue` (dashboard module)
- [ ] Layout de base avec navigation
- [ ] Breadcrumbs + header module
- [ ] Tests d'intégration

**Livrables J2 DEV3:** API connectée + Page index Context

---

### 🗓️ JOUR 3 (Mercredi) - PAGE SWOT

#### DEV 1 - Frontend Lead
**Matin (4h):**
- [ ] Créer `/components/context/SWOTMatrix.vue` (grid 2×2)
- [ ] Créer `/components/context/SWOTQuadrant.vue`
- [ ] CSS/Tailwind pour design moderne
- [ ] Drag & drop entre quadrants (optionnel)

**Après-midi (4h):**
- [ ] Créer `/pages/context/SWOT.vue` (page complète)
- [ ] Intégrer SWOTMatrix + IssueCard
- [ ] Bouton "Ajouter enjeu" → Dialog IssueForm
- [ ] Filtres par catégorie

**Livrables J3 DEV1:** Page SWOT interactive complète

---

#### DEV 2 - Backend Lead
**Matin (4h):**
- [ ] Endpoint PATCH `/contexts/{id}/issues/{issueId}/toggle-major`
- [ ] Endpoint GET `/contexts/{id}/issues/swot` (filtré SWOT)
- [ ] Endpoint GET `/contexts/{id}/issues/pestel` (filtré PESTEL)
- [ ] Tests PHPUnit controllers

**Après-midi (4h):**
- [ ] Améliorer enrichir table `stakeholders` (si besoin)
- [ ] Policy `ContextIssuePolicy.php` (permissions)
- [ ] Middleware permissions sur routes
- [ ] Documentation API (Swagger optionnel)

**Livrables J3 DEV2:** API enrichie + Permissions

---

#### DEV 3 - Full-Stack
**Matin (4h):**
- [ ] Enrichir `contextIssueStore.ts` (actions SWOT)
- [ ] Actions: createIssue, updateIssue, deleteIssue, toggleMajor
- [ ] Getters: issuesBySentiment, issuesByCategory
- [ ] Tests stores

**Après-midi (4h):**
- [ ] Connecter SWOT.vue aux stores
- [ ] Tester CRUD complet sur page SWOT
- [ ] Gestion erreurs API
- [ ] Toasts notifications (succès/erreur)

**Livrables J3 DEV3:** SWOT.vue fonctionnel E2E

---

### 🗓️ JOUR 4 (Jeudi) - PAGE PESTEL + POLISH

#### DEV 1 - Frontend Lead
**Matin (4h):**
- [ ] Créer `/components/context/PESTELGrid.vue` (6 colonnes)
- [ ] Créer `/components/context/PESTELColumn.vue`
- [ ] CSS/Tailwind pour grid PESTEL
- [ ] Responsive mobile/tablet

**Après-midi (4h):**
- [ ] Créer `/pages/context/PESTEL.vue` (page complète)
- [ ] Intégrer PESTELGrid + IssueCard
- [ ] Formulaire ajout enjeu PESTEL
- [ ] Filtres + recherche

**Livrables J4 DEV1:** Page PESTEL complète

---

#### DEV 2 - Backend Lead
**Matin (4h):**
- [ ] Enrichir stakeholders (si temps)
- [ ] Endpoint `/application-scopes` (optionnel)
- [ ] Optimisation queries (eager loading)
- [ ] Index BDD pour performance

**Après-midi (4h):**
- [ ] Tests PHPUnit complets (>80% coverage)
- [ ] Fixer bugs détectés
- [ ] Seeders de données de démo complètes
- [ ] Script reset BDD pour démo

**Livrables J4 DEV2:** Backend stable et testé

---

#### DEV 3 - Full-Stack
**Matin (4h):**
- [ ] Connecter PESTEL.vue aux stores
- [ ] Actions PESTEL dans stores
- [ ] Tests CRUD PESTEL
- [ ] Synchronisation SWOT ↔ PESTEL

**Après-midi (4h):**
- [ ] Créer `/pages/context/Stakeholders.vue` (améliorer existant)
- [ ] Tableau parties intéressées
- [ ] CRUD basique stakeholders
- [ ] Tests E2E Cypress (SWOT + PESTEL)

**Livrables J4 DEV3:** PESTEL + Stakeholders fonctionnels

---

### 🗓️ JOUR 5 (Vendredi) - TESTS, DÉMO & DÉPLOIEMENT

#### DEV 1 - Frontend Lead
**Matin (4h):**
- [ ] Tests E2E Cypress complets (tous les scénarios)
- [ ] Fixer bugs UI détectés
- [ ] Polish CSS/animations
- [ ] Accessibilité (aria-labels, keyboard nav)

**Après-midi (4h):**
- [ ] Documentation utilisateur (README pages)
- [ ] Screenshots/vidéos démo
- [ ] Build production frontend
- [ ] Préparer présentation démo

**Livrables J5 DEV1:** Frontend testé et documenté

---

#### DEV 2 - Backend Lead
**Matin (4h):**
- [ ] Tests API finaux (Postman collection complète)
- [ ] Fixer derniers bugs
- [ ] Optimisations finales
- [ ] Logs & monitoring

**Après-midi (4h):**
- [ ] Backup BDD
- [ ] Migration production (si déploiement)
- [ ] Seeders données de démo pour présentation
- [ ] Documentation API (Postman + README)

**Livrables J5 DEV2:** Backend production-ready

---

#### DEV 3 - Full-Stack
**Matin (4h):**
- [ ] Tests d'intégration complets
- [ ] Vérifier tous les flux E2E
- [ ] Fixer bugs d'intégration
- [ ] Performance (Lighthouse > 90)

**Après-midi (4h):**
- [ ] Documentation technique complète
- [ ] Guide installation/déploiement
- [ ] Préparer données de démo
- [ ] 🎉 **DÉMO FINALE** avec l'équipe

**Livrables J5 DEV3:** Projet livré et démo

---

## 📋 USER STORIES PRIORITAIRES (MVP)

### Epic 1: Composants UI Base
**US-01:** En tant que développeur, je veux des composants UI réutilisables pour accélérer le développement  
**Critères d'acceptation:**
- [ ] Dialog, Input, Select, Tabs, Button, Card créés
- [ ] Documentation Storybook (optionnel)
- [ ] Accessible (WCAG AA)

### Epic 2: Module Contexte - SWOT
**US-02:** En tant que Responsable Qualité, je veux créer une analyse SWOT interactive pour identifier les enjeux internes/externes  
**Critères d'acceptation:**
- [ ] Matrice 2×2 (Forces, Faiblesses, Opportunités, Menaces)
- [ ] CRUD enjeux (créer, modifier, supprimer)
- [ ] Marquer enjeux majeurs
- [ ] Filtrer par catégorie
- [ ] Données persistées en BDD

**US-03:** En tant que utilisateur, je veux voir les enjeux par sentiment (favorable/défavorable) pour prioriser les actions  
**Critères d'acceptation:**
- [ ] Badge couleur par sentiment
- [ ] Compteur par quadrant
- [ ] Tri par impact

### Epic 3: Module Contexte - PESTEL
**US-04:** En tant que Responsable Qualité, je veux créer une analyse PESTEL pour identifier les facteurs macro-environnementaux  
**Critères d'acceptation:**
- [ ] 6 colonnes (Politique, Économique, Social, Techno, Écolo, Légal)
- [ ] CRUD enjeux PESTEL
- [ ] Synchronisation avec SWOT (même data model)
- [ ] Export possible (bonus)

### Epic 4: Parties Intéressées
**US-05:** En tant que Responsable Qualité, je veux gérer les parties intéressées pour répondre à ISO 9001  
**Critères d'acceptation:**
- [ ] Liste parties intéressées
- [ ] CRUD basique
- [ ] Catégorie (interne/externe)
- [ ] Niveau pertinence

---

## 🎯 BOARD KANBAN (À METTRE DANS JIRA/TRELLO)

### Backlog Sprint
```
📋 BACKLOG
├── [UI] Composants UI base (DEV1)
├── [BACK] Migrations Point 4 (DEV2)
├── [FRONT] Stores Pinia (DEV3)
├── [BACK] Controllers API (DEV2)
├── [FRONT] Page SWOT (DEV1)
├── [FRONT] Page PESTEL (DEV1)
├── [FULL] Intégration (DEV3)
└── [TEST] Tests E2E (ALL)
```

### To Do (Jour 1)
```
📝 TO DO
├── [DEV1] Dialog.vue
├── [DEV1] Input.vue
├── [DEV1] Select.vue
├── [DEV2] Migration categories
├── [DEV2] Migration contexts
├── [DEV2] Migration issues
├── [DEV3] contextIssueStore.ts
└── [DEV3] Types TypeScript
```

### In Progress
```
🔄 IN PROGRESS
├── [DEV1] Tabs.vue
└── [DEV2] Modèles Eloquent
```

### Review
```
👀 REVIEW
└── [DEV3] contextService.ts
```

### Done
```
✅ DONE
└── (vide au démarrage)
```

---

## 📊 DÉFINITION OF DONE (DoD)

### Pour une tâche frontend:
- [ ] Code écrit et testé
- [ ] Composant responsive (mobile/tablet/desktop)
- [ ] Accessible (aria-labels, keyboard nav)
- [ ] TypeScript strict (pas de `any`)
- [ ] Tests unitaires (Vitest) si composant complexe
- [ ] Code review par un autre dev
- [ ] Intégré dans la branche `develop`

### Pour une tâche backend:
- [ ] Migration créée et testée
- [ ] Modèle Eloquent avec relations
- [ ] Controller avec validation
- [ ] Routes API définies
- [ ] Test API Postman OK
- [ ] Tests PHPUnit (si temps)
- [ ] Code review
- [ ] Intégré dans `develop`

### Pour une page complète:
- [ ] CRUD fonctionnel E2E
- [ ] Gestion erreurs API
- [ ] Loading states
- [ ] Empty states
- [ ] Toasts notifications
- [ ] Tests Cypress (au moins 1 scénario)
- [ ] Documentation utilisateur
- [ ] Démo validée par PO

---

## ⚡ DAILY STAND-UP (9h00 chaque matin)

**Format (15 min max):**

**DEV 1:**
- ✅ Hier j'ai fait: ...
- 🎯 Aujourd'hui je fais: ...
- 🚧 Blocages: ...

**DEV 2:**
- ✅ Hier j'ai fait: ...
- 🎯 Aujourd'hui je fais: ...
- 🚧 Blocages: ...

**DEV 3:**
- ✅ Hier j'ai fait: ...
- 🎯 Aujourd'hui je fais: ...
- 🚧 Blocages: ...

**Actions:** Résoudre blocages immédiatement

---

## 🔄 WORKFLOW GIT

### Branches
```
main (production)
├── develop (intégration)
    ├── feature/ui-components (DEV1)
    ├── feature/backend-context (DEV2)
    └── feature/frontend-swot (DEV3)
```

### Process
1. Créer branche depuis `develop`
2. Commit réguliers (plusieurs par jour)
3. Push en fin de journée
4. PR vers `develop` quand feature terminée
5. Review par un autre dev
6. Merge dans `develop`
7. Tests d'intégration
8. Merge `develop` → `main` en fin de sprint

---

## 📈 MÉTRIQUES DE SUIVI

### Burndown Chart (à traquer)
| Jour | Heures Restantes | Heures Brûlées |
|------|------------------|----------------|
| J0   | 120h             | 0h             |
| J1   | 96h              | 24h            |
| J2   | 72h              | 48h            |
| J3   | 48h              | 72h            |
| J4   | 24h              | 96h            |
| J5   | 0h               | 120h ✅         |

### Vélocité
- **Tâches planifiées:** ~45 tâches
- **Tâches par dev/jour:** ~3 tâches
- **Tâches critiques:** 20 (must-have)
- **Tâches optionnelles:** 25 (nice-to-have)

---

## 🎯 OBJECTIFS SMART DU SPRINT

**S**pécifique: Livrer module Contexte (SWOT/PESTEL) fonctionnel  
**M**esurable: 2 pages (SWOT + PESTEL) avec CRUD complet  
**A**tteignable: 120h disponibles, scope réduit  
**R**éaliste: Équipe de 3 devs full-stack expérimentés  
**T**emporel: 5 jours (10-14 février 2026)

### Critères de succès:
- ✅ Composants UI base créés et réutilisables
- ✅ API Point 4 complète et testée
- ✅ Page SWOT fonctionnelle E2E
- ✅ Page PESTEL fonctionnelle E2E
- ✅ Démo réussie devant PO/client
- ✅ Code en production (ou staging)

---

## 🚨 RISQUES & MITIGATION

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| **Scope creep** | 🔴 Élevé | 🟡 Moyen | Daily review scope, dire NON aux features hors MVP |
| **Bug bloquant** | 🔴 Élevé | 🟡 Moyen | Tests continus, pair programming sur parties critiques |
| **Dépendances entre devs** | 🟡 Moyen | 🔴 Élevé | Communication Slack permanente, daily stand-up |
| **Malade/absent** | 🔴 Élevé | 🟢 Faible | Documentation continue, partage de connaissances |
| **Problème technique** | 🟡 Moyen | 🟡 Moyen | Stack connue (Vue/Laravel), POC jour 1 |

**Stratégie:** Livrer MVP minimal jour 4, jour 5 = buffer + polish

---

## 📞 COMMUNICATION

### Slack Channels
- `#sprint-context-iso` - Communication équipe
- `#tech-support` - Questions techniques
- `#daily-standup` - Résumés quotidiens

### Réunions
- **Daily Stand-up:** 9h00 (15 min)
- **Demo intermédiaire:** Mercredi 15h (30 min)
- **Retrospective:** Vendredi 17h (1h)

### Disponibilité
- **PO/Product:** Disponible sur Slack 9h-18h
- **Tech Lead:** Reviews PR en continu
- **QA:** Tests dès feature terminée

---

## ✅ CHECKLIST DÉMARRAGE SPRINT (Lundi matin)

### Tous les devs
- [ ] Pull dernière version `develop`
- [ ] Installer packages npm (`npm install`)
- [ ] Lancer backend Laravel (`php artisan serve`)
- [ ] Lancer frontend Vite (`npm run dev`)
- [ ] Vérifier BDD connectée
- [ ] Créer sa branche feature
- [ ] Lire plan sprint complet
- [ ] Daily stand-up 9h00

### DEV 1
- [ ] Vérifier radix-vue installé
- [ ] Créer dossier `/components/ui/`
- [ ] Préparer Tailwind config

### DEV 2
- [ ] Créer dossier `/app/Models/Context/`
- [ ] Préparer migrations vierges
- [ ] Postman collection setup

### DEV 3
- [ ] Créer dossier `/stores/`
- [ ] Vérifier Pinia setup
- [ ] Préparer types TypeScript

---

## 🎉 LIVRABLE FINAL (Vendredi 17h)

### Démo
1. **Présentation** (5 min) - Contexte projet
2. **Démo SWOT** (10 min) - CRUD complet live
3. **Démo PESTEL** (10 min) - Fonctionnalités
4. **Démo Stakeholders** (5 min) - Si temps
5. **Architecture technique** (5 min) - Code review rapide
6. **Q&A** (10 min)

### Artefacts à livrer
- [ ] Code source sur Git (branche `main`)
- [ ] BDD avec données de démo
- [ ] Documentation technique (README)
- [ ] Documentation utilisateur (guide)
- [ ] Collection Postman API
- [ ] Tests automatisés (Cypress + PHPUnit)
- [ ] Screenshots/vidéos démo

---

**Prêts pour le sprint ? Let's go ! 🚀**

**Document créé le:** 9 février 2026  
**Sprint:** 10-14 février 2026  
**Équipe:** 3 développeurs full-stack  
**Objectif:** MVP Module Contexte ISO 9001
