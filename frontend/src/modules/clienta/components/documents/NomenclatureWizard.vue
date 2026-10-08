<template>
  <v-card class="nomenclature-wizard" rounded="xl">
    <!-- Header -->
    <div class="wizard-header px-6 pt-5 pb-4">
      <div class="d-flex align-start justify-space-between">
        <div>
          <div class="text-h6 font-weight-bold">Gestion des nomenclatures</div>
          <div class="text-body-2 text-medium-emphasis mt-1">
            Configurez la codification documentaire de votre organisation
          </div>
        </div>
        <v-btn icon="mdi-close" variant="text" @click="$emit('close')" />
      </div>

      <!-- Stepper -->
      <div class="wizard-stepper mt-5">
        <div
          v-for="(step, i) in steps"
          :key="i"
          class="wizard-step"
          :class="{ active: currentStep === i, completed: currentStep > i, clickable: currentStep > i }"
          @click="currentStep > i ? (currentStep = i) : null"
        >
          <div class="step-bubble">
            <v-icon v-if="currentStep > i" size="16">mdi-check</v-icon>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <div class="step-info">
            <div class="step-label">{{ step.label }}</div>
            <div class="step-desc">{{ step.desc }}</div>
          </div>
          <div v-if="i < steps.length - 1" class="step-connector" :class="{ done: currentStep > i }" />
        </div>
      </div>
    </div>

    <v-divider />

    <!-- Step content -->
    <v-card-text class="pa-6" style="min-height: 420px">

      <!-- ── STEP 0 : Types documentaires ── -->
      <div v-if="currentStep === 0">
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <div class="text-subtitle-1 font-weight-bold">Types de documents</div>
            <div class="text-caption text-medium-emphasis">
              Définissez les catégories de documents (ex : Procédure, Formulaire, Politique…)
            </div>
          </div>
          <v-btn color="primary" prepend-icon="mdi-plus" variant="tonal" @click="openDocTypeForm()">
            Ajouter un type
          </v-btn>
        </div>

        <v-alert v-if="docTypeError" class="mb-4" density="comfortable" type="error" variant="tonal">
          {{ docTypeError }}
        </v-alert>

        <v-alert
          v-if="missingActiveTypes.length > 0"
          class="mb-4"
          density="comfortable"
          icon="mdi-alert-circle-outline"
          type="warning"
          variant="tonal"
        >
          {{ missingActiveTypes.length }} type(s) documentaire(s) actif(s) n'ont pas encore de nomenclature publiée.
        </v-alert>

        <v-table density="comfortable">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Abréviation</th>
              <th>Configuration active</th>
              <th>Exemple</th>
              <th>Documents</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in documentTypeCatalogs" :key="item.id">
              <td class="font-weight-medium">{{ item.name }}</td>
              <td><code class="abbr-pill">{{ item.abbreviation }}</code></td>
              <td>
                <div v-if="activeTemplateForType(item.id)" class="d-flex flex-column ga-1">
                  <div class="d-flex align-center ga-2">
                    <v-chip color="success" size="small" variant="tonal">
                      Template actif utilisé pour la génération
                    </v-chip>
                    <span class="text-caption text-medium-emphasis">
                      v{{ activeTemplateForType(item.id)?.version }}
                    </span>
                  </div>
                  <div class="template-structure">
                    {{ structureLabel(activeTemplateForType(item.id)?.format_structure ?? [], activeTemplateForType(item.id)?.separator ?? '-') }}
                  </div>
                </div>
                <v-chip v-else color="warning" size="small" variant="tonal">
                  Aucune configuration publiée
                </v-chip>
              </td>
              <td>
                <code v-if="activeTemplateForType(item.id)?.preview_example" class="sample-pill">
                  {{ activeTemplateForType(item.id)?.preview_example }}
                </code>
                <span v-else class="text-medium-emphasis">—</span>
              </td>
              <td>{{ activeTemplateForType(item.id)?.documents_count ?? 0 }}</td>
              <td>
                <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" variant="tonal">
                  {{ item.is_active ? 'Actif' : 'Inactif' }}
                </v-chip>
              </td>
              <td class="text-right">
                <v-btn
                  color="primary"
                  density="comfortable"
                  size="small"
                  variant="tonal"
                  @click="configureDocType(item.id)"
                >
                  {{ activeTemplateForType(item.id) ? 'Modifier' : 'Configurer' }}
                </v-btn>
                <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openDocTypeForm(item)" />
                <v-btn
                  color="error"
                  icon="mdi-delete-outline"
                  size="small"
                  variant="text"
                  @click="confirmDeleteDocType(item.id)"
                />
              </td>
            </tr>
            <tr v-if="documentTypeCatalogs.length === 0">
              <td class="text-center text-medium-emphasis py-6" colspan="7">
                <v-icon class="mb-2" color="grey" size="32">mdi-file-document-outline</v-icon>
                <div>Aucun type documentaire défini.</div>
                <div class="text-caption mt-1">Commencez par ajouter un type (ex : Procédure, Formulaire…)</div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <!-- ── STEP 1 : Structure du code ── -->
      <div v-if="currentStep === 1">
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <div class="text-subtitle-1 font-weight-bold">Structure du code document</div>
            <div class="text-caption text-medium-emphasis">
              Définissez les parties qui composent le code (ex : PRC-PROD-2025-001)
            </div>
          </div>
          <div class="d-flex ga-2">
            <v-select
              v-model="selectedDocTypeId"
              clearable
              density="compact"
              hide-details
              :items="docTypeOptions"
              label="Type de document"
              style="min-width: 200px"
              variant="outlined"
              @update:model-value="onDocTypeChange"
            />
          </div>
        </div>

        <v-alert
          v-if="!selectedDocTypeId"
          class="mb-4"
          density="comfortable"
          icon="mdi-information-outline"
          type="info"
          variant="tonal"
        >
          Sélectionnez un type de document pour configurer sa structure de code.
        </v-alert>

        <template v-if="selectedDocTypeId">
          <v-alert
            v-if="selectedActiveTemplate"
            class="mb-4"
            density="comfortable"
            icon="mdi-check-decagram-outline"
            type="success"
            variant="tonal"
          >
            Configuration active chargée : version {{ selectedActiveTemplate.version }},
            publiée {{ formatDate(selectedActiveTemplate.published_at) }}.
            Toute publication créera une nouvelle version et archivera l'actuelle.
          </v-alert>

          <v-alert
            v-else
            class="mb-4"
            density="comfortable"
            icon="mdi-file-cog-outline"
            type="info"
            variant="tonal"
          >
            Aucun template actif n'existe pour ce type. Une structure par défaut est proposée.
          </v-alert>

          <!-- Séparateur global -->
          <div class="d-flex align-center ga-3 mb-4">
            <span class="text-body-2 font-weight-medium">Séparateur entre les parties :</span>
            <v-btn-toggle v-model="activeSeparator" density="compact" mandatory variant="outlined">
              <v-btn value="-">Tiret  -</v-btn>
              <v-btn value="_">Underscore  _</v-btn>
              <v-btn value="/">Slash  /</v-btn>
              <v-btn value=".">Point  .</v-btn>
            </v-btn-toggle>
          </div>

          <!-- Boutons d'ajout rapide -->
          <div class="d-flex flex-wrap ga-2 mb-4">
            <span class="text-caption text-medium-emphasis align-self-center">Ajouter :</span>
            <v-btn
              v-for="quick in availableQuickAddParts"
              :key="quick.type"
              density="compact"
              :prepend-icon="quick.icon"
              size="small"
              variant="tonal"
              @click="addPart(quick)"
            >
              {{ quick.label }}
            </v-btn>
          </div>

          <!-- Liste des parties (drag & drop) -->
          <draggable
            v-model="activeParts"
            class="parts-list"
            handle=".drag-handle"
            item-key="id"
            @end="reorderParts"
          >
            <template #item="{ element, index }">
              <v-card
                class="part-row mb-2"
                :class="{ 'part-locked': element.type === 'sequential_number' }"
                elevation="0"
                rounded="lg"
              >
                <v-card-text class="pa-3">
                  <div class="d-flex align-center ga-3 flex-wrap">
                    <!-- Drag handle -->
                    <v-icon
                      class="drag-handle"
                      :class="{ 'drag-locked': element.type === 'sequential_number' || element.type === 'document_type' }"
                      color="grey-lighten-1"
                      size="20"
                    >
                      {{ (element.type === 'sequential_number' || element.type === 'document_type') ? 'mdi-lock-outline' : 'mdi-drag-vertical' }}
                    </v-icon>

                    <!-- Numéro d'ordre -->
                    <v-chip color="primary" size="small" variant="tonal">{{ index + 1 }}</v-chip>

                    <!-- Type de partie -->
                    <v-select
                      v-model="element.type"
                      density="compact"
                      :disabled="element.type === 'sequential_number' || element.type === 'document_type'"
                      hide-details
                      :items="getAvailablePartTypeOptions(element.type)"
                      style="min-width: 190px; max-width: 210px"
                      variant="outlined"
                      @update:model-value="onPartTypeChange(element)"
                    />

                    <!-- Libellé -->
                    <v-text-field
                      v-model="element.label"
                      density="compact"
                      :disabled="element.type === 'sequential_number' || element.type === 'document_type'"
                      hide-details
                      placeholder="Libellé"
                      style="min-width: 130px; max-width: 180px"
                      variant="outlined"
                    />

                    <!-- Longueur -->
                    <v-text-field
                      v-model.number="element.length"
                      density="compact"
                      :disabled="element.auto && element.type !== 'sequential_number'"
                      hide-details
                      label="Long."
                      max="20"
                      min="1"
                      style="max-width: 80px"
                      type="number"
                      variant="outlined"
                    />

                    <!-- Valeur par défaut (custom / free_text) -->
                    <v-text-field
                      v-if="['custom', 'free_text'].includes(element.type)"
                      v-model="element.value"
                      density="compact"
                      hide-details
                      placeholder="Valeur par défaut"
                      style="min-width: 130px; max-width: 160px"
                      variant="outlined"
                    />

                    <!-- Scope séquence -->
                    <v-select
                      v-if="element.type === 'sequential_number'"
                      v-model="element.sequence_scope"
                      density="compact"
                      hide-details
                      :items="sequenceScopeOptions"
                      label="Portée"
                      style="min-width: 200px; max-width: 240px"
                      variant="outlined"
                    />

                    <v-spacer />

                    <!-- Supprimer -->
                    <v-btn
                      v-if="element.type !== 'sequential_number' && element.type !== 'document_type'"
                      color="error"
                      icon="mdi-close"
                      size="small"
                      variant="text"
                      @click="removePart(index)"
                    />
                    <v-tooltip v-else-if="element.type === 'sequential_number'" text="Le numéro séquentiel est obligatoire">
                      <template #activator="{ props: tp }">
                        <v-icon v-bind="tp" color="grey" size="18">mdi-information-outline</v-icon>
                      </template>
                    </v-tooltip>
                    <v-tooltip v-else-if="element.type === 'document_type'" text="Le type de document est obligatoirement en première position">
                      <template #activator="{ props: tp }">
                        <v-icon v-bind="tp" color="grey" size="18">mdi-lock-outline</v-icon>
                      </template>
                    </v-tooltip>
                  </div>
                </v-card-text>
              </v-card>
            </template>
          </draggable>

          <v-alert
            v-if="structureErrors.length > 0"
            class="mt-3"
            density="comfortable"
            type="warning"
            variant="tonal"
          >
            <ul class="mb-0 pl-4">
              <li v-for="(err, i) in structureErrors" :key="i">{{ err }}</li>
            </ul>
          </v-alert>

          <div v-if="selectedTemplateHistory.length > 0" class="mt-6">
            <div class="text-subtitle-2 font-weight-bold mb-2">Historique des configurations</div>
            <v-table class="template-history" density="compact">
              <thead>
                <tr>
                  <th>Version</th>
                  <th>Statut</th>
                  <th>Exemple</th>
                  <th>Publication</th>
                  <th>Documents</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="template in selectedTemplateHistory" :key="template.id">
                  <td class="font-weight-medium">v{{ template.version }}</td>
                  <td>
                    <v-chip :color="templateStatusColor(template.status)" size="x-small" variant="tonal">
                      {{ templateStatusLabel(template.status) }}
                    </v-chip>
                  </td>
                  <td>
                    <code v-if="template.preview_example" class="sample-pill">{{ template.preview_example }}</code>
                    <span v-else class="text-medium-emphasis">—</span>
                  </td>
                  <td>{{ formatDate(template.published_at) }}</td>
                  <td>{{ template.documents_count ?? 0 }}</td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </template>
      </div>

      <!-- ── STEP 2 : Validation & Publication ── -->
      <div v-if="currentStep === 2">
        <div class="text-subtitle-1 font-weight-bold mb-4">Validation et publication</div>

        <v-row>
          <v-col cols="12" md="6">
            <v-card class="preview-card" elevation="0" rounded="lg">
              <v-card-title class="text-subtitle-2 font-weight-bold pa-4 pb-2">
                <v-icon class="mr-2" color="primary" size="18">mdi-eye-outline</v-icon>
                Prévisualisation du code
              </v-card-title>
              <v-card-text class="pa-4 pt-0">
                <div v-if="loadingPreview" class="d-flex align-center ga-2 py-4">
                  <v-progress-circular indeterminate size="20" width="2" />
                  <span class="text-caption">Génération…</span>
                </div>
                <template v-else>
                  <div class="preview-code-box mb-3">
                    <span class="preview-code">{{ previewCode || '—' }}</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mb-3">
                    Exemples générés :
                  </div>
                  <div class="d-flex flex-wrap ga-2">
                    <code
                      v-for="sample in previewSamples"
                      :key="sample.index"
                      class="sample-pill"
                    >
                      {{ sample.code }}
                    </code>
                  </div>
                </template>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card class="summary-card" elevation="0" rounded="lg">
              <v-card-title class="text-subtitle-2 font-weight-bold pa-4 pb-2">
                <v-icon class="mr-2" color="success" size="18">mdi-check-circle-outline</v-icon>
                Récapitulatif
              </v-card-title>
              <v-card-text class="pa-4 pt-0">
                <div class="summary-row">
                  <span class="summary-label">Type de document</span>
                  <span class="summary-value">
                    {{ selectedDocType?.name ?? '—' }}
                    <code v-if="selectedDocType" class="abbr-pill ml-1">{{ selectedDocType.abbreviation }}</code>
                  </span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Template actuel</span>
                  <span class="summary-value">
                    {{ selectedActiveTemplate ? `v${selectedActiveTemplate.version}` : 'Aucun' }}
                  </span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Version publiée</span>
                  <span class="summary-value">v{{ nextTemplateVersion }}</span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Séparateur</span>
                  <code class="abbr-pill">{{ activeSeparator }}</code>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Nombre de parties</span>
                  <span class="summary-value">{{ activeParts.length }}</span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Numéro séquentiel</span>
                  <v-chip
                    :color="hasSequentialPart ? 'success' : 'error'"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ hasSequentialPart ? 'Présent' : 'Manquant' }}
                  </v-chip>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Portée séquence</span>
                  <span class="summary-value">{{ sequencePartLabel }}</span>
                </div>
                <v-divider class="my-3" />
                <div class="text-caption text-medium-emphasis">
                  Structure des parties :
                </div>
                <div class="d-flex flex-wrap ga-1 mt-2">
                  <v-chip
                    v-for="(part, i) in activeParts"
                    :key="i"
                    :color="partChipColor(part.type)"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ part.label || part.type }}
                  </v-chip>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-alert
          v-if="structureErrors.length > 0"
          class="mt-4"
          density="comfortable"
          type="error"
          variant="tonal"
        >
          Corrigez les erreurs avant de publier :
          <ul class="mb-0 pl-4 mt-1">
            <li v-for="(err, i) in structureErrors" :key="i">{{ err }}</li>
          </ul>
        </v-alert>

        <v-alert
          v-else
          class="mt-4"
          density="comfortable"
          icon="mdi-check-circle"
          type="success"
          variant="tonal"
        >
          La structure est valide. Vous pouvez publier ce template.
        </v-alert>
      </div>
    </v-card-text>

    <v-divider />

    <!-- Footer actions -->
    <v-card-actions class="px-6 py-4">
      <v-btn v-if="currentStep > 0" variant="text" @click="currentStep--">
        <v-icon start>mdi-arrow-left</v-icon>
        Précédent
      </v-btn>
      <v-spacer />
      <v-btn variant="tonal" @click="$emit('close')">Fermer</v-btn>
      <v-btn
        v-if="currentStep < steps.length - 1"
        color="primary"
        :disabled="!canProceed"
        variant="flat"
        @click="nextStep"
      >
        Suivant
        <v-icon end>mdi-arrow-right</v-icon>
      </v-btn>
      <v-btn
        v-else
        color="success"
        :disabled="structureErrors.length > 0 || saving || templateSaving"
        :loading="saving || templateSaving"
        prepend-icon="mdi-publish"
        variant="flat"
        @click="publish"
      >
        Publier le template
      </v-btn>
    </v-card-actions>
  </v-card>

  <!-- Dialog : formulaire type documentaire -->
  <v-dialog v-model="docTypeDialog" max-width="560">
    <v-card rounded="lg">
      <v-card-title class="pa-4">
        {{ editingDocTypeId ? 'Modifier le type' : 'Nouveau type documentaire' }}
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-row dense>
          <v-col cols="12" md="8">
            <v-text-field
              v-model="docTypeForm.name"
              label="Nom *"
              placeholder="ex : Procédure"
              required
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="docTypeForm.abbreviation"
              label="Abréviation *"
              maxlength="10"
              placeholder="ex : PRC"
              required
              variant="outlined"
              @input="docTypeForm.abbreviation = docTypeForm.abbreviation.toUpperCase()"
            />
          </v-col>
          <v-col cols="12">
            <v-text-field
              v-model="docTypeForm.description"
              label="Description"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="docTypeForm.display_order"
              label="Ordre d'affichage"
              min="0"
              type="number"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-switch
              v-model="docTypeForm.is_active"
              color="success"
              inset
              label="Actif"
            />
            <div class="text-caption text-medium-emphasis mt-1">
              L’activation nécessite un template publié actif pour ce type.
            </div>
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn variant="text" @click="docTypeDialog = false">Annuler</v-btn>
        <v-btn
          color="primary"
          :disabled="!docTypeForm.name || !docTypeForm.abbreviation"
          :loading="docTypesLoading"
          variant="flat"
          @click="saveDocType"
        >
          Enregistrer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Dialog : confirmation suppression -->
  <v-dialog v-model="confirmDeleteDialog" max-width="420">
    <v-card rounded="lg">
      <v-card-title class="text-subtitle-1 font-weight-bold pa-4">Supprimer le type ?</v-card-title>
      <v-card-text class="text-body-2 text-medium-emphasis px-4">
        Cette action est irréversible. Les documents existants ne seront pas affectés.
      </v-card-text>
      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn variant="text" @click="confirmDeleteDialog = false">Annuler</v-btn>
        <v-btn color="error" :loading="docTypesLoading" variant="flat" @click="deleteDocType">
          Supprimer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import type { DocumentTypeCatalog } from '../../types/document.types'
