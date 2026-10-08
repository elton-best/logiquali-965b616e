# 📋 KANBAN BOARD - SPRINT 5 JOURS

**Sprint:** 10-14 février 2026  
**Dernière mise à jour:** 9 février 2026 - 00h00  

---

## 🔴 TODO (Non commencé)

### DEV 1 - Frontend Lead
- [ ] **[J1]** Installer packages npm (@headlessui/vue, @vueuse/core, vee-validate, yup)
- [ ] **[J1]** Dialog.vue (radix-vue)
- [ ] **[J1]** Input.vue + Select.vue
- [ ] **[J1]** Tabs.vue
- [ ] **[J1]** Améliorer Button.vue + Card.vue
- [ ] **[J2]** IssueCard.vue
- [ ] **[J2]** IssueForm.vue
- [ ] **[J2]** CategoryBadge.vue
- [ ] **[J2]** DataTable.vue amélioration
- [ ] **[J2]** EmptyState.vue + LoadingSpinner.vue
- [ ] **[J3]** SWOTMatrix.vue (grid 2×2)
- [ ] **[J3]** SWOTQuadrant.vue
- [ ] **[J3]** SWOT.vue page complète
- [ ] **[J4]** PESTELGrid.vue (6 colonnes)
- [ ] **[J4]** PESTELColumn.vue
- [ ] **[J4]** PESTEL.vue page complète
- [ ] **[J5]** Tests Cypress E2E (5+ scénarios)
- [ ] **[J5]** Accessibilité WCAG AA
- [ ] **[J5]** Build production

### DEV 2 - Backend Lead
- [ ] **[J1]** Migration create_analysis_categories_table
- [ ] **[J1]** Migration create_organization_contexts_table
- [ ] **[J1]** Migration create_context_issues_table
- [ ] **[J1]** Modèle AnalysisCategory.php
- [ ] **[J1]** Modèle OrganizationContext.php
- [ ] **[J1]** Modèle ContextIssue.php
- [ ] **[J1]** Seeder catégories SWOT/PESTEL
- [ ] **[J2]** AnalysisCategoryController.php
- [ ] **[J2]** ContextController.php (CRUD)
- [ ] **[J2]** Routes API /analysis-categories + /contexts
- [ ] **[J2]** ContextIssueController.php (CRUD)
- [ ] **[J2]** Routes API /contexts/{id}/issues
- [ ] **[J2]** StoreContextIssueRequest validation
- [ ] **[J3]** PATCH /issues/{id}/toggle-major
- [ ] **[J3]** GET /issues/swot (filtré)
- [ ] **[J3]** GET /issues/pestel (filtré)
- [ ] **[J3]** ContextIssuePolicy.php
- [ ] **[J4]** Tests PHPUnit (ContextController)
- [ ] **[J4]** Tests PHPUnit (ContextIssueController)
- [ ] **[J4]** Optimisation queries (eager loading)
- [ ] **[J5]** Tests API finaux (Postman)
- [ ] **[J5]** Documentation API

### DEV 3 - Full-Stack
- [ ] **[J1]** contextIssueStore.ts (Pinia)
- [ ] **[J1]** Enrichir contextService.ts
- [ ] **[J1]** Types TypeScript (/types/context.ts)
- [ ] **[J1]** Setup Postman collection
- [ ] **[J1]** useContext.ts (composable)
- [ ] **[J1]** useCategories.ts (composable)
- [ ] **[J2]** Tester API avec Postman
- [ ] **[J2]** Connecter contextService aux API
- [ ] **[J2]** categoryStore.ts (Pinia)
- [ ] **[J2]** /pages/context/index.vue
- [ ] **[J3]** Enrichir contextIssueStore (actions SWOT)
- [ ] **[J3]** Getters: issuesBySentiment, issuesByCategory
- [ ] **[J3]** Connecter SWOT.vue aux stores
- [ ] **[J3]** Gestion erreurs API + toasts
- [ ] **[J4]** Connecter PESTEL.vue aux stores
- [ ] **[J4]** /pages/context/Stakeholders.vue
- [ ] **[J4]** Tests E2E Cypress (SWOT + PESTEL)
- [ ] **[J5]** Tests d'intégration complets
- [ ] **[J5]** Documentation technique

---

## 🟡 IN PROGRESS (En cours)

### DEV 1
_Aucune tâche en cours_

### DEV 2
_Aucune tâche en cours_

### DEV 3
_Aucune tâche en cours_

---

## 🟢 DONE (Terminé)

### Documentation (Préparation)
- [x] ✅ Analyse complète projet (ANALYSE_INTEGRATION_LOGI_CLIENTA.md)
- [x] ✅ Plan intégration Vue.js (PLAN_INTEGRATION_VUE_LOGI.md)
- [x] ✅ Plan sprint 5 jours (SPRINT_5_JOURS_PLAN_AGILE.md)
- [x] ✅ Checklist DEV 1 (DEV1_FRONTEND_LEAD_TASKS.md)
- [x] ✅ Checklist DEV 2 (DEV2_BACKEND_LEAD_TASKS.md)
- [x] ✅ Checklist DEV 3 (DEV3_FULLSTACK_TASKS.md)
- [x] ✅ Résumé sprint (RESUME_SPRINT_5_JOURS.md)
- [x] ✅ Kanban board (KANBAN_SPRINT_5_JOURS.md)

