<template>
  <v-card rounded="lg">
    <v-card-title class="pa-4 d-flex align-center justify-space-between">
      <span>{{ editingDocument ? 'Modifier le document' : 'Nouveau document' }}</span>
      <v-btn icon="mdi-close" size="small" variant="text" @click="$emit('cancel')" />
    </v-card-title>
    <v-divider />

    <v-card-text class="pa-4">
      <v-form ref="formRef" @submit.prevent="submit">
        <v-row>
          <!-- Processus -->
          <v-col cols="12" md="6">
            <v-select
              v-model="form.process_id"
              item-title="label"
              item-value="value"
              :items="processOptions"
              label="Processus *"
              :rules="[v => !!v || 'Le processus est obligatoire']"
              variant="outlined"
            />
            <v-alert
              v-if="processCodeWarning"
              class="mt-1"
              density="compact"
              type="warning"
              variant="tonal"
            >
              {{ processCodeWarning }}
            </v-alert>
          </v-col>

          <!-- Type de document -->
          <v-col cols="12" md="6">
            <v-select
              v-model="form.type"
              :items="typeOptions"
              label="Type de document *"
              :rules="[v => !!v || 'Le type est obligatoire']"
              variant="outlined"
            />
          </v-col>

          <!-- Prévisualisation du code -->
          <v-col cols="12">
            <v-alert
              border="start"
              color="info"
              density="comfortable"
              icon="mdi-barcode-scan"
              variant="tonal"
            >
              <div class="d-flex align-center justify-space-between flex-wrap ga-3">
                <div>
                  <div class="text-caption text-medium-emphasis">Code généré automatiquement</div>
                  <div class="text-subtitle-2 font-weight-bold font-mono mt-1">
                    {{ codePreview || 'Sélectionnez un type et un processus' }}
                  </div>
                  <div v-if="templateInfo" class="text-caption text-medium-emphasis mt-1">
                    Template v{{ templateInfo.version }} · ID {{ templateInfo.id }}
                  </div>
                </div>
                <v-btn
                  color="primary"
                  :disabled="!form.process_id || !form.type"
                  :loading="loadingPreview"
                  prepend-icon="mdi-refresh"
                  size="small"
                  variant="tonal"
                  @click="refreshPreview"
                >
                  Rafraîchir
                </v-btn>
              </div>
            </v-alert>
          </v-col>

          <!-- Parties dynamiques issues de la nomenclature -->
          <template v-if="dynamicParts.length > 0">
            <v-col cols="12">
              <v-divider class="mb-2" />
              <div class="text-caption text-medium-emphasis mb-3">
                <v-icon size="14">mdi-tune</v-icon>
                Informations complémentaires requises par la nomenclature
              </div>
            </v-col>
            <v-col
              v-for="part in dynamicParts"
              :key="part.id"
              cols="12"
              md="6"
            >
              <!-- Texte libre -->
              <v-text-field
                v-if="part.type === 'free_text' || part.type === 'custom'"
                v-model="dynamicValues[part.id]"
                density="comfortable"
                :hint="`Max ${part.length} caractères`"
                :label="part.label + (part.editable ? ' *' : '')"
                :maxlength="part.length"
                persistent-hint
                :rules="part.editable ? [v => !!v || `${part.label} est obligatoire`] : []"
                variant="outlined"
              />
              <!-- Département / service (liste fixe si options définies) -->
              <v-select
                v-else-if="part.options && part.options.length > 0"
                v-model="dynamicValues[part.id]"
                density="comfortable"
                :items="part.options"
                :label="part.label + ' *'"
                :rules="[v => !!v || `${part.label} est obligatoire`]"
                variant="outlined"
              />
            </v-col>
          </template>

          <!-- ── Rattachement module / sous-module / section ── -->
          <v-col cols="12">
            <v-divider class="mb-2" />
            <div class="d-flex align-center ga-2 mb-3">
              <v-icon color="primary" size="16">mdi-sitemap-outline</v-icon>
              <span class="text-caption font-weight-medium">Rattachement dans la sidebar</span>
              <v-chip color="info" size="x-small" variant="tonal">Optionnel</v-chip>
            </div>
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.source_module"
              clearable
              density="comfortable"
              hide-details
              :items="moduleOptions"
              label="Module"
              variant="outlined"
              @update:model-value="onModuleChange"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.source_submodule"
              clearable
              density="comfortable"
              :disabled="!form.source_module"
              hide-details
              :items="subModuleOptions"
              label="Sous-module"
              variant="outlined"
              @update:model-value="onSubModuleChange"
            />
          </v-col>

          <v-col v-if="sectionOptions.length > 0" cols="12" md="4">
            <v-select
              v-model="form.source_section"
              clearable
              density="comfortable"
              :disabled="!form.source_submodule"
              hide-details
              :items="sectionOptions"
              label="Section"
              variant="outlined"
            />
          </v-col>

          <!-- Nom du document -->
          <v-col cols="12">
            <v-text-field
              v-model="form.nom"
              label="Nom du document *"
              :rules="[v => !!v || 'Le nom est obligatoire']"
              variant="outlined"
            />
          </v-col>

          <!-- Description -->
          <v-col cols="12">
            <v-textarea
              v-model="form.description"
              label="Description"
              rows="3"
              variant="outlined"
            />
          </v-col>

          <!-- État + Date création -->
          <v-col cols="12" md="6">
            <v-select
              v-model="form.etat"
              :items="etatOptions"
              label="État *"
              :rules="[v => !!v || 'L\'état est obligatoire']"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.date_creation"
              label="Date de création *"
              :rules="[v => !!v || 'La date est obligatoire']"
              type="date"
              variant="outlined"
            />
          </v-col>

          <!-- Périodicité révision -->
          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="form.periodicite_revision"
              hint="Laisser vide si pas de révision périodique"
              label="Périodicité de révision (mois)"
              min="1"
              persistent-hint
              type="number"
              variant="outlined"
            />
          </v-col>

          <!-- Fichier -->
          <v-col cols="12" md="6">
            <v-file-input
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods"
              :hint="fileHint"
              :label="isProcedureType ? 'Fichier *' : 'Fichier (optionnel)'"
              persistent-hint
              variant="outlined"
              @change="handleFileChange"
            />
          </v-col>
        </v-row>
      </v-form>
    </v-card-text>

    <v-divider />
    <v-card-actions class="px-4 py-3">
      <v-spacer />
      <v-btn variant="text" @click="$emit('cancel')">Annuler</v-btn>
      <v-btn
        color="primary"
        :loading="loading"
        prepend-icon="mdi-content-save"
        variant="flat"
        @click="submit"
      >
        Enregistrer
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import type { UnifiedDocument } from '../../types/document-unified.types'
import type { NomenclaturePart } from '../../composables/useNomenclatureTemplates'
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from '@/modules/shared/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useNomenclatureTemplates } from '../../composables/useNomenclatureTemplates'
import { useDocuments } from '../../composables/useDocuments'
import { useAccessCatalog } from '../../composables/useAccessCatalog'

