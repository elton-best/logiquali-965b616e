# Documentation Technique — Frontend BestQHSE

Stack : Vue 3 · TypeScript · Vite · Pinia · Vuetify 3 · Tailwind CSS 4

---

## 1. Stack technique

| Composant        | Technologie                     | Version |
| ---------------- | ------------------------------- | ------- |
| Framework        | Vue 3                           | ^3.5.21 |
| Langage          | TypeScript                      | ~5.9.2  |
| Build            | Vite                            | ^7.1.5  |
| State management | Pinia                           | ^3.0.3  |
| UI principale    | Vuetify                         | ^3.10.1 |
| CSS utilitaire   | Tailwind CSS                    | ^4.1.18 |
| Routing          | Vue Router                      | ^4.5.1  |
| HTTP             | Axios                           | ^1.13.2 |
| WebSocket        | Laravel Echo + Pusher           | ^2.3.0  |
| Graphiques       | ApexCharts + Chart.js + ECharts | —       |
| Calendrier       | FullCalendar                    | ^6.1.20 |
| Éditeur riche    | Tiptap + Quill                  | ^3.18.0 |
| i18n             | vue-i18n                        | ^9.14.5 |
| Tests unitaires  | Vitest                          | ^4.0.17 |
| Tests E2E        | Cypress                         | ^15.9.0 |

---

## 2. Architecture des sources

```
frontend/src/
├── api/
│   ├── services/          # Services API par domaine (actions, audits, auth…)
│   ├── client.ts          # Instance Axios configurée
│   └── http-client.ts     # Intercepteurs, gestion erreurs, token
├── modules/
│   ├── clienta/           # Module entreprise (admin + collaborateurs)
│   │   ├── components/    # Composants spécifiques ClientA
│   │   ├── composables/   # Hooks métier (useRisks, useDocuments…)
│   │   ├── pages/         # Pages par fonctionnalité
│   │   ├── router/        # Routes ClientA
│   │   └── types/         # Types TypeScript
│   ├── clientb/           # Module client final
│   │   ├── components/
│   │   ├── pages/
│   │   └── router/
│   ├── superadmin/        # Module super administrateur
│   │   ├── components/
│   │   ├── pages/
│   │   └── router/
│   └── shared/            # Composants et composables partagés
├── components/            # Composants globaux réutilisables
│   ├── common/            # AppButton, AppModal, AppTable…
│   ├── ui/                # Composants UI primitifs (Dialog, Select, Tabs…)
│   └── [domaine]/         # Composants par domaine QHSE
├── stores/                # Stores Pinia
├── services/              # Services métier frontend
├── composables/           # Hooks Vue réutilisables
├── router/                # Configuration routeur principal
├── types/                 # Types TypeScript globaux
├── utils/                 # Utilitaires (permissions, format, erreurs)
├── i18n/locales/          # Traductions fr.json / en.json
└── styles/                # Design tokens, SCSS global
```

---

## 3. Architecture modulaire

Le frontend est organisé en 3 modules isolés avec leurs propres routes, composants et composables.

### 3.1 Module ClientA (entreprise)

Point d'entrée : `modules/clienta/router/index.ts`

Pages disponibles :

| Section        | Pages                                                          |
| -------------- | -------------------------------------------------------------- |
| Dashboard      | `dashboard.vue`                                                |
| Contexte       | `context/`, `stakeholders/`                                    |
| Leadership     | `leadership/` (Policy, OrgChart, JobDescriptions)              |
| Planification  | `planning/` (RisksOpportunities, Objectives, ActionPlans)      |
| Support        | `documents/`, `competences/`, `communications/`, `operations/` |
| Performance    | `audits/`, `indicators/`, `management-reviews/`                |
| Amélioration   | `nonconformities/`, `actions/`, `improvement/`                 |
| ISO            | `iso/` (securite, environnement, energie)                      |
| Administration | `users/`, `roles/`, `sites/`, `settings/`, `subscriptions/`    |
| Profil         | `profile/`                                                     |

Composables métier (`modules/clienta/composables/`) :