import type { NomenclaturePart, NomenclatureTemplate } from '../../composables/useNomenclatureTemplates'
import { computed, ref } from 'vue'
import draggable from 'vuedraggable'
import { useToast } from '@/modules/shared/composables/useToast'
import { useDocumentTypeCatalogs } from '../../composables/useDocumentTypeCatalogs'
import { useNomenclatureTemplates } from '../../composables/useNomenclatureTemplates'

const props = defineProps<{
  siteId: number
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const toast = useToast()

const { items: documentTypeCatalogs, loading: docTypesLoading, error: docTypeError, fetchItems: fetchDocumentTypes, createItem, updateItem, deleteItem } = useDocumentTypeCatalogs()
const {
  templates,
  fetchTemplates,
  previewCode: fetchPreview,
  simulateSamples,
  createTemplate,
  saving: templateSaving,
} = useNomenclatureTemplates()

// ── Wizard state ──────────────────────────────────────────────
const currentStep = ref(0)
const saving = ref(false)

const steps = [
  { label: 'Types documentaires', desc: 'Catégories de documents' },
  { label: 'Structure du code', desc: 'Parties et séparateurs' },
  { label: 'Validation', desc: 'Prévisualisation et publication' },
]

// ── Step 0 : Types documentaires ─────────────────────────────
const docTypeDialog = ref(false)
const confirmDeleteDialog = ref(false)
const editingDocTypeId = ref<number | null>(null)
const pendingDeleteId = ref<number | null>(null)
const docTypeForm = ref({
  name: '',
  abbreviation: '',
  description: '',
  is_active: false,
  display_order: 0,
})

function openDocTypeForm (item?: DocumentTypeCatalog) {
  editingDocTypeId.value = item?.id ?? null
  docTypeForm.value = {
    name: item?.name ?? '',
    abbreviation: item?.abbreviation ?? '',
    description: item?.description ?? '',
    is_active: item?.is_active ?? false,
    display_order: item?.display_order ?? documentTypeCatalogs.value.length,
  }
  docTypeDialog.value = true
}

async function saveDocType () {
  const payload = {
    ...docTypeForm.value,
    abbreviation: docTypeForm.value.abbreviation.toUpperCase().trim(),
    site_id: props.siteId,
  }
  try {
    if (editingDocTypeId.value) {
      await updateItem(editingDocTypeId.value, payload)
      toast.success('Type mis à jour.')
    } else {
      await createItem(payload)
      toast.success('Type créé.')
    }
    docTypeDialog.value = false
    await fetchDocumentTypes({ site_id: props.siteId })
  } catch {
    toast.error('Impossible d\'enregistrer le type. Publiez d\'abord un template si vous souhaitez l\'activer.')
  }
}

function confirmDeleteDocType (id: number) {
  pendingDeleteId.value = id
  confirmDeleteDialog.value = true
}

async function deleteDocType () {
  if (!pendingDeleteId.value) return
  try {
    await deleteItem(pendingDeleteId.value)
    toast.success('Type supprimé.')
    confirmDeleteDialog.value = false
    await fetchDocumentTypes({ site_id: props.siteId })
  } catch {
    toast.error('Impossible de supprimer ce type.')
  }
}

// ── Step 1 : Structure du code ────────────────────────────────
const selectedDocTypeId = ref<number | null>(null)
const activeSeparator = ref('-')
const activeParts = ref<(NomenclaturePart & { id: string })[]>([])

const docTypeOptions = computed(() =>
  documentTypeCatalogs.value
    .filter(d => d.is_active)
    .map(d => ({ title: `${d.name} (${d.abbreviation})`, value: d.id })),
)

const selectedDocType = computed(() =>
  documentTypeCatalogs.value.find(d => d.id === selectedDocTypeId.value) ?? null,
)

const publishedTemplatesByTypeId = computed(() => {
  const map = new Map<number, NomenclatureTemplate>()
  const published = templates.value
    .filter(t => t.status === 'published' && t.is_active && t.document_type_catalog_id)
    .sort((a, b) => b.version - a.version)

  for (const template of published) {
    const typeId = template.document_type_catalog_id
    if (typeId && !map.has(typeId)) map.set(typeId, template)
  }

  return map
})

const selectedActiveTemplate = computed(() => {
  if (!selectedDocTypeId.value) return null
  return publishedTemplatesByTypeId.value.get(selectedDocTypeId.value) ?? null
})

const missingActiveTypes = computed(() =>
  documentTypeCatalogs.value.filter(item => item.is_active && !publishedTemplatesByTypeId.value.has(item.id)),
)

const nextTemplateVersion = computed(() => (selectedActiveTemplate.value?.version ?? 0) + 1)

function activeTemplateForType (typeId: number) {
  return publishedTemplatesByTypeId.value.get(typeId) ?? null
}

const selectedTemplateHistory = computed(() => {
  if (!selectedDocTypeId.value) return []

  return templates.value
    .filter(template => template.document_type_catalog_id === selectedDocTypeId.value)
    .sort((a, b) => b.version - a.version)
})

const partTypeOptions = [
  { title: 'Type de document', value: 'document_type' },
  { title: 'Code processus', value: 'process_code' },
  { title: 'Année', value: 'year' },
  { title: 'Mois', value: 'month' },
  { title: 'Code site', value: 'site_code' },
  { title: 'Texte libre', value: 'free_text' },
  { title: 'Personnalisé', value: 'custom' },
  { title: 'Numéro séquentiel', value: 'sequential_number' },
]

function partTypeLabel (type: string) {
  return partTypeOptions.find(option => option.value === type)?.title ?? type
}

function structureLabel (parts: NomenclaturePart[], separator: string) {
  const visibleParts = parts.filter(part => part.type !== 'separator')
  if (visibleParts.length === 0) return 'Structure vide'

  return visibleParts
    .map(part => part.label || partTypeLabel(part.type))
    .join(` ${separator} `)
}

function formatDate (value: string | null | undefined) {
  if (!value) return 'date non renseignée'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(new Date(value))
}

function templateStatusLabel (status: NomenclatureTemplate['status']) {
  const labels: Record<NomenclatureTemplate['status'], string> = {
    draft: 'Brouillon',
    published: 'Publié',
    archived: 'Archivé',
  }

  return labels[status]
}

function templateStatusColor (status: NomenclatureTemplate['status']) {
  const colors: Record<NomenclatureTemplate['status'], string> = {
    draft: 'warning',
    published: 'success',
    archived: 'grey',
  }

  return colors[status]
}

const sequenceScopeOptions = [
  { title: 'Global', value: 'global' },
  { title: 'Par type', value: 'by_type' },
  { title: 'Par type + processus', value: 'by_type_process' },
  { title: 'Par type + année', value: 'by_type_year' },
  { title: 'Par type + processus + année', value: 'by_type_process_year' },
  { title: 'Par type + processus + année + mois', value: 'by_type_process_year_month' },
]

const quickAddParts = [
  { type: 'document_type', label: 'Type doc.', icon: 'mdi-file-document-outline', unique: true },
  { type: 'process_code', label: 'Processus', icon: 'mdi-sitemap-outline', unique: true },
  { type: 'year', label: 'Année', icon: 'mdi-calendar-outline', unique: true },
  { type: 'month', label: 'Mois', icon: 'mdi-calendar-month-outline', unique: true },
  { type: 'site_code', label: 'Site', icon: 'mdi-map-marker-outline', unique: true },
  { type: 'free_text', label: 'Texte libre', icon: 'mdi-pencil-outline', unique: false },
  { type: 'custom', label: 'Personnalisé', icon: 'mdi-cog-outline', unique: false },
]

const availableQuickAddParts = computed(() => {
  return quickAddParts.filter(q => {
    if (!q.unique) return true
    return !activeParts.value.some(p => p.type === q.type)
  })
})

function getAvailablePartTypeOptions(currentType: string) {
  const uniqueTypes = ['document_type', 'process_code', 'year', 'month', 'site_code', 'sequential_number']
  return partTypeOptions.filter(opt => {
    if (opt.value === currentType) return true // Toujours autoriser le type actuel de la ligne
    if (uniqueTypes.includes(opt.value)) {
      return !activeParts.value.some(p => p.type === opt.value)
    }
    return true
  })
}

function makeDefaultPart (type: string): NomenclaturePart & { id: string } {
  const isAuto = ['year', 'month', 'sequential_number', 'site_code'].includes(type)
  const isLocked = ['year', 'month', 'site_code', 'sequential_number', 'document_type', 'process_code'].includes(type)
  
  let value: string | null = null
  if (type === 'year') value = new Date().getFullYear().toString()
  if (type === 'month') value = (new Date().getMonth() + 1).toString().padStart(2, '0')
  if (type === 'site_code') value = 'SIT'

  return {
    id: `part-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    order: activeParts.value.length + 1,
    type: type as NomenclaturePart['type'],
    label: partTypeOptions.find(o => o.value === type)?.title ?? type,
    length: type === 'sequential_number' ? 3 : type === 'year' ? 4 : type === 'month' ? 2 : 3,
    value,
    editable: !isLocked,
    auto: isAuto,
    sequence_scope: type === 'sequential_number' ? 'by_type_process' : undefined,
  }
}

function normalizeTemplatePart (part: NomenclaturePart, index: number): NomenclaturePart & { id: string } {
  let type = part.type

  if (part.type === 'separator') type = 'free_text'
  if ((part as any).type === 'token' && ['SEQUENCE', 'NUMERO', 'SEQ'].includes((part as any).token)) {
    type = 'sequential_number'
  }

  const defaults = makeDefaultPart(type)

  return {
    ...defaults,
    ...part,
    id: part.id ?? `part-${Date.now()}-${index}-${Math.random().toString(36).slice(2, 7)}`,
    type,
    order: index + 1,
    label: part.label || defaults.label,
    length: part.length || defaults.length,
    value: part.value ?? defaults.value,
    editable: part.editable ?? defaults.editable,
    auto: part.auto ?? defaults.auto,
    sequence_scope: part.sequence_scope ?? defaults.sequence_scope,
  }
}

function loadTemplateIntoBuilder (template: NomenclatureTemplate | null) {
  activeSeparator.value = template?.separator ?? '-'

  const loadedParts = (template?.format_structure ?? [])
    .filter(part => part.type !== 'separator')
    .map((part, index) => normalizeTemplatePart(part, index))

  activeParts.value = loadedParts.length > 0
    ? loadedParts
    : [
        makeDefaultPart('document_type'),
        makeDefaultPart('process_code'),
        makeDefaultPart('sequential_number'),
      ]

  reorderParts()
}

function addPart (quick: { type: string }) {
  activeParts.value.push(makeDefaultPart(quick.type))
}

function removePart (index: number) {
  activeParts.value.splice(index, 1)
  reorderParts()
}

function reorderParts () {
  const docTypeIndex = activeParts.value.findIndex(p => p.type === 'document_type')
  if (docTypeIndex > 0) {
    const [docTypePart] = activeParts.value.splice(docTypeIndex, 1)
    activeParts.value.unshift(docTypePart)
  }
  activeParts.value.forEach((p, i) => { p.order = i + 1 })
}

function onPartTypeChange (part: NomenclaturePart & { id: string }) {
  const defaults = makeDefaultPart(part.type)
  Object.assign(part, {
    label: defaults.label,
    length: defaults.length,
    value: null,
    editable: defaults.editable,
    auto: defaults.auto,
    sequence_scope: defaults.sequence_scope,
  })
}

function onDocTypeChange () {
  if (!selectedDocTypeId.value) {
    activeParts.value = []
    previewCode.value = ''
    previewSamples.value = []
    return
  }

  loadTemplateIntoBuilder(selectedActiveTemplate.value)
}

function configureDocType (typeId: number) {
  selectedDocTypeId.value = typeId
  onDocTypeChange()
  currentStep.value = 1
}

const hasSequentialPart = computed(() =>
  activeParts.value.some(p => p.type === 'sequential_number'),
)

const sequencePartLabel = computed(() => {
  const seq = activeParts.value.find(p => p.type === 'sequential_number')
  if (!seq) return '—'
  return sequenceScopeOptions.find(o => o.value === seq.sequence_scope)?.title ?? '—'
})

const structureErrors = computed(() => {
  if (!selectedDocTypeId.value) return []
  const errors: string[] = []
  if (activeParts.value.length === 0) errors.push('Ajoutez au moins une partie.')
  if (!hasSequentialPart.value) errors.push('La structure doit contenir un numéro séquentiel.')
  const seqCount = activeParts.value.filter(p => p.type === 'sequential_number').length
  if (seqCount > 1) errors.push('Un seul numéro séquentiel est autorisé.')
  for (const part of activeParts.value) {
    if (!part.label.trim()) errors.push(`Une partie n'a pas de libellé.`)
    if (part.length < 1) errors.push(`La longueur de "${part.label}" doit être ≥ 1.`)
  }
  return errors
})

