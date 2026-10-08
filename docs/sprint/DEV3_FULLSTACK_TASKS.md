# 👨‍💻 DEV 3 - FULL-STACK - Tâches Sprint 5 Jours

**Rôle:** Full-Stack (Support/Intégration)  
**Sprint:** 10-14 février 2026  
**Charge:** 40 heures (8h/jour × 5 jours)

---

## 🎯 OBJECTIFS PERSONNELS

- ✅ Créer **stores Pinia** pour module Context
- ✅ Connecter **frontend ↔ backend** (intégration)
- ✅ Développer **composants métier complexes** (SWOTMatrix)
- ✅ **Tests d'intégration** E2E

---

## 📅 PLANNING DÉTAILLÉ

### JOUR 1 (Lundi) - STORES & SERVICES

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-10h30  contextIssueStore.ts (Pinia)
10h30-11h30 Enrichir contextService.ts
11h30-13h00 Types TypeScript (/types/context.ts)
```

**Après-midi (4h):**
```
14h00-15h00 Setup Postman collection API
15h00-16h00 useContext.ts (composable)
16h00-17h00 useCategories.ts (composable)
17h00-18h00 Documentation API (README)
```

**Livrables J1:** Stores + Services ready ✅

---

### JOUR 2 (Mardi) - PAGE INDEX + TESTS API

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  Tester API avec Postman (toutes routes)
11h00-12h00 Connecter contextService aux vraies API
12h00-13h00 categoryStore.ts (Pinia)
```

**Après-midi (4h):**
```
14h00-15h30 /pages/context/index.vue (dashboard)
15h30-16h30 Layout + navigation module
16h30-17h30 Breadcrumbs + header
17h30-18h00 Tests d'intégration
```

**Livrables J2:** API connectée + Page index ✅

---

### JOUR 3 (Mercredi) - INTÉGRATION SWOT

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  Enrichir contextIssueStore (actions SWOT)
11h00-12h00 Getters: issuesBySentiment, issuesByCategory
12h00-13h00 Tests stores
```

**Après-midi (4h):**
```
14h00-15h30 Connecter SWOT.vue aux stores
15h30-16h30 Tester CRUD complet
16h30-17h30 Gestion erreurs API + toasts
17h30-18h00 Préparer démo mi-sprint
```

**Livrables J3:** SWOT.vue E2E fonctionnel ✅

**🎯 DEMO mi-sprint 15h:** Présenter SWOT complet

---

### JOUR 4 (Jeudi) - INTÉGRATION PESTEL

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  Connecter PESTEL.vue aux stores
11h00-12h00 Actions PESTEL dans stores
12h00-13h00 Tests CRUD PESTEL
```

**Après-midi (4h):**
```
14h00-15h00 Synchronisation SWOT ↔ PESTEL
15h00-16h00 /pages/context/Stakeholders.vue
16h00-17h00 CRUD stakeholders
17h00-18h00 Tests E2E Cypress (SWOT + PESTEL)
```

**Livrables J4:** PESTEL + Stakeholders OK ✅

---

### JOUR 5 (Vendredi) - INTÉGRATION FINALE

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  Tests d'intégration complets
11h00-12h00 Vérifier tous flux E2E
12h00-13h00 Fixer bugs d'intégration
```

**Après-midi (4h):**
```
14h00-15h00 Performance (Lighthouse > 90)
15h00-16h00 Documentation technique
16h00-17h00 Guide installation/déploiement
17h00-18h00 🎉 DÉMO FINALE
```

**Livrables J5:** Projet intégré et livré ✅

---

## 📋 CHECKLIST TÂCHES

### Stores Pinia (Jour 1)
- [ ] `/stores/contextIssueStore.ts`
  - State: issues[], loading, error
  - Actions: fetchIssues, createIssue, updateIssue, deleteIssue
  - Getters: issuesByContext, issuesBySentiment
- [ ] `/stores/categoryStore.ts`
  - State: categories[]
  - Actions: fetchCategories
  - Getters: swotCategories, pestelCategories

### Composables (Jour 1)
- [ ] `/composables/useContext.ts`
- [ ] `/composables/useCategories.ts`

### Services API (Jour 1-2)
- [ ] Enrichir `/services/api/contextService.ts`
- [ ] Tester connexion API réelle
- [ ] Gestion erreurs HTTP

### Pages (Jour 2-4)
- [ ] `/pages/context/index.vue` - Dashboard module
- [ ] Connecter SWOT.vue aux stores (J3)
- [ ] Connecter PESTEL.vue aux stores (J4)
- [ ] `/pages/context/Stakeholders.vue` (J4)

### Tests (Jour 4-5)
- [ ] Tests Cypress E2E:
  - Scénario: Créer enjeu SWOT
  - Scénario: Modifier enjeu
  - Scénario: Supprimer enjeu
  - Scénario: Marquer enjeu majeur
  - Scénario: Filtrer par catégorie

### Documentation (Jour 5)
- [ ] README technique
- [ ] Guide installation
- [ ] Guide déploiement

---

## 🛠️ COMMANDES UTILES

### Stores Pinia
```typescript
// Exemple store
import { defineStore } from 'pinia'
import { contextService } from '@/services/api/contextService'

export const useContextIssueStore = defineStore('contextIssue', {
  state: () => ({
    issues: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchIssues(contextId: number) {
      this.loading = true
      try {
        this.issues = await contextService.getContextIssues(contextId)
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },
  },
  getters: {
    issuesByCategory: (state) => (categoryId: number) => 
      state.issues.filter(i => i.category_id === categoryId),
  }
})
```

### Tests Cypress
```bash
npm run test:e2e          # Ouvrir Cypress
npm run test:e2e:headless # Lancer tests headless
```

---

## 📚 RESSOURCES TECHNIQUES

### Documentation
- Pinia: https://pinia.vuejs.org/
- Cypress: https://docs.cypress.io/
- VueUse: https://vueuse.org/

### Exemples
Voir stores existants: `/src/stores/auditStore.ts`

---

## 🤝 COORDINATION AVEC L'ÉQUIPE

### Dépendances
**DEV 1 (Frontend):**
- ⏳ Attendre composants UI (J1)
- 🤝 Collaboration sur SWOT.vue (J3)

**DEV 2 (Backend):**
- ⏳ Attendre API ready (J2 matin)
- 🤝 Tests Postman ensemble (J2)

### Communication
- **Slack:** Point d'avancement 2×/jour
- **Daily:** Partager blocages
- **Code review:** Reviewer PR de DEV1 et DEV2

---

## ✅ DEFINITION OF DONE (DoD)

- [ ] Store Pinia testé
- [ ] Service API connecté
- [ ] Page fonctionnelle E2E
- [ ] Gestion erreurs + loading
- [ ] Tests Cypress (au moins 1)
- [ ] Documentation
- [ ] Code review
- [ ] Merged dans `develop`

---

## 🎯 KPIs PERSONNELS

| Métrique | Objectif | Suivi |
|----------|----------|-------|
| Stores créés | 2 | __ |
| Pages connectées | 3 | __ |
| Tests E2E | 5+ | __ |
| Bugs intégration | < 3 | __ |
| Code reviews | 10+ | __ |

---

**Bon sprint ! 🚀**
