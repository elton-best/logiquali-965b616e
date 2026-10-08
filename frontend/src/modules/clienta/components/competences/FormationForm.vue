<template>
  <v-form ref="formRef" @submit.prevent="handleSubmit">
    <v-row>
      <v-col cols="12">
        <v-text-field
          v-model="formData.designation"
          density="comfortable"
          label="Désignation / Thème de la formation *"
          prepend-inner-icon="mdi-book-education"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-alert type="info" variant="tonal">
          Formation ponctuelle = dates de début et fin. Formation récurrente = date de référence + fréquence.
        </v-alert>
      </v-col>

      <v-col cols="12" md="6">
        <v-select
          v-model="formData.frequency"
          density="comfortable"
          :items="filteredFrequencyOptions"
          label="Type de formation *"
          prepend-inner-icon="mdi-repeat"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="6">
        <v-select
          v-model="formData.periodMode"
          density="comfortable"
          :items="periodModeOptions"
          label="Mode de planification *"
          prepend-inner-icon="mdi-calendar-range"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <div class="d-flex flex-wrap gap-2 align-center mb-2">
          <v-chip color="primary" size="small" variant="tonal">
            {{ collaborators.length }} collaborateur(s) filtré(s)
          </v-chip>
          <v-chip v-if="formData.targetUserIds.length" color="success" size="small" variant="tonal">
            {{ formData.targetUserIds.length }} sélectionné(s)
          </v-chip>
          <v-spacer />
          <v-btn
            size="small"
            variant="text"
            @click="formData.targetUserIds = selectVisibleCollaborators(formData.targetUserIds)"
          >
            Sélectionner la liste filtrée
          </v-btn>
          <v-btn
            size="small"
            variant="text"
            @click="formData.targetUserIds = []"
          >
            Vider
          </v-btn>
        </div>
      </v-col>

      <v-col cols="12" md="3">
        <v-text-field
          v-model="collaboratorFilters.search"
          clearable
          density="comfortable"
          hide-details
          label="Rechercher"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="3">
        <v-select
          v-model="collaboratorFilters.siteId"
          clearable
          density="comfortable"
          hide-details
          :items="siteOptions"
          item-title="title"
          item-value="value"
          label="Site"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="3">
        <v-select
          v-model="collaboratorFilters.department"
          clearable
          density="comfortable"
          hide-details
          :items="departmentOptions"
          item-title="title"
          item-value="value"
          label="Département / service"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="3">
        <v-text-field
          v-model="collaboratorFilters.jobTitle"
          clearable
          density="comfortable"
          hide-details
          label="Poste"
          prepend-inner-icon="mdi-briefcase-outline"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="6">
        <v-autocomplete
          v-model="formData.targetUserIds"
          chips
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="id"
          :items="collaborators"
          label="Personnel ciblé *"
          :loading="loadingCollaborators"
          multiple
          prepend-inner-icon="mdi-account-group"
          :rules="[rules.required]"
          variant="outlined"
        >
          <template #item="{ props: itemProps, item }">
            <v-list-item
              v-bind="itemProps"
              :subtitle="item.raw.department || item.raw.site_name || item.raw.email"
              :title="item.raw.full_name"
            />
          </template>
        </v-autocomplete>
      </v-col>

      <v-col v-if="formData.periodMode === 'standard'" cols="12" md="3">
        <v-select
          v-model="standardPeriod.unit"
          density="comfortable"
          :items="standardPeriodUnits"
          label="Type de récurrence *"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="formData.periodMode === 'standard'" cols="12" md="3">
        <v-select
          v-model="standardPeriod.year"
          density="comfortable"
          :items="yearOptions"
          label="Année *"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="formData.periodMode === 'standard'" cols="12" md="6">
        <v-select
          v-model="standardPeriod.value"
          density="comfortable"
          :items="standardValueOptions"
          label="Référence *"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="formData.periodMode === 'custom'" cols="12" md="6">
        <v-text-field
          v-model="formData.dateDebut"
          density="comfortable"
          label="Date de début *"
          prepend-inner-icon="mdi-calendar-start"
          :rules="[rules.required]"
          type="date"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="formData.periodMode === 'custom'" cols="12" md="6">
        <v-text-field
          v-model="formData.dateFin"
          density="comfortable"
          label="Date de fin *"
          prepend-inner-icon="mdi-calendar-end"
          :rules="[rules.required, rules.dateAfter]"
          type="date"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="6">
        <v-autocomplete
          v-model="formData.organizerUserId"
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="id"
          :items="collaborators"
          label="Responsable formation (organisateur) *"
          :loading="loadingCollaborators"
          prepend-inner-icon="mdi-account-star"
          :rules="[rules.required]"
          variant="outlined"
        >
          <template #item="{ props: itemProps, item }">
            <v-list-item
              v-bind="itemProps"
              :subtitle="item.raw.department || item.raw.site_name || item.raw.email"
              :title="item.raw.full_name"
            />
          </template>
        </v-autocomplete>
      </v-col>

      <!-- Formateur : interne ou externe -->
      <v-col cols="12" md="6">
        <v-select
          v-model="formateurType"
          density="comfortable"
          :items="[{ title: 'Interne', value: 'interne' }, { title: 'Externe', value: 'externe' }]"
          label="Type de formateur *"
          prepend-inner-icon="mdi-account-tie"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="formateurType === 'interne'" cols="12" md="6">
        <v-autocomplete
          v-model="formData.formateurUserId"
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="id"
          :items="collaborators"
          label="Formateur (interne) *"
          :loading="loadingCollaborators"
          prepend-inner-icon="mdi-account-tie"
          :rules="[rules.required]"
          variant="outlined"
        >
          <template #item="{ props: itemProps, item }">
            <v-list-item
              v-bind="itemProps"
              :subtitle="item.raw.department || item.raw.site_name || item.raw.email"
              :title="item.raw.full_name"
            />
          </template>
        </v-autocomplete>
      </v-col>

      <v-col v-if="formateurType === 'externe'" cols="12" md="6">
        <v-text-field
          v-model="formData.formateur"
          density="comfortable"
          label="Formateur (externe) *"
          prepend-inner-icon="mdi-account-tie"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-text-field
          v-model="formData.periodLabel"
          density="comfortable"
          hint="Libellé métier optionnel (ex: T2 2026 / Campagne Mai)"
          label="Libellé de référence"
          persistent-hint
          prepend-inner-icon="mdi-label-outline"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-textarea
          v-model="formData.observations"
          density="comfortable"
          label="Observations / Commentaires"
          prepend-inner-icon="mdi-comment-text"
          rows="3"
          variant="outlined"
        />
      </v-col>
    </v-row>

    <v-divider class="my-4" />

    <div class="d-flex justify-end gap-2">
      <v-btn variant="text" @click="$emit('cancel')">
        Annuler
      </v-btn>
      <v-btn color="primary" :loading="loading" type="submit" variant="flat">
        {{ isEdit ? 'Mettre à jour' : 'Créer' }}
      </v-btn>
    </div>
  </v-form>
