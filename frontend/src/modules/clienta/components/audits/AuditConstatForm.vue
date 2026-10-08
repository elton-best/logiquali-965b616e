<template>
  <v-container class="constat-form pa-4" fluid>
    <v-card class="mx-auto" elevation="3" max-width="800">
      <v-card-title class="bg-primary text-white">
        <v-icon class="mr-2">mdi-clipboard-text</v-icon>
        {{ isEdit ? 'Modifier le constat' : 'Nouveau constat d\'audit' }}
      </v-card-title>

      <v-card-text class="pt-6">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <!-- Type de constat -->
          <v-select
            v-model="form.type"
            :items="findingTypes"
            label="Type de constat *"
            prepend-icon="mdi-format-list-bulleted-type"
            :rules="[rules.required]"
            variant="outlined"
            @update:model-value="updateDeadline"
          >
            <template #item="{ props: optionProps, item }">
              <v-list-item v-bind="optionProps">
                <template #prepend>
                  <v-icon :color="item.raw.color">{{ item.raw.icon }}</v-icon>
                </template>
              </v-list-item>
            </template>
          </v-select>

          <!-- Titre -->
          <v-text-field
            v-model="form.title"
            class="mt-4"
            counter="255"
            label="Titre du constat *"
            prepend-icon="mdi-format-title"
            :rules="[rules.required]"
            variant="outlined"
          />

          <!-- Description -->
          <v-textarea
            v-model="form.description"
            class="mt-4"
            counter="2000"
            label="Description détaillée *"
            prepend-icon="mdi-text"
            rows="4"
            :rules="[rules.required]"
            variant="outlined"
          />

          <!-- Preuves -->
          <v-textarea
            v-model="form.evidence"
            class="mt-4"
            hint="Documents consultés, témoignages, observations directes..."
            label="Preuves et éléments factuels"
            persistent-hint
            prepend-icon="mdi-file-document"
            rows="3"
            variant="outlined"
          />

          <!-- Exigence ISO -->
          <v-text-field
            v-model="form.requirement"
            class="mt-4"
            label="Exigence normative"
            placeholder="Ex: ISO 9001:2015 § 8.4.1"
            prepend-icon="mdi-certificate"
            variant="outlined"
          />

          <!-- Clause ISO -->
          <v-select
            v-model="form.clause_iso"
            class="mt-4"
            clearable
            :items="isoClauses"
            label="Clause ISO"
            prepend-icon="mdi-format-list-numbered"
            variant="outlined"
          />

          <!-- Processus -->
          <v-select
            v-model="form.process_id"
            class="mt-4"
            clearable
            item-title="name"
            item-value="id"
            :items="processes"
            label="Processus concerné"
            prepend-icon="mdi-sitemap"
            variant="outlined"
          />

          <!-- Localisation -->
          <v-text-field
            v-model="form.location"
            class="mt-4"
            label="Localisation"
            placeholder="Ex: Atelier production - Zone A2"
            prepend-icon="mdi-map-marker"
            variant="outlined"
          />

          <!-- Axes QHSE -->
          <v-select
            v-model="form.qhse_axes"
            chips
            class="mt-4"
            :items="qhseAxes"
            label="Axes QHSE concernés"
            multiple
            prepend-icon="mdi-shield-check"
            variant="outlined"
          />

          <!-- Sévérité -->
          <v-select
            v-model="form.severity"
            class="mt-4"
            :items="severityLevels"
            label="Sévérité"
            prepend-icon="mdi-alert"
            variant="outlined"
          >
            <template #item="{ props: optionProps, item }">
              <v-list-item v-bind="optionProps">
                <template #prepend>
                  <v-chip :color="item.raw.color" size="small">
                    {{ item.title }}
                  </v-chip>
                </template>
              </v-list-item>
            </template>
          </v-select>

          <!-- Priorité -->
          <v-slider
            v-model="form.priority"
            class="mt-4"
            label="Priorité"
            :max="5"
            :min="1"
            prepend-icon="mdi-flag"
            show-ticks="always"
            :step="1"
            thumb-label="always"
          >
            <template #thumb-label="{ modelValue }">
              <v-icon v-if="modelValue >= 4" size="small">mdi-fire</v-icon>
              <span v-else>{{ modelValue }}</span>
            </template>
          </v-slider>

          <!-- Pour NC uniquement -->
          <v-expand-transition>
            <div v-if="isNonConformity" class="mt-6">
              <v-divider class="mb-4" />
              <h3 class="text-subtitle-1 mb-4">
                <v-icon class="mr-2">mdi-wrench</v-icon>
                Informations complémentaires (NC)
              </h3>

              <!-- Cause racine -->
              <v-textarea
                v-model="form.root_cause"
                hint="Analyse 5 Pourquoi, diagramme Ishikawa..."
                label="Cause racine"
                persistent-hint
                prepend-icon="mdi-tree"
                rows="2"
                variant="outlined"
              />

              <!-- Action immédiate -->
              <v-textarea
                v-model="form.immediate_action"
                class="mt-4"
                hint="Mesures prises sur le terrain"
                label="Action immédiate"
                persistent-hint
                prepend-icon="mdi-flash"
                rows="2"
                variant="outlined"
              />

              <!-- Date limite -->
              <v-text-field
                v-model="form.deadline"
                class="mt-4"
                :hint="deadlineHint"
                label="Date limite de traitement"
                persistent-hint
                prepend-icon="mdi-calendar-clock"
                type="date"
                variant="outlined"
              />
            </div>
          </v-expand-transition>

          <!-- Upload photos -->
          <v-divider class="my-6" />
          <h3 class="text-subtitle-1 mb-4">
            <v-icon class="mr-2">mdi-camera</v-icon>
            Photos et pièces jointes
          </h3>

          <v-file-input
            v-model="files"
            accept="image/*,application/pdf"
            chips
            counter
            label="Ajouter des photos"
            multiple
            prepend-icon="mdi-camera-plus"
            show-size
            variant="outlined"
            @update:model-value="handleFileUpload"
          >
            <template #selection="{ fileNames }">
              <template v-for="fileName in fileNames" :key="fileName">
                <v-chip class="mr-2" label size="small">
                  {{ fileName }}
                </v-chip>
              </template>
            </template>
          </v-file-input>

          <!-- Preview photos -->
          <v-row v-if="form.attachments.length > 0" class="mt-2">
            <v-col
              v-for="(attachment, index) in form.attachments"
              :key="index"
              cols="6"
              md="3"
              sm="4"
            >
              <v-card>
                <v-img
                  v-if="attachment.type === 'image'"
                  aspect-ratio="1"
                  cover
                  :src="attachment.url"
                />
                <v-card-text v-else class="text-center">
                  <v-icon size="48">mdi-file-pdf-box</v-icon>
                  <div class="text-caption mt-2">{{ attachment.name }}</div>
                </v-card-text>
                <v-card-actions class="justify-end">
                  <v-btn
                    color="error"
                    icon
                    size="small"
                    @click="removeAttachment(index)"
                  >
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn
          variant="text"
          @click="handleCancel"
        >
          Annuler
        </v-btn>
        <v-spacer />
        <v-btn
          color="primary"
          :loading="loading"
          variant="elevated"
          @click="handleSubmit"
        >
          <v-icon class="mr-2">mdi-content-save</v-icon>
          {{ isEdit ? 'Modifier' : 'Enregistrer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuditStore } from '@/stores/improvement/auditStore'

  // Props
  const props = defineProps<{
    auditId: number
    findingId?: number
  }>()

  // Emits
  const emit = defineEmits<{
    saved: []
    cancelled: []
  }>()

  const router = useRouter()
  const store = useAuditStore()

  // State
  const formRef = ref<any>(null)
  const loading = ref(false)
  const files = ref<File[]>([])
  const processes = ref<any[]>([])

  const form = ref({
    type: 'observation',
    title: '',
    description: '',
    evidence: '',
    requirement: '',
    clause_iso: null,
    process_id: null,
    location: '',
    qhse_axes: ['quality'],
    severity: 'medium',
    priority: 2,
    root_cause: '',
    immediate_action: '',
    deadline: '',
    attachments: [] as any[],
  })

  // Data
  const findingTypes = [
    { value: 'nc_major', title: 'NC Majeure', icon: 'mdi-alert-octagon', color: 'error' },
    { value: 'nc_minor', title: 'NC Mineure', icon: 'mdi-alert', color: 'warning' },
    { value: 'observation', title: 'Observation', icon: 'mdi-eye', color: 'info' },
    { value: 'opportunity', title: 'Opportunité', icon: 'mdi-lightbulb', color: 'success' },
  ]

  const isoClauses = [
    '4.1', '4.2', '4.3', '4.4',
    '5.1', '5.2', '5.3',
    '6.1', '6.2', '6.3',
    '7.1', '7.2', '7.3', '7.4', '7.5',
    '8.1', '8.2', '8.3', '8.4', '8.5', '8.6', '8.7',
    '9.1', '9.2', '9.3',
    '10.1', '10.2', '10.3',
  ]

  const qhseAxes = [
    { value: 'quality', title: 'Qualité' },
    { value: 'health', title: 'Santé' },
    { value: 'safety', title: 'Sécurité' },
    { value: 'environment', title: 'Environnement' },
  ]

  const severityLevels = [
    { value: 'low', title: 'Faible', color: 'success' },
    { value: 'medium', title: 'Moyenne', color: 'warning' },
    { value: 'high', title: 'Élevée', color: 'error' },
    { value: 'critical', title: 'Critique', color: 'red-darken-2' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Champ requis',
  }

  // Computed
  const isEdit = computed(() => !!props.findingId)
  const isNonConformity = computed(() =>
    ['nc_major', 'nc_minor'].includes(form.value.type),
  )
  const deadlineHint = computed(() => {
    if (form.value.type === 'nc_major') return '15 jours maximum'
    if (form.value.type === 'nc_minor') return '30 jours maximum'
    return '60 jours recommandés'
  })

  // Methods
  function updateDeadline () {
    const today = new Date()
    let days = 60 // default

    if (form.value.type === 'nc_major') days = 15
    else if (form.value.type === 'nc_minor') days = 30

    const deadline = new Date(today)
    deadline.setDate(deadline.getDate() + days)

    form.value.deadline = deadline.toISOString().split('T')[0] || ''
  }

  function handleFileUpload () {
    if (!files.value) return

    for (const file of files.value) {
      const reader = new FileReader()
      reader.addEventListener('load', e => {
        form.value.attachments.push({
          name: file.name,
          type: file.type.startsWith('image/') ? 'image' : 'pdf',
          url: e.target?.result as string,
          size: file.size,
        })
      })
      reader.readAsDataURL(file)
    }
  }

  function removeAttachment (index: number) {
    form.value.attachments.splice(index, 1)
  }

  async function handleSubmit () {
    const valid = await formRef.value?.validate()
    if (!valid) return

    loading.value = true
    try {
      await store.addFinding(props.auditId, form.value)
      emit('saved')
      router.back()
    } catch (error) {
      console.error('Erreur:', error)
    } finally {
      loading.value = false
    }
  }

  function handleCancel () {
    emit('cancelled')
    router.back()
  }

  async function loadData () {
    // Charger les processus disponibles
    processes.value = [] // À charger depuis le store processStore
  }

  onMounted(() => {
    loadData()
    updateDeadline()
  })

  watch(() => form.value.type, () => {
    updateDeadline()
  })
</script>

<style scoped>
.constat-form {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

/* Mobile optimizations */
@media (max-width: 600px) {
  .v-card {
    margin: 0 !important;
    border-radius: 0 !important;
  }

  .v-card-title {
    font-size: 1.1rem !important;
  }
}
</style>