| Composable             | Responsabilité                           |
| ---------------------- | ---------------------------------------- |
| `useRisks`             | CRUD risques, matrice, export            |
| `useDocuments`         | CRUD documents, versioning, approbation  |
| `useAudits`            | Planification, exécution, clôture audits |
| `useActions`           | Suivi actions, Gantt, Kanban             |
| `useNonConformities`   | Déclaration, analyse, clôture NC         |
| `useProcesses`         | Gestion processus, diagramme tortue      |
| `useIndicators`        | KPI, saisie valeurs, graphiques          |
| `useSubscription`      | Abonnement, modules accessibles          |
| `useSites`             | Gestion sites, sélecteur de site         |
| `useUsers`             | Gestion utilisateurs, rôles              |
| `useFormations`        | Plan de formation, compétences           |
| `useCommunications`    | Plan de communication                    |
| `useModuleAccess`      | Vérification accès modules               |
| `useSubscribedModules` | Modules souscrits actifs                 |

### 3.2 Module ClientB (client final)

Pages :

- `dashboard.vue` — tableau de bord client
- `ComplaintManagement.vue` — gestion réclamations
- `SatisfactionForms.vue` / `SatisfactionSurveys.vue` — formulaires satisfaction
- `Profile.vue` — profil client
- `Support.vue` — tickets support

### 3.3 Module SuperAdmin

Pages :

- `dashboard.vue` — statistiques plateforme
- `companies/` — validation entreprises (KYC)
- `norms/` — gestion référentiels normatifs
- `offers/` — gestion offres commerciales
- `subscriptions/` — gestion abonnements
- `users/` — gestion utilisateurs plateforme
- `settings.vue` — paramètres système

---

## 4. Stores Pinia

### 4.1 Stores globaux

| Store         | Fichier           | Responsabilité                           |
| ------------- | ----------------- | ---------------------------------------- |
| Auth          | `auth.ts`         | Utilisateur connecté, token, permissions |
| App           | `app.ts`          | État global application                  |
| Site Context  | `siteContext.ts`  | Site actif sélectionné                   |
| Global Loader | `globalLoader.ts` | État chargement global                   |
| Lock Screen   | `lockScreen.ts`   | Verrouillage écran                       |

### 4.2 Stores domaine

| Store                                 | Responsabilité                  |
| ------------------------------------- | ------------------------------- |
| `riskStore` / `improvement/riskStore` | Risques, matrice                |
| `auditStore` / `auditProgramStore`    | Audits, programmes              |
| `documentStore`                       | Documents, versions             |
| `nonConformityStore`                  | Non-conformités                 |
| `actionStore`                         | Actions correctives/préventives |
| `objectiveStore`                      | Objectifs QHSE                  |
| `indicatorStore` / `indicateurStore`  | Indicateurs KPI                 |
| `processStore`                        | Processus                       |
| `stakeholderStore`                    | Parties intéressées             |
| `contextStore`                        | Contexte organisme              |
| `managementReviewStore`               | Revues de direction             |
| `siteStore`                           | Sites                           |
| `userStore`                           | Utilisateurs                    |
| `subscriptionStore`                   | Abonnements                     |
| `competenceStore`                     | Compétences, habilitations      |
| `habilitationStore`                   | Habilitations ISO 45001         |
| `consommationEnergieStore`            | Consommations ISO 50001         |
| `aspectEnvironnementalStore`          | Aspects ISO 14001               |

---

## 5. Composants UI

### 5.1 Composants communs (`components/common/`)

| Composant                                | Usage                       |
| ---------------------------------------- | --------------------------- |
| `AppButton`                              | Bouton standardisé          |
| `AppModal`                               | Modale générique            |
| `AppTable`                               | Tableau avec tri/pagination |
| `AppInput` / `AppSelect` / `AppTextarea` | Champs formulaire           |
| `AppCard`                                | Carte contenu               |
| `AppBadge` / `StatusBadge`               | Badges statut               |
| `AppEmptyState`                          | État vide                   |
| `AppSpinner`                             | Indicateur chargement       |
| `AppToast`                               | Notifications toast         |
| `AppProgressBar`                         | Barre progression           |
| `AppWidget` / `AppWidgetCompact`         | Widgets tableau de bord     |