function partChipColor (type: string): string {
  const map: Record<string, string> = {
    document_type: 'primary',
    process_code: 'teal',
    year: 'blue',
    month: 'cyan',
    site_code: 'purple',
    free_text: 'orange',
    custom: 'grey',
    sequential_number: 'success',
  }
  return map[type] ?? 'grey'
}

// ── Step 2 : Prévisualisation ─────────────────────────────────
const previewCode = ref('')
const previewSamples = ref<Array<{ code: string, index: number }>>([])
const loadingPreview = ref(false)

async function refreshPreview () {
  if (!selectedDocTypeId.value || activeParts.value.length === 0) return
  loadingPreview.value = true
  try {
    const docType = selectedDocType.value
    const contextParts = activeParts.value.map(p => ({
      ...p,
      value: p.type === 'document_type' ? (docType?.abbreviation ?? 'XXX') : p.value,
    }))
    previewCode.value = await fetchPreview(contextParts, activeSeparator.value)
    previewSamples.value = await simulateSamples(
      contextParts,
      activeSeparator.value,
      { document_type: docType?.abbreviation ?? 'XXX', process_code: 'PRC' },
      3,
    )
  } finally {
    loadingPreview.value = false
  }
}

// ── Navigation ────────────────────────────────────────────────
const canProceed = computed(() => {
  if (currentStep.value === 0) return documentTypeCatalogs.value.length > 0
  if (currentStep.value === 1) return !!selectedDocTypeId.value && structureErrors.value.length === 0
  return true
})

