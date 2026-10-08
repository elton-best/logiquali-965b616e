# 📋 TEMPLATE REPLICATION - 4 MODULES ISO 9001

**Date:** 9 février 2026  
**Stratégie:** Reproduire les documents Logi en formulaires Vue.js interactifs  
**Architecture:** Composants réutilisables + Sidebar uniforme

---

## 🎯 DOCUMENTS À REPRODUIRE (Logi/)

### Point 4 - Contexte de l'organisme

```
✅ M1-D1-Domaine d'application.docx
✅ M3-D1-CONTEXTE DE L'ORGANISME.docx (SWOT/PESTEL)
✅ M3-D2-REGISTRE DES PARTIES INTERESSEES.docx
```

### Point 5 - Leadership

```
✅ Politique QHSE (à créer - basé sur standard ISO)
✅ Organigramme & Responsabilités
✅ M1-D4-Fiche de poste.docx
```

### Point 6 - Planification

```
✅ M6-D1-Plan de maitrise des risques et opportunités.xlsx
✅ Objectifs QHSE (à créer)
✅ Plans d'action (à créer)
```

### Point 7 - Support

```
✅ PLAN ANNUEL DE FORMATION.xlsx
✅ INVENTAIRE DES EQUIPEMENTS.xlsx
✅ M5-D2-INVENTAIRE DOCUMENTAIRE.xlsx
✅ PLAN DE COMMUNICATION ET DE SENSIBILISATION.xlsx
✅ PLAN DE MAINTENANCE.xlsx
```

**Total:** 13 documents à transformer en formulaires interactifs

---

## 🏗️ ARCHITECTURE COMPOSANTS

### 1. Layout Global (Sidebar uniforme)

**Structure:**

```
AppLayout.vue (Layout principal)
├── Sidebar.vue (Navigation - IDENTIQUE partout)
├── Topbar.vue (Barre supérieure - breadcrumbs, search, user)
└── <slot> (Contenu de la page)
```

**Sidebar Client A - Structure:**

```vue
<v-navigation-drawer permanent width="260">
  <!-- Logo -->
  <div class="sidebar-header">
    <QualiBestLogo />
  </div>
  
  <!-- Menu ISO 9001 -->
  <v-list>
    📊 Dashboard
    
    📋 Point 4 - Contexte
      ├── Domaine d'application
      ├── SWOT/PESTEL
      └── parties intéressées
    
    👔 Point 5 - Leadership
      ├── Politique QHSE
      ├── Organigramme
      └── Fiches de poste
    
    📈 Point 6 - Planification
      ├── Risques & Opportunités
      ├── Objectifs QHSE
      └── Plans d'action
    
    🛠️ Point 7 - Support
      ├── Formation
      ├── Équipements
      ├── Documentation
      └── Communication
    
    📝 Processus
    📄 Documents
    🔍 Audits
    ⚠️ Non-conformités
    ✅ Actions
    
    <!-- Séparateur -->
    ⚙️ Paramètres
    👤 Profil
  </v-list>
  
  <!-- User Info -->
  <div class="sidebar-footer">
    <v-avatar />
    <span>{{ userName }}</span>
  </div>
</v-navigation-drawer>
```

---

### 2. Composants UI Réutilisables

**Base Components (radix-vue + Tailwind):**

```
/components/ui/
├── Button.vue           - Boutons (primary, secondary, danger)
├── Input.vue            - Champs texte
├── Textarea.vue         - Zone de texte
├── Select.vue           - Select/Dropdown
├── DatePicker.vue       - Sélecteur de date
├── Checkbox.vue         - Cases à cocher
├── Radio.vue            - Boutons radio
├── Switch.vue           - Interrupteur on/off
├── Dialog.vue           - Modal/Dialog
├── Tabs.vue             - Onglets
├── Card.vue             - Carte
├── Table.vue            - Tableau
├── Badge.vue            - Badge/Tag
└── Alert.vue            - Alertes/Notifications
```

**Form Components (métier):**