### 5.2 Composants UI primitifs (`components/ui/`)

Basés sur Radix Vue + Headless UI :

- Dialog, DropdownMenu, Tabs, Select, Checkbox, Switch
- Table (TableBody, TableRow, TableCell…)
- Badge, Button, Card, Input, Label, Textarea
- `UniversalDialog`, `UniversalFilters`, `UniversalPagination`, `UniversalSkeleton`
- `GlobalSearch`, `LoadingStates`, `ProgressiveLoader`

### 5.3 Composants domaine QHSE

| Domaine         | Composants clés                                                                                                     |
| --------------- | ------------------------------------------------------------------------------------------------------------------- |
| Actions         | `ActionCard`, `ActionForm`, `ActionGanttView`, `ActionKanbanView`, `ActionWizardDialog`                             |
| Audits          | `AuditCard`, `AuditWizardDialog`, `AuditChecklistTab`, `AuditFindingsTab`, `AuditCalendar`                          |
| Documents       | `DocumentCard`, `DocumentPreview`, `DocumentPyramidTree`, `DocumentQuillEditor`, `VersionHistory`, `ReviewWorkflow` |
| Risques         | `RiskHeatmapInteractive` (composant interactif matrice)                                                             |
| Non-conformités | `NCCard`, `NCCauseAnalysis`, `NCFilters`                                                                            |
| Indicateurs     | `IndicatorChart`, `IndicatorDashboard`, `IndicatorTrendWidget`                                                      |
| Processus       | `TurtleDiagramEditor`, `TurtleCell` (diagramme tortue)                                                              |
| Contexte        | `ContextWizard`, `PestelRadar`, `SwotMatrix`                                                                        |
| Compétences     | `CompetenceMatrix`, `GapAnalysisDialog`, `HabilitationForm`                                                         |
| Revue direction | `ManagementReviewWizard`, `ReviewTimeline`                                                                          |
| Satisfaction    | `SatisfactionDashboard`                                                                                             |
| Support         | `PlanMaintenance`, `InventaireEquipements`, `MaintenanceAlertes`                                                    |

---

## 6. Routing

### 6.1 Structure des routes

```
/ (public)
/landing
/pricing
/auth/login
/auth/signup/company
/auth/signup/clientb
/auth/forgot-password
/auth/email-verification
/qr-verify/:hash

/superadmin/*          ← Module SuperAdmin
/company/*             ← Module ClientA (entreprise)
/clientb/*             ← Module ClientB (client final)
```

### 6.2 Guards de navigation (`router/guards.ts`)

- Vérification authentification (`requiresAuth`)
- Redirection selon `user_type` (super_admin → /superadmin, company → /company, clientb → /clientb)
- `subscriptionGuard` — bloque accès si abonnement inactif
- Gestion erreurs import dynamique (retry automatique)

---

## 7. Composables globaux

| Composable          | Fichier                            | Responsabilité                    |
| ------------------- | ---------------------------------- | --------------------------------- |
| `useAuth`           | `composables/useAuth.ts`           | Authentification, déconnexion     |
| `usePermissions`    | `composables/usePermissions.ts`    | Vérification permissions frontend |
| `useFilters`        | `composables/useFilters.ts`        | Filtres réutilisables             |
| `usePagination`     | `composables/usePagination.ts`     | Pagination générique              |
| `useGlobalSearch`   | `composables/useGlobalSearch.ts`   | Recherche globale                 |
| `useLoading`        | `composables/useLoading.ts`        | États de chargement               |
| `useContextLoading` | `composables/useContextLoading.ts` | Chargement contextuel             |
| `useSnackbar`       | `composables/useSnackbar.ts`       | Notifications snackbar            |
| `useToast`          | `composables/useToast.ts`          | Notifications toast               |
| `useWebSocket`      | `composables/useWebSocket.ts`      | Connexion WebSocket (Reverb)      |
| `useMenu`           | `composables/useMenu.ts`           | Navigation sidebar                |

Composables partagés (`modules/shared/composables/`) :

