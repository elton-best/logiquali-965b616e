# 🎨 PLAN D'INTÉGRATION VUE.JS - Modules ISO Logi → Client A

**Date:** 9 février 2026  
**Décision:** Rester sur Vue.js 3 + TypeScript + Vuetify + radix-vue

---

## ✅ STACK TECHNIQUE CONFIRMÉE

### Frontend (Existant - À enrichir)
- ✅ Vue 3.5 + TypeScript
- ✅ Vuetify 3.10 (Material Design)
- ✅ **radix-vue 1.9** (composants headless accessibles) 
- ✅ Pinia 3.0 (state management)
- ✅ Vue Router 4.5
- ✅ Tailwind CSS 4.1
- ✅ Axios (API calls)
- ✅ lucide-vue-next (icônes)
- ✅ chart.js + vue-chartjs (graphiques)

### Backend (Inchangé)
- ✅ Laravel 11 + PostgreSQL
- ✅ API REST + Sanctum
- ✅ Spatie Permission

---

## 🎯 STRATÉGIE D'INTÉGRATION

### 1. Adapter Design System Logi pour Vue

Au lieu de migrer vers React, nous allons :
1. **Utiliser radix-vue** (déjà installé !) pour les composants primitifs accessibles
2. **Créer composants UI Vue** inspirés du design Logi
3. **Combiner Vuetify + radix-vue** pour le meilleur des deux mondes
4. **Réutiliser les couleurs/styles** du design Logi avec Tailwind

### 2. Structure des Nouveaux Modules

```
frontend/src/
├── pages/
│   ├── context/              # Point 4 - Contexte Organisme (NOUVEAU)
│   │   ├── index.vue
│   │   ├── SWOT.vue
│   │   ├── PESTEL.vue
│   │   ├── Stakeholders.vue  # Améliorer existant
│   │   └── ApplicationScope.vue
│   │
│   ├── leadership/           # Point 5 - Leadership (NOUVEAU)
│   │   ├── index.vue
│   │   ├── QHSEPolicy.vue
│   │   ├── JobDescriptions.vue
│   │   └── Responsibilities.vue
│   │
│   ├── planning/             # Point 6 - Planification (NOUVEAU)
│   │   ├── index.vue
│   │   ├── RiskMatrix.vue     # Améliorer risks/ existant
│   │   ├── DUERP.vue          # NOUVEAU
│   │   ├── EnvironmentalAspects.vue
│   │   ├── Objectives.vue     # Améliorer existant
│   │   └── Compliance.vue
│   │
│   ├── support/              # Point 7 - Support (NOUVEAU)
│   │   ├── index.vue
│   │   ├── Equipment.vue
│   │   ├── Maintenance.vue
│   │   ├── Training.vue
│   │   └── Communication.vue
│   │
│   ├── operations/           # Point 8 - Réalisation (NOUVEAU)
│   │   ├── index.vue
│   │   ├── OperationalControls.vue
│   │   └── EmergencyProcedures.vue
│   │
│   ├── evaluation/           # Point 9 - Évaluation
│   │   ├── Audits.vue         # Améliorer improvement/audits
│   │   └── ManagementReview.vue  # Améliorer management-reviews
│   │
│   └── improvement/          # Point 10 - Amélioration (existant)
│       ├── NonConformities.vue  # Améliorer
│       ├── Actions.vue          # Améliorer
│       ├── FiveWhy.vue          # NOUVEAU
│       └── Ishikawa.vue         # NOUVEAU
│
├── components/
│   ├── ui/                   # Composants UI réutilisables (NOUVEAU)
│   │   ├── Button.vue
│   │   ├── Card.vue
│   │   ├── Dialog.vue
│   │   ├── Input.vue
│   │   ├── Select.vue
│   │   ├── Table.vue
│   │   ├── Tabs.vue
│   │   └── ...
│   │
│   ├── shared/               # Composants métier partagés
│   │   ├── AiAssistant.vue   # NOUVEAU
│   │   ├── SignaturePad.vue  # NOUVEAU
│   │   ├── ExcelImport.vue   # NOUVEAU
│   │   ├── PDFGenerator.vue  # NOUVEAU
│   │   └── DataTable.vue     # Améliorer existant
│   │
│   ├── charts/               # Graphiques spécialisés
│   │   ├── RiskHeatmap.vue   # NOUVEAU
│   │   ├── SWOTMatrix.vue    # NOUVEAU
│   │   └── ...
│   │
│   └── onboarding/           # NOUVEAU
│       ├── FirstLogin.vue
│       ├── PasswordChange.vue
│       ├── SignatureUpload.vue
│       └── ActivityDomain.vue
│
├── stores/
│   ├── contextStore.ts       # Améliorer existant
│   ├── stakeholderStore.ts   # Améliorer existant
│   ├── qhsePolicyStore.ts    # NOUVEAU
│   ├── jobDescriptionStore.ts # NOUVEAU
│   ├── duerpStore.ts         # NOUVEAU
│   ├── environmentalAspectStore.ts # NOUVEAU
│   ├── equipmentStore.ts     # NOUVEAU
│   ├── trainingStore.ts      # NOUVEAU
│   ├── aiAssistantStore.ts   # NOUVEAU
│   └── onboardingStore.ts    # NOUVEAU
│
├── services/
│   ├── api/
│   │   ├── contextService.ts     # NOUVEAU
│   │   ├── stakeholderService.ts # NOUVEAU
│   │   ├── qhsePolicyService.ts  # NOUVEAU
│   │   ├── duerpService.ts       # NOUVEAU
│   │   ├── equipmentService.ts   # NOUVEAU
│   │   ├── trainingService.ts    # NOUVEAU
│   │   └── aiService.ts          # NOUVEAU (Grok)
│
└── composables/              # Hooks Vue réutilisables
    ├── usePermissions.ts     # NOUVEAU
    ├── useDebounce.ts        # NOUVEAU
    └── useApi.ts             # NOUVEAU
```