</template>

<script setup lang="ts">
  import type { FormationFormData, FormationFrequency } from '../../types/formation.types'
  import { computed, reactive, ref, watch } from 'vue'
  import { useCollaborators } from '../../composables/useCollaborators'

  interface Props {
    initialData?: Partial<FormationFormData>
    isEdit?: boolean
    loading?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    loading: false,
  })

  const emit = defineEmits<{
    submit: [data: FormationFormData]
    cancel: []
  }>()

  const formRef = ref()
  const {
    collaborators,
    collaboratorFilters,
    departmentOptions,
    loadingCollaborators,
    selectVisibleCollaborators,
    siteOptions,
  } = useCollaborators()

  const months = [
    'Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin',
    'Juil', 'Aout', 'Sept', 'Oct', 'Nov', 'Dec',
  ]

  const frequencyOptions: { title: string, value: FormationFrequency }[] = [
    { title: 'Ponctuelle', value: 'ponctuelle' },
    { title: 'Annuelle', value: 'annuelle' },
    { title: 'Semestrielle', value: 'semestrielle' },
    { title: 'Trimestrielle', value: 'trimestrielle' },
    { title: 'Mensuelle', value: 'mensuelle' },
    { title: 'Biennale', value: 'biennale' },
    { title: 'Sur demande', value: 'sur_demande' },
  ]

  const recurringFrequencies: FormationFrequency[] = [
    'annuelle',
    'semestrielle',
    'trimestrielle',
    'mensuelle',
    'biennale',
    'sur_demande',
  ]

  const periodModeOptions = [
    { title: 'Récurrente', value: 'standard' },
    { title: 'Ponctuelle', value: 'custom' },
  ]

  const standardPeriodUnits = [
    { title: 'Mois', value: 'month' },
    { title: 'Trimestre', value: 'quarter' },
  ]

  const currentYear = new Date().getFullYear()
  const yearOptions = Array.from({ length: 6 }).map((_, index) => currentYear - 1 + index)

  const standardPeriod = reactive({
    unit: 'month' as 'month' | 'quarter',
    year: currentYear,
    value: 1,
  })

  const standardValueOptions = computed(() => {
    if (standardPeriod.unit === 'quarter') {
      return [
        { title: 'T1', value: 1 },
        { title: 'T2', value: 2 },
        { title: 'T3', value: 3 },
        { title: 'T4', value: 4 },
      ]
    }

    return months.map((month, index) => ({ title: month, value: index + 1 }))
  })

  const filteredFrequencyOptions = computed(() => {
    if (formData.periodMode === 'custom') {
      return frequencyOptions.filter(option => option.value === 'ponctuelle')
    }
    return frequencyOptions.filter(option => recurringFrequencies.includes(option.value))
  })

  function inferStandardPeriod () {
    if (!props.initialData?.dateDebut || !props.initialData?.dateFin) return
    const start = new Date(props.initialData.dateDebut)
    const end = new Date(props.initialData.dateFin)
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return

    const startIsFirstDay = start.getDate() === 1
    const endIsLastDay = end.getDate() === new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate()
    if (!startIsFirstDay || !endIsLastDay || start.getFullYear() !== end.getFullYear()) return

    const monthSpan = end.getMonth() - start.getMonth() + 1
    if (monthSpan === 1) {
      formData.periodMode = 'standard'
      standardPeriod.unit = 'month'
      standardPeriod.year = start.getFullYear()
      standardPeriod.value = start.getMonth() + 1
      return
    }

    if (monthSpan === 3 && start.getMonth() % 3 === 0) {
      formData.periodMode = 'standard'
      standardPeriod.unit = 'quarter'
      standardPeriod.year = start.getFullYear()
      standardPeriod.value = Math.floor(start.getMonth() / 3) + 1
    }
  }

  // Détermine si le formateur est interne ou externe à l'initialisation
  const formateurType = ref<'interne' | 'externe'>(
    props.initialData?.formateurUserId ? 'interne' : 'externe',
  )

  const formData = reactive<FormationFormData>({
    designation: props.initialData?.designation || '',
    targetUserIds: props.initialData?.targetUserIds || [],
    formateur: props.initialData?.formateur || '',
    formateurUserId: props.initialData?.formateurUserId ?? null,
    organizerUserId: props.initialData?.organizerUserId ?? null,
    dateDebut: props.initialData?.dateDebut || '',
    dateFin: props.initialData?.dateFin || '',
    periodMode: props.initialData?.periodMode || 'custom',
    periodLabel: props.initialData?.periodLabel || '',
    frequency: props.initialData?.frequency || 'ponctuelle',
    observations: props.initialData?.observations || '',
  })

  const rules = {
    required: (v: any) => {
      if (Array.isArray(v)) return v.length > 0 || 'Ce champ est requis'
      return !!v || 'Ce champ est requis'
    },
    dateAfter: (v: string) => {
      if (!v || !formData.dateDebut) return true
      return new Date(v) >= new Date(formData.dateDebut) || 'La fin de période doit être après le début'
    },
  }

  function applyStandardPeriodToDates () {
    if (formData.periodMode !== 'standard') {
      return
    }

    if (standardPeriod.unit === 'quarter') {
      const startMonth = (standardPeriod.value - 1) * 3
      const startDate = new Date(standardPeriod.year, startMonth, 1)
      const endDate = new Date(standardPeriod.year, startMonth + 3, 0)
      formData.dateDebut = startDate.toISOString().split('T')[0] || ''
      formData.dateFin = endDate.toISOString().split('T')[0] || ''
      if (!formData.periodLabel) {
        formData.periodLabel = `T${standardPeriod.value} ${standardPeriod.year}`
      }
      return
    }

    const startDate = new Date(standardPeriod.year, standardPeriod.value - 1, 1)
    const endDate = new Date(standardPeriod.year, standardPeriod.value, 0)
    formData.dateDebut = startDate.toISOString().split('T')[0] || ''
    formData.dateFin = endDate.toISOString().split('T')[0] || ''
    if (!formData.periodLabel) {
      const month = months[standardPeriod.value - 1]
      formData.periodLabel = `${month} ${standardPeriod.year}`
    }
  }

  async function handleSubmit () {
    applyStandardPeriodToDates()

    const { valid } = await formRef.value.validate()
    if (valid) {
      emit('submit', formData)
    }
  }

  watch(() => props.initialData, () => {
    inferStandardPeriod()
  }, { immediate: true })

  watch(() => formData.periodMode, (mode) => {
    if (mode === 'custom') {
      formData.frequency = 'ponctuelle'
      return
    }

    if (formData.frequency === 'ponctuelle') {
      formData.frequency = 'mensuelle'
    }
  }, { immediate: true })
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