- `useApi` — wrapper Axios avec gestion erreurs
- `useAsyncAction` — action async avec état loading/error
- `useAsyncData` — chargement données async
- `useDebounce` — debounce réactif
- `useTheme` — thème clair/sombre

---

## 8. Services frontend

| Service                                   | Responsabilité                  |
| ----------------------------------------- | ------------------------------- |
| `authService`                             | Login, logout, refresh token    |
| `riskService` / `improvement/riskService` | API risques                     |
| `auditService`                            | API audits                      |
| `documentService`                         | API documents, upload, download |
| `nonConformityService`                    | API non-conformités             |
| `actionService`                           | API actions                     |
| `indicatorService`                        | API indicateurs KPI             |
| `processService`                          | API processus                   |
| `subscriptionService`                     | API abonnements                 |
| `userService`                             | API utilisateurs                |
| `siteService`                             | API sites                       |
| `pdfExportService`                        | Export PDF côté client          |
| `dashboardService`                        | API tableau de bord             |
| `leadershipService`                       | API politique, organigramme     |
| `competenceService`                       | API compétences, matrice        |
| `habilitationService`                     | API habilitations               |
| `satisfactionSurveyService`               | API enquêtes satisfaction       |
| `complaintService`                        | API réclamations                |
| `superAdminService`                       | API superadmin                  |
| `normService`                             | API référentiels normatifs      |
| `geoCatalogService`                       | Pays, villes                    |
| `qrCodeService`                           | Vérification QR codes           |

---

## 9. Internationalisation

Fichiers : `src/i18n/locales/fr.json` et `en.json`

Langues supportées : Français (défaut), Anglais.

Composant de sélection : `components/ui/LanguageSwitcher.vue`

---

## 10. Design system

### 10.1 Tokens CSS (`styles/design-tokens.css`)

Variables CSS pour couleurs, espacements, typographie, ombres.

### 10.2 Thème Vuetify (`config/theme.ts`)

Couleurs primaires, secondaires, états (success, warning, error, info).

### 10.3 Tailwind CSS 4

Configuration : `tailwind.config.js` — classes utilitaires pour layouts et espacements.

### 10.4 Composants ClientA (`modules/clienta/assets/clienta-theme.css`)

Thème spécifique module entreprise.

---

## 11. Tests

### 11.1 Tests unitaires (Vitest)

Fichiers de tests dans `__tests__/` à côté des composants :

| Fichier                          | Composant testé          |
| -------------------------------- | ------------------------ |
| `AdvancedKpiDashboard.spec.ts`   | Dashboard KPI            |
| `ChartWidget.spec.ts`            | Widget graphique         |
| `KpiStatCard.spec.ts`            | Carte KPI                |
| `DocumentUpload.spec.ts`         | Upload document          |
| `RiskHeatmapInteractive.spec.ts` | Heatmap risques          |
| `RiskMatrix.spec.ts`             | Matrice risques          |
| `TurtleDiagramEditor.spec.ts`    | Éditeur diagramme tortue |
| `ConsentBanner.spec.ts`          | Bannière consentement    |
| `GlobalSearchBar.spec.ts`        | Barre recherche globale  |
| `useAuth.spec.ts`                | Composable auth          |
| `auth.spec.ts`                   | Store auth               |
| `risks.spec.ts`                  | Store risques            |
| `pdfExportService.spec.ts`       | Service export PDF       |

### 11.2 Tests E2E (Cypress)

Répertoire : `cypress/e2e/`
Configuration : `cypress.config.ts`

---

## 12. Configuration Vite

Fichier : `vite.config.mts`

Plugins utilisés :

- `@vitejs/plugin-vue` — support Vue SFC
- `vite-plugin-vuetify` — tree-shaking Vuetify
- `unplugin-vue-router` — routes typées
- `unplugin-auto-import` — imports automatiques Vue/Pinia
- `unplugin-vue-components` — enregistrement automatique composants
- `vite-plugin-vue-layouts-next` — layouts automatiques
- `unplugin-fonts` — polices (Roboto)

Alias : `@` → `src/`

Build : `--max-old-space-size=6144` (projets volumineux)