---

## 📦 PACKAGES SUPPLÉMENTAIRES À INSTALLER

```bash
# Composants UI additionnels
npm install @headlessui/vue       # Alternative à radix-vue
npm install @vueuse/core          # Utilitaires Vue
npm install vueuse/motion         # Animations

# Formulaires & validation
npm install vee-validate yup      # Validation formulaires

# Signature électronique
npm install vue-signature-pad     # Signature canvas

# Génération PDF (frontend preview)
npm install jspdf html2canvas     # PDF côté client

# Excel
# xlsx déjà installé ✅

# Dates
# dayjs et date-fns déjà installés ✅

# Graphiques
# chart.js, apexcharts, echarts déjà installés ✅
```

---

## 🚀 PHASE 1 : COMPOSANTS UI BASE (Semaine 1)

### Objectif
Créer les composants UI réutilisables inspirés du design Logi avec radix-vue

### Tâches
1. ✅ Créer `/components/ui/` 
2. ✅ Adapter composants Radix UI React → radix-vue:
   - Button.vue
   - Card.vue
   - Dialog.vue
   - Input.vue
   - Select.vue
   - Tabs.vue
   - Table.vue
3. ✅ Configurer Tailwind avec couleurs design Logi
4. ✅ Créer composants shared:
   - DataTable.vue (améliorer existant)
   - AiAssistant.vue
   - SignaturePad.vue

### Livrables
- 10+ composants UI base fonctionnels
- Documentation Storybook (optionnel)

---

## 🌍 PHASE 2 : POINT 4 - CONTEXTE ORGANISME (Semaines 2-3)

### Backend
1. Migrations:
   - `analysis_categories`
   - `organization_contexts`
   - `context_issues`
   - `application_scopes`
   - Améliorer `stakeholders`

2. Modèles Eloquent:
   - `AnalysisCategory`
   - `OrganizationContext`
   - `ContextIssue`
   - `ApplicationScope`
   - `Stakeholder` (enrichir)

3. Controllers API:
   - `ContextController`
   - `StakeholderController` (enrichir)

### Frontend Vue
1. Pages:
   - `/pages/context/index.vue` (dashboard module)
   - `/pages/context/SWOT.vue` (analyse SWOT)
   - `/pages/context/PESTEL.vue` (analyse PESTEL)
   - `/pages/context/Stakeholders.vue` (parties intéressées)
   - `/pages/context/ApplicationScope.vue` (domaine application)

2. Composants:
   - `SWOTMatrix.vue` (matrice interactive)
   - `PESTELGrid.vue`
   - `StakeholderCard.vue`
   - `IssueForm.vue`

3. Stores Pinia:
   - Enrichir `contextStore.ts`
   - Enrichir `stakeholderStore.ts`

4. Services API:
   - `contextService.ts`
   - Enrichir `stakeholderService.ts`

### Livrables
- ✅ Module Contexte fonctionnel
- ✅ SWOT/PESTEL opérationnels
- ✅ Parties intéressées CRUD
- ✅ Tests E2E

---

## 📋 PHASE 3 : POINT 6 - DUERP & AES (Semaines 4-5)

### Backend
1. Migrations:
   - `duerp` + `duerp_dangers`
   - `environmental_aspects`
   - Enrichir `risks` (matrice 5x5)

2. Modèles + Controllers

### Frontend Vue
1. Pages:
   - `/pages/planning/DUERP.vue`
   - `/pages/planning/EnvironmentalAspects.vue`
   - `/pages/planning/RiskMatrix.vue` (améliorer existant)

2. Composants:
   - `DUERPTable.vue`
   - `DangerForm.vue`
   - `RiskHeatmap.vue` (graphique)
   - `CriticalityMatrix.vue`

3. Stores + Services

### Livrables
- ✅ DUERP complet + PDF
- ✅ AES opérationnel
- ✅ Matrice risques 5x5