async function nextStep () {
  if (currentStep.value === 1) await refreshPreview()
  currentStep.value++
}

// ── Publication ───────────────────────────────────────────────
async function publish () {
  if (structureErrors.value.length > 0) return
  saving.value = true
  try {
    await createTemplate({
      site_id: props.siteId,
      document_type_catalog_id: selectedDocTypeId.value,
      name: `Template ${selectedDocType.value?.name ?? 'Document'} v${nextTemplateVersion.value}`,
      version: nextTemplateVersion.value,
      status: 'published',
      separator: activeSeparator.value,
      format_structure: activeParts.value.map(p => ({ ...p })),
      is_active: true,
    })
    toast.success('Template publié avec succès.')
    await fetchTemplates({ site_id: props.siteId })
    emit('saved')
    currentStep.value = 0
  } catch {
    toast.error('Impossible de publier le template.')
  } finally {
    saving.value = false
  }
}

// ── Init ──────────────────────────────────────────────────────
fetchDocumentTypes({ site_id: props.siteId })
fetchTemplates({ site_id: props.siteId })
</script>

<style scoped>
.nomenclature-wizard {
  border: 1px solid rgba(15, 23, 42, 0.1);
}

.wizard-header {
  background: linear-gradient(135deg, rgba(91, 141, 217, 0.06), rgba(16, 185, 129, 0.05));
}