```
/components/forms/
├── FormField.vue        - Wrapper champ formulaire (label + input + error)
├── FormSection.vue      - Section formulaire (titre + champs groupés)
├── FormActions.vue      - Actions formulaire (Enregistrer, Annuler)
├── FormStepper.vue      - Formulaire multi-étapes
├── RichTextEditor.vue   - Éditeur riche (TipTap)
├── FileUpload.vue       - Upload fichiers
├── SignaturePad.vue     - Signature électronique
└── AutocompleteField.vue - Autocomplete
```

**Document Components (spécifiques):**

```
/components/documents/
├── DocumentHeader.vue   - En-tête document (titre, ref, date, version)
├── DocumentMetadata.vue - Métadonnées (auteur, approbateur, etc.)
├── DocumentSection.vue  - Section document
├── DocumentTable.vue    - Tableau dans document
├── DocumentApproval.vue - Workflow approbation
└── DocumentExport.vue   - Export PDF/Word
```

---

### 3. Templates par Type de Document

**Template 1: Formulaire Simple (ex: Domaine d'application)**

```vue
<template>
  <AppLayout>
    <PageHeader
      title="Domaine d'application"
      :breadcrumbs="['Point 4', 'Contexte', 'Domaine']"
    />

    <FormContainer>
      <DocumentHeader
        :doc-ref="form.reference"
        :version="form.version"
        :date="form.date"
      />

      <FormSection title="1. Organisme">
        <FormField label="Nom de l'organisme" required>
          <Input v-model="form.organizationName" />
        </FormField>
        <FormField label="Adresse">
          <Textarea v-model="form.address" rows="3" />
        </FormField>
      </FormSection>

      <FormSection title="2. Domaine d'activité">
        <FormField label="Description">
          <RichTextEditor v-model="form.activityDescription" />
        </FormField>
      </FormSection>

      <FormSection title="3. Exclusions ISO">
        <FormField label="Clauses exclues">
          <Checkbox v-model="form.exclusions" :options="isoExclusions" />
        </FormField>
      </FormSection>

      <FormActions
        @save="handleSave"
        @cancel="handleCancel"
        @export-pdf="handleExportPDF"
      />
    </FormContainer>
  </AppLayout>
</template>
```

**Template 2: Matrice Interactive (ex: SWOT)**

```vue
<template>
  <AppLayout>
    <PageHeader
      title="Analyse SWOT/PESTEL"
      :breadcrumbs="['Point 4', 'Contexte', 'SWOT']"
    />

    <Tabs v-model="activeTab">
      <Tab value="swot">SWOT</Tab>
      <Tab value="pestel">PESTEL</Tab>
    </Tabs>

    <!-- SWOT Matrix -->
    <div v-if="activeTab === 'swot'" class="swot-container">
      <div class="grid grid-cols-2 gap-4">
        <SWOTQuadrant
          title="Forces"
          color="green"
          :items="strengths"
          @add="handleAddStrength"
          @edit="handleEditIssue"
          @delete="handleDeleteIssue"
        />
        <SWOTQuadrant
          title="Faiblesses"
          color="red"
          :items="weaknesses"
          @add="handleAddWeakness"
        />
        <SWOTQuadrant
          title="Opportunités"
          color="blue"
          :items="opportunities"
          @add="handleAddOpportunity"
        />
        <SWOTQuadrant
          title="Menaces"
          color="orange"
          :items="threats"
          @add="handleAddThreat"
        />
      </div>
    </div>

    <!-- Issue Dialog -->
    <Dialog v-model="showIssueDialog">
      <IssueForm
        :issue="currentIssue"
        :category="currentCategory"
        @save="handleSaveIssue"
        @cancel="showIssueDialog = false"
      />
    </Dialog>
  </AppLayout>
</template>
```

**Template 3: Tableau/Registre (ex: parties intéressées)**

```vue
<template>
  <AppLayout>
    <PageHeader
      title="Registre des parties intéressées"
      :breadcrumbs="['Point 4', 'Contexte', 'parties intéressées']"
    >
      <template #actions>
        <Button @click="handleAdd">
          <Icon name="plus" />
          Ajouter partie intéressée
        </Button>
      </template>
    </PageHeader>

    <!-- Filters -->
    <div class="filters-bar">
      <Select
        v-model="filters.type"
        :options="stakeholderTypes"
        placeholder="Type"
      />
      <Select
        v-model="filters.influence"
        :options="influenceLevels"
        placeholder="Influence"
      />
      <Input v-model="filters.search" placeholder="Rechercher..." />
    </div>

    <!-- Table -->
    <DataTable
      :columns="columns"
      :data="filteredStakeholders"
      :loading="loading"
      @row-click="handleEdit"
      @sort="handleSort"
    >
      <template #cell-influence="{ row }">
        <Badge :color="getInfluenceColor(row.influence)">
          {{ row.influence }}
        </Badge>
      </template>
      <template #cell-actions="{ row }">
        <Button size="sm" variant="ghost" @click="handleEdit(row)">
          <Icon name="edit" />
        </Button>
        <Button size="sm" variant="ghost" @click="handleDelete(row)">
          <Icon name="trash" />
        </Button>
      </template>
    </DataTable>

    <!-- Stakeholder Dialog -->
    <Dialog v-model="showStakeholderDialog" size="lg">
      <StakeholderForm
        :stakeholder="currentStakeholder"
        @save="handleSave"
        @cancel="showStakeholderDialog = false"
      />
    </Dialog>
  </AppLayout>
</template>
```

**Template 4: Plan/Planning (ex: Plan de formation)**

```vue
<template>
  <AppLayout>
    <PageHeader
      title="Plan annuel de formation"
      :breadcrumbs="['Point 7', 'Support', 'Formation']"
    >
      <template #actions>
        <Button @click="handleExportExcel">
          <Icon name="download" />
          Exporter Excel
        </Button>
        <Button @click="handleAdd">
          <Icon name="plus" />
          Ajouter formation
        </Button>
      </template>
    </PageHeader>

    <!-- Timeline View -->
    <div class="timeline-container">
      <GanttChart
        :items="trainings"
        :start-date="yearStart"
        :end-date="yearEnd"
        @item-click="handleEdit"
      />
    </div>

    <!-- Table View -->
    <Tabs v-model="viewMode">
      <Tab value="table">Vue tableau</Tab>
      <Tab value="calendar">Vue calendrier</Tab>
      <Tab value="gantt">Vue Gantt</Tab>
    </Tabs>

    <div v-if="viewMode === 'table'">
      <DataTable
        :columns="trainingColumns"
        :data="trainings"
        :group-by="groupBy"
      >
        <template #cell-status="{ row }">
          <StatusChip :status="row.status" />
        </template>
        <template #cell-progress="{ row }">
          <ProgressBar :value="row.progress" />
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
```

---

## 🎨 DESIGN SYSTEM

### Couleurs (Tailwind CSS)

```css
/* Primaires */
--primary-50: #eff6ff;
--primary-500: #3b82f6; /* Bleu principal */
--primary-600: #2563eb;
--primary-700: #1d4ed8;

/* Secondaires */
--success: #10b981; /* Vert */
--warning: #f59e0b; /* Orange */
--danger: #ef4444; /* Rouge */
--info: #06b6d4; /* Cyan */

/* Neutres */
--gray-50: #f9fafb;
--gray-100: #f3f4f6;
--gray-200: #e5e7eb;
--gray-700: #374151;
--gray-900: #111827;
```

### Typographie

```css
/* Titres */
h1: text-3xl font-bold text-gray-900
h2: text-2xl font-semibold text-gray-800
h3: text-xl font-medium text-gray-700

/* Corps */
body: text-base text-gray-600
small: text-sm text-gray-500

/* Monospace (code, refs) */
code: font-mono text-sm
```

### Espacements

```css
/* Sections */
section-spacing: mb-8

/* Form fields */
field-spacing: mb-4

/* Cards */
card-padding: p-6
```

---

## 📊 MAPPING DOCUMENTS → COMPOSANTS

### Sprint 1: Point 4 - Contexte

**M1-D1-Domaine d'application:**

```
Page: /context/application-scope
Composants:
  - FormField (nom, adresse, activité)
  - RichTextEditor (description)
  - Checkbox (exclusions ISO)
  - DocumentApproval (workflow)
```

**M3-D1-CONTEXTE (SWOT/PESTEL):**

```
Page: /context/swot-pestel
Composants:
  - Tabs (SWOT / PESTEL)
  - SWOTMatrix (4 quadrants)
  - PESTELGrid (6 colonnes)
  - IssueCard (carte enjeu)
  - IssueForm (formulaire enjeu)
```

**M3-D2-REGISTRE PARTIES INTERESSEES:**

```
Page: /context/stakeholders
Composants:
  - DataTable (liste)
  - StakeholderForm (formulaire)
  - Badge (niveau influence)
  - Select (filtres)
```

---

### Sprint 2: Point 5 - Leadership

**Politique QHSE:**

```
Page: /leadership/policy
Composants:
  - RichTextEditor (contenu politique)
  - DocumentVersion (gestion versions)
  - SignaturePad (signature direction)
  - DocumentApproval (workflow)
```

**Organigramme:**

```
Page: /leadership/org-chart
Composants:
  - OrgChartTree (arbre hiérarchique)
  - OrgChartNode (nœud organigramme)
  - UserCard (carte collaborateur)
```

**M1-D4-Fiche de poste:**

```
Page: /leadership/job-descriptions
Composants:
  - FormStepper (multi-étapes)
  - RichTextEditor (missions, responsabilités)
  - Checkbox (compétences requises)
  - FileUpload (pièces jointes)
```

---

### Sprint 3: Point 6 - Planification

**M6-D1-Plan risques & opportunités:**

```
Page: /planning/risks-opportunities
Composants:
  - RiskMatrix (matrice gravité×probabilité)
  - RiskForm (formulaire risque)
  - OpportunityForm (formulaire opportunité)
  - DataTable (registre)
  - Badge (criticité)
```

**Objectifs QHSE:**

```
Page: /planning/objectives
Composants:
  - ObjectiveCard (carte objectif SMART)
  - ProgressBar (avancement)
  - KPIChart (graphiques indicateurs)
  - DataTable (liste objectifs)
```

**Plans d'action:**

```
Page: /planning/action-plans
Composants:
  - GanttChart (planning visuel)
  - ActionForm (formulaire action)
  - StatusChip (statut)
  - UserSelect (responsable)
```

---

### Sprint 4: Point 7 - Support

**PLAN ANNUEL DE FORMATION:**

```
Page: /support/training
Composants:
  - GanttChart (planning annuel)
  - TrainingForm (formulaire formation)
  - Calendar (vue calendrier)
  - DataTable (liste formations)
  - ProgressBar (réalisation)
```

**INVENTAIRE DES EQUIPEMENTS:**

```
Page: /support/equipment
Composants:
  - DataTable (inventaire)
  - EquipmentForm (formulaire équipement)
  - Badge (statut, état)
  - FileUpload (photos, docs)
  - QRCodeGenerator (code équipement)
```

**M5-D2-INVENTAIRE DOCUMENTAIRE:**

```
Page: /support/document-inventory
Composants:
  - DataTable (inventaire)
  - DocumentForm (formulaire doc)
  - Badge (type, statut)
  - Select (filtres multi-critères)
```

**PLAN DE COMMUNICATION:**

```
Page: /support/communication
Composants:
  - GanttChart (planning)
  - CommunicationForm (formulaire)
  - Badge (canal, cible)
  - Calendar (vue calendrier)
```

**PLAN DE MAINTENANCE:**

```
Page: /support/maintenance
Composants:
  - GanttChart (planning)
  - MaintenanceForm (formulaire)
  - Badge (type maintenance)
  - Calendar (vue calendrier)
  - RecurrenceSelector (récurrence)
```

---

## 🔧 COMPOSANTS TECHNIQUES

### Store Pinia Pattern

```typescript
// /stores/contextStore.ts
export const useContextStore = defineStore("context", {
  state: () => ({
    applicationScope: null,
    issues: [],
    stakeholders: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchApplicationScope() {
      this.loading = true;
      try {
        this.applicationScope = await contextService.getApplicationScope();
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },

    async saveApplicationScope(data) {
      return contextService.saveApplicationScope(data);
    },
  },

  getters: {
    swotIssues: (state) => state.issues.filter((i) => i.type === "swot"),
    pestelIssues: (state) => state.issues.filter((i) => i.type === "pestel"),
  },
});
```

### Service API Pattern

```typescript
// /services/api/contextService.ts
export const contextService = {
  // Application Scope
  getApplicationScope: () => http.get("/api/client-a/application-scope"),
  saveApplicationScope: (data) =>
    http.post("/api/client-a/application-scope", data),

  // SWOT/PESTEL
  getIssues: (contextId) =>
    http.get(`/api/client-a/contexts/${contextId}/issues`),
  createIssue: (contextId, data) =>
    http.post(`/api/client-a/contexts/${contextId}/issues`, data),
  updateIssue: (issueId, data) =>
    http.put(`/api/client-a/issues/${issueId}`, data),
  deleteIssue: (issueId) => http.delete(`/api/client-a/issues/${issueId}`),

  // Stakeholders
  getStakeholders: () => http.get("/api/client-a/stakeholders"),
  createStakeholder: (data) => http.post("/api/client-a/stakeholders", data),
  updateStakeholder: (id, data) =>
    http.put(`/api/client-a/stakeholders/${id}`, data),
  deleteStakeholder: (id) => http.delete(`/api/client-a/stakeholders/${id}`),
};
```

---

## 📋 CHECKLIST DÉVELOPPEMENT

### Par composant

- [ ] TypeScript strict (pas de `any`)
- [ ] Props validés avec types
- [ ] Emits documentés
- [ ] Composable si logique réutilisable
- [ ] Tailwind CSS (pas de CSS custom)
- [ ] Responsive (mobile/tablet/desktop)
- [ ] Accessible (ARIA labels)
- [ ] Tests unitaires (si complexe)
- [ ] Storybook story (si UI)
- [ ] Documentation JSDoc

### Par page

- [ ] Layout AppLayout avec sidebar
- [ ] Breadcrumbs corrects
- [ ] Loading states
- [ ] Error handling
- [ ] Empty states
- [ ] Formulaire validé (vee-validate)
- [ ] API connectée
- [ ] Store Pinia
- [ ] Tests E2E Cypress

---

## 🎯 OBJECTIFS PAR SPRINT (Mise à jour)

### Sprint 1 (10-14 fév): Point 4

**Pages:** 3

- /context/application-scope
- /context/swot-pestel
- /context/stakeholders

**Composants nouveaux:** 12

- SWOTMatrix, SWOTQuadrant, PESTELGrid
- IssueCard, IssueForm
- StakeholderForm, DocumentHeader
- (+ 5 composants UI base)

---

### Sprint 2 (17-21 fév): Point 5

**Pages:** 3

- /leadership/policy
- /leadership/org-chart
- /leadership/job-descriptions

**Composants nouveaux:** 8

- RichTextEditor, SignaturePad
- OrgChartTree, OrgChartNode
- FormStepper, JobDescriptionForm

---

### Sprint 3 (24-28 fév): Point 6

**Pages:** 3

- /planning/risks-opportunities
- /planning/objectives
- /planning/action-plans

**Composants nouveaux:** 8

- RiskMatrix, RiskForm
- ObjectiveCard, KPIChart
- GanttChart, ActionForm

---

### Sprint 4 (03-07 mar): Point 7

**Pages:** 5

- /support/training
- /support/equipment
- /support/document-inventory
- /support/communication
- /support/maintenance

**Composants nouveaux:** 10

- Calendar, RecurrenceSelector
- EquipmentForm, QRCodeGenerator
- CommunicationForm, MaintenanceForm

---

## 📦 LIVRABLES FINAUX (4 sprints)

**Total:**

- 📄 **14 pages** Vue.js complètes
- 🧩 **50+ composants** réutilisables
- 📊 **13 documents** Logi reproduits
- 🎨 **1 sidebar** unifiée Client A
- 🔌 **40+ endpoints** API Laravel
- ✅ **20+ tests** E2E Cypress

---

**Créé le:** 9 février 2026  
**Version:** 1.0  
**Status:** Stratégie validée - Option B