interface ProcessOption { label: string, value: number, code?: string }
interface TypeOption { title: string, value: string }

const props = defineProps<{
  siteId: number
  processOptions: ProcessOption[]
  typeOptions: TypeOption[]
  processCodeById: Map<number, string>
  editingDocument?: UnifiedDocument | null
}>()

const emit = defineEmits<{
  cancel: []
  saved: [doc: UnifiedDocument]
}>()

const toast = useToast()
const authStore = useAuthStore()
const { resolveActiveTemplate } = useNomenclatureTemplates()
const { createDocument, loading } = useDocuments()
const { catalog, fetchCatalog } = useAccessCatalog()

// ── Form state ────────────────────────────────────────────────
const formRef = ref()
const form = ref({
  process_id: props.editingDocument?.process_id ?? null as number | null,
  type: props.editingDocument?.type ?? (props.typeOptions[0]?.value ?? 'PRC'),
  nom: props.editingDocument?.nom ?? props.editingDocument?.title ?? '',
  description: props.editingDocument?.description ?? '',
  etat: props.editingDocument?.etat ?? 'a_etablir',
  date_creation: props.editingDocument?.date_creation?.split('T')[0] ?? new Date().toISOString().split('T')[0],
  periodicite_revision: props.editingDocument?.periodicite_revision ?? null as number | null,
  fichier: null as File | null,
  source_module: (props.editingDocument?.metadata as any)?.source_context?.module ?? '' as string,
  source_submodule: (props.editingDocument?.metadata as any)?.source_context?.submodule ?? '' as string,
  source_section: (props.editingDocument?.metadata as any)?.source_context?.section ?? '' as string,
})

// ── Module / sous-module / section cascadés ───────────────────
const moduleOptions = computed(() =>
  catalog.value.modules
    .filter(m => m.is_active)
    .sort((a, b) => a.order - b.order)
    .map(m => ({ title: m.name, value: m.code })),
)

const subModuleOptions = computed(() => {
  if (!form.value.source_module) return []
  return catalog.value.sub_modules
    .filter(sm => sm.module_code === form.value.source_module && sm.is_active)
    .sort((a, b) => a.order - b.order)
    .map(sm => ({ title: sm.name, value: sm.code }))
})

const sectionOptions = computed(() => {
  if (!form.value.source_submodule) return []
  return catalog.value.sections
    .filter(s => s.sub_module_code === form.value.source_submodule && s.is_active)
    .sort((a, b) => a.order - b.order)
    .map(s => ({ title: s.name, value: s.code }))
})

function onModuleChange () {
  form.value.source_submodule = ''
  form.value.source_section = ''
}

function onSubModuleChange () {
  form.value.source_section = ''
}