---

## 👔 PHASE 4 : POINT 5 - LEADERSHIP (Semaine 6)

### Backend
1. Migrations: `qhse_policies`, `job_descriptions`, `responsibilities`
2. Modèles + Controllers
3. Génération PDF politique QHSE

### Frontend Vue
1. Pages:
   - `/pages/leadership/QHSEPolicy.vue`
   - `/pages/leadership/JobDescriptions.vue`
   - `/pages/leadership/Responsibilities.vue`

2. Composants:
   - `PolicyEditor.vue`
   - `SignaturePad.vue` (réutilisable)
   - `JobDescriptionForm.vue`

### Livrables
- ✅ Politique QHSE éditable + PDF
- ✅ Fiches de poste + signature

---

## 🛠️ PHASE 5 : POINT 7 - SUPPORT (Semaines 7-8)

### Backend
1. Migrations: `equipment`, `maintenance_plans`, `training_*`
2. Modèles + Controllers

### Frontend Vue
1. Pages:
   - `/pages/support/Equipment.vue`
   - `/pages/support/Maintenance.vue`
   - `/pages/support/Training.vue`

2. Composants:
   - `EquipmentCard.vue`
   - `MaintenancePlan.vue`
   - `TrainingCalendar.vue` (FullCalendar)
   - `ExcelImport.vue`

### Livrables
- ✅ Gestion équipements
- ✅ Plans maintenance
- ✅ Plan formation annuel

---

## ✨ PHASE 6 : AMÉLIORATIONS & IA (Semaines 9-10)

### Backend
1. Service IA Grok (Laravel)
2. Tables: `ai_suggestions`, `onboarding_steps`
3. Enrichir NC (5Why, Ishikawa)

### Frontend Vue
1. Composants:
   - `AiAssistant.vue` (global)
   - `FiveWhyDiagram.vue`
   - `IshikawaDiagram.vue`

2. Pages:
   - `/pages/improvement/FiveWhy.vue`
   - `/pages/improvement/Ishikawa.vue`
   - Onboarding workflow

### Livrables
- ✅ IA Grok intégré
- ✅ Analyse causes enrichie
- ✅ Onboarding premier login

---

## 📊 ESTIMATION GLOBALE

| Phase | Durée | Backend | Frontend | Total |
|-------|-------|---------|----------|-------|
| Phase 1 - UI Base | 1 sem | 0h | 30h | 30h |
| Phase 2 - Contexte | 2 sem | 20h | 40h | 60h |
| Phase 3 - DUERP/AES | 2 sem | 20h | 40h | 60h |
| Phase 4 - Leadership | 1 sem | 15h | 25h | 40h |
| Phase 5 - Support | 2 sem | 20h | 40h | 60h |
| Phase 6 - IA & Polish | 2 sem | 15h | 35h | 50h |
| **TOTAL** | **10 sem** | **90h** | **210h** | **300h** |

**Budget:** 300h × 60€/h = **18 000€** (inchangé vs React)

---

## 🎨 DESIGN SYSTEM VUE (radix-vue + Vuetify)

### Philosophie
- **Vuetify** pour les composants riches (DataTable, DatePicker, etc.)
- **radix-vue** pour les composants headless accessibles (Dialog, Select, Tabs)
- **Tailwind** pour le styling custom
- **lucide-vue-next** pour les icônes

### Couleurs Logi (Tailwind Config)

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        // Palette principale Logi
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
        },
        // Couleurs QHSE
        quality: '#3b82f6',    // Bleu
        health: '#10b981',     // Vert
        safety: '#f59e0b',     // Orange
        environment: '#06b6d4', // Cyan
      }
    }
  }
}
```

---

## ✅ CHECKLIST DÉMARRAGE

### Préparation (Aujourd'hui)
- [x] Décision Vue.js validée
- [ ] Installer packages supplémentaires
- [ ] Configurer Tailwind couleurs Logi
- [ ] Créer structure dossiers `/pages/context`, etc.
- [ ] Créer `/components/ui/`

### Semaine 1
- [ ] Créer composants UI base (10 composants)
- [ ] AiAssistant.vue base
- [ ] SignaturePad.vue
- [ ] DataTable.vue amélioré

### Semaine 2-3
- [ ] Migrations Point 4
- [ ] Pages Contexte
- [ ] SWOT/PESTEL fonctionnels
- [ ] Tests

---

## 🚀 COMMENÇONS !

**Prochaines actions immédiates:**
1. Installer packages supplémentaires
2. Créer structure dossiers
3. Créer premier composant UI (Button.vue)
4. Créer premier service (contextService.ts)
5. Créer première page (SWOT.vue)

**Prêt à commencer ?** 🎯

---

**Document créé le:** 9 février 2026  
**Version:** 1.0 (Vue.js)  
**Durée estimée:** 10 semaines  
**Budget:** 18 000€