### Code
- [x] ✅ contextService.ts (service API initial)
- [x] ✅ Arborescence frontend créée (/components/ui, /pages/context, /composables)

---

## 🔥 BLOCKERS (Blocages)

_Aucun blocage actuellement_

**🚨 Règle:** Si blocage > 30min → Escalader au daily ou sur Slack

---

## 📊 MÉTRIQUES SPRINT

### Vélocité
| Jour | TODO | In Progress | Done | % Complétion |
|------|------|-------------|------|--------------|
| **J0** | 52 | 0 | 9 | 15% |
| **J1** | __ | __ | __ | __% |
| **J2** | __ | __ | __ | __% |
| **J3** | __ | __ | __ | __% |
| **J4** | __ | __ | __ | __% |
| **J5** | 0 | 0 | 61 | 100% |

### Répartition par Dev
| Dev | Tâches assignées | Terminées | % Complétion |
|-----|------------------|-----------|--------------|
| **DEV 1** | 18 | 0 | 0% |
| **DEV 2** | 20 | 0 | 0% |
| **DEV 3** | 18 | 0 | 0% |
| **Total** | **56** | **0** | **0%** |

### Répartition par type
| Type | Total | Terminées | Restantes |
|------|-------|-----------|-----------|
| Composants UI | 12 | 0 | 12 |
| Pages | 4 | 0 | 4 |
| Migrations | 3 | 0 | 3 |
| Modèles | 3 | 0 | 3 |
| Controllers | 3 | 0 | 3 |
| Routes API | 10 | 0 | 10 |
| Stores | 2 | 0 | 2 |
| Tests | 8 | 0 | 8 |
| Documentation | 9 | 8 | 1 |

---

## 🎯 OBJECTIFS JOURNALIERS

### Jour 1 (Lundi) - FONDATIONS
**Objectif:** Setup + Fondations technique  
**Tâches:** 18 tâches  
**Critère succès:** BDD migrée + 6 composants UI + Stores créés

### Jour 2 (Mardi) - API & COMPOSANTS MÉTIER
**Objectif:** API complète + Composants métier  
**Tâches:** 13 tâches  
**Critère succès:** API testable Postman + Composants métier prêts

### Jour 3 (Mercredi) - PAGE SWOT
**Objectif:** SWOT fonctionnel E2E  
**Tâches:** 9 tâches  
**Critère succès:** Démo mi-sprint SWOT réussie ✅

### Jour 4 (Jeudi) - PAGE PESTEL
**Objectif:** PESTEL complète + Tests  
**Tâches:** 10 tâches  
**Critère succès:** PESTEL + tests PHPUnit OK

### Jour 5 (Vendredi) - DÉMO
**Objectif:** Tests finaux + Démo  
**Tâches:** 6 tâches  
**Critère succès:** Démo validée client ✅

---

## 📈 BURNDOWN CHART (à mettre à jour quotidiennement)

```
56 │ TODO
   │ ●
   │  ╲
   │   ╲
   │    ●
   │     ╲
   │      ╲
   │       ●
   │        ╲
   │         ╲
   │          ●
   │           ╲
   │            ╲
 0 │             ● DONE
   └─────────────────────
     J0 J1 J2 J3 J4 J5
```

**Légende:**
- ● = Nombre de tâches restantes
- Ligne = Trajectoire idéale

**🎯 Objectif:** Atteindre 0 tâches restantes vendredi 17h

---

## 🔄 WORKFLOW GIT

### Branches
```
main (production)
  ↓
develop (intégration)
  ↓
├── feature/ui-components (DEV 1)
├── feature/backend-context (DEV 2)
└── feature/frontend-swot (DEV 3)
```

### Règles de merge
1. **Daily:** Merge feature → develop (fin de journée)
2. **Code review:** Obligatoire avant merge
3. **Conflicts:** Résoudre immédiatement
4. **Final:** Merge develop → main (vendredi après démo)

---

## 🚨 RISQUES IDENTIFIÉS

| Risque | Status | Owner | Mitigation |
|--------|--------|-------|------------|
| API pas prête J3 | 🟡 Moyen | DEV 2 | Priorité absolue J1-J2 |
| Bugs intégration | 🟡 Moyen | DEV 3 | Tests fréquents |
| Scope creep | 🟢 Faible | Tous | Strict MVP |
| Dev malade | 🟢 Faible | - | Cross-training |

---

## 📞 COMMUNICATION

### Daily Stand-up
- **Timing:** 9h00-9h15 (15 min)
- **Format:** 3 questions (fait hier, faire aujourd'hui, blocages)

### Demo
- **Mi-sprint:** Mercredi 15h (30 min)
- **Finale:** Vendredi 17h (1h)

### Slack
- **Channel:** #sprint-integration-logi
- **Notifications:** Quand API prête, blocage critique, merge conflicts

---

## ✅ DEFINITION OF DONE (Rappel)

Une tâche est "Done" quand:
- [ ] Code écrit et testé
- [ ] Tests unitaires/E2E (si applicable)
- [ ] Documentation (si composant réutilisable)
- [ ] Code review approuvée
- [ ] Merged dans `develop`
- [ ] Déployé/fonctionnel

---

**📝 Note:** Mettre à jour ce Kanban **2 fois par jour** (matin après daily, soir avant fin journée)

**🔄 Dernière synchro:** 9 février 2026 - 00h00