const etatOptions = [
  { title: 'À établir', value: 'a_etablir' },
  { title: 'En cours', value: 'en_cours' },
  { title: 'Terminé', value: 'termine' },
]

// ── Code preview ──────────────────────────────────────────────
const codePreview = ref('')
const loadingPreview = ref(false)
const templateInfo = ref<{ id: number, version: number } | null>(null)
const activeTemplate = ref<NomenclaturePart[]>([])

async function refreshPreview () {
  if (!form.value.process_id || !form.value.type) {
    codePreview.value = ''
    templateInfo.value = null
    activeTemplate.value = []
    return
  }
  const processCode = props.processCodeById.get(form.value.process_id)
  if (!processCode) return

  loadingPreview.value = true
  try {
    const result = await resolveActiveTemplate({
      site_id: props.siteId,
      type: form.value.type,
      process_id: form.value.process_id,
      processus: processCode,
    })
    if (result) {
      codePreview.value = result.code
      templateInfo.value = result.nomenclature_template_id
        ? { id: result.nomenclature_template_id, version: result.nomenclature_template_version ?? 1 }
        : null
    }
  } finally {
    loadingPreview.value = false
  }
}

// ── Dynamic parts ─────────────────────────────────────────────
/**
 * Parties de la nomenclature qui nécessitent une saisie utilisateur :
 * free_text et custom (les autres sont auto-générées).
 */
const dynamicParts = computed<(NomenclaturePart & { id: string })[]>(() => {
  return activeTemplate.value
    .filter(p => ['free_text', 'custom'].includes(p.type) && p.editable)
    .map((p, i) => ({ ...p, id: p.id ?? `dyn-${i}` }))
})

const dynamicValues = ref<Record<string, string>>({})

// ── Watchers ──────────────────────────────────────────────────
watch(
  () => [form.value.process_id, form.value.type],
  () => { void refreshPreview() },
)

// ── Computed helpers ──────────────────────────────────────────
const processCodeWarning = computed(() => {
  if (!form.value.process_id) return null
  const code = props.processCodeById.get(form.value.process_id)
  if (!code) {
    return 'Aucune nomenclature définie pour ce processus. Configurez-la dans "Nomenclatures".'
  }
  return null
})

const isProcedureType = computed(() =>
  ['PRC', 'PRD'].includes(form.value.type),
)

const fileHint = computed(() =>
  isProcedureType.value
    ? 'Fichier obligatoire pour une procédure (PDF, Word…)'
    : 'PDF, Word, Excel, PowerPoint — max 20 Mo',
)

// ── File handling ─────────────────────────────────────────────
function handleFileChange (e: Event) {
  const target = e.target as HTMLInputElement
  form.value.fichier = target.files?.[0] ?? null
}

// ── Submit ────────────────────────────────────────────────────
async function submit () {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (!form.value.process_id) {
    toast.error('Le processus est obligatoire.')
    return
  }
  const processCode = props.processCodeById.get(form.value.process_id)
  if (!processCode) {
    toast.error('Aucune nomenclature définie pour ce processus.')
    return
  }
  if (isProcedureType.value && !form.value.fichier && !props.editingDocument) {
    toast.error('Le fichier est obligatoire pour une procédure.')
    return
  }

  const formData = new FormData()
  formData.append('site_id', String(props.siteId))
  formData.append('process_id', String(form.value.process_id))
  formData.append('processus', processCode)
  formData.append('type', form.value.type)
  // Champ moderne : title (plus nom)
  formData.append('title', form.value.nom)
  if (form.value.description) formData.append('description', form.value.description)
  // Champ moderne : etat (état de rédaction)
  formData.append('etat', form.value.etat)
  // needs_verification : true par défaut (workflow complet)
  formData.append('needs_verification', '1')
  formData.append('source_type', 'inventory')
  if (form.value.periodicite_revision) {
    formData.append('periodicite_revision', String(form.value.periodicite_revision))
  }
  if (form.value.fichier) formData.append('fichier', form.value.fichier)

  // Rattachement module / sous-module / section
  if (form.value.source_module) formData.append('source_module', form.value.source_module)
  if (form.value.source_submodule) formData.append('source_submodule', form.value.source_submodule)
  if (form.value.source_section) formData.append('source_section', form.value.source_section)

  // Ajouter les valeurs dynamiques
  for (const [key, value] of Object.entries(dynamicValues.value)) {
    if (value) formData.append(`dynamic_parts[${key}]`, value)
  }

  try {
    const doc = await createDocument(formData)
    toast.success('Document enregistré avec succès.')
    emit('saved', doc)
  } catch (err: any) {
    const msg = err?.response?.data?.message ?? 'Impossible d\'enregistrer le document.'
    toast.error(msg)
  }
}

// ── Init ──────────────────────────────────────────────────────
if (props.editingDocument) void refreshPreview()

onMounted(() => {
  void fetchCatalog()
})
</script>

<style scoped>
.font-mono {
  font-family: 'Courier New', monospace;
}
</style>