/* Stepper */
.wizard-stepper {
  display: flex;
  align-items: flex-start;
  gap: 0;
}

.wizard-step {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  position: relative;
}

.wizard-step.clickable {
  cursor: pointer;
}

.step-bubble {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
  transition: all 0.25s ease;
}

.wizard-step.active .step-bubble {
  background: #3b82f6;
  color: #fff;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
}

.wizard-step.completed .step-bubble {
  background: #10b981;
  color: #fff;
}

.step-info {
  flex: 1;
}

.step-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.2;
}

.step-desc {
  font-size: 0.72rem;
  color: #94a3b8;
  line-height: 1.2;
}

.wizard-step.active .step-label { color: #3b82f6; }
.wizard-step.completed .step-label { color: #10b981; }

.step-connector {
  position: absolute;
  top: 16px;
  left: calc(100% - 8px);
  width: 24px;
  height: 2px;
  background: #e2e8f0;
  z-index: 0;
}

.step-connector.done { background: #10b981; }

/* Parts list */
.parts-list { min-height: 60px; }

.part-row {
  border: 1px solid #e2e8f0;
  background: #fff;
  transition: border-color 0.2s;
}

.part-row:hover { border-color: rgba(59, 130, 246, 0.35); }

.part-locked { background: rgba(148, 163, 184, 0.06); }

.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
.drag-locked { cursor: not-allowed !important; }

/* Preview */
.preview-card, .summary-card {
  border: 1px solid #e2e8f0;
  height: 100%;
}

.preview-code-box {
  background: #f1f5f9;
  border-radius: 10px;
  padding: 14px 16px;
  text-align: center;
}

.preview-code {
  font-family: 'Courier New', monospace;
  font-size: 1.3rem;
  font-weight: 700;
  color: #1e3a8a;
  letter-spacing: 0.05em;
}

.sample-pill {
  background: #e0f2fe;
  color: #0369a1;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 600;
}

.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px dashed #f1f5f9;
  font-size: 0.85rem;
}

.summary-row:last-child { border-bottom: none; }

.summary-label { color: #64748b; }
.summary-value { font-weight: 500; color: #0f172a; }

.template-structure {
  color: #475569;
  font-size: 0.78rem;
  line-height: 1.35;
  max-width: 360px;
}

.template-history {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

/* Badges */
.abbr-pill {
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #1e3a8a;
}
</style>
