<template>
  <v-form ref="formRef" @submit.prevent="handleSubmit">
    <v-row>
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.type"
          density="comfortable"
          :items="typeOptions"
          label="Type de plan *"
          prepend-inner-icon="mdi-tag"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="6">
        <v-select
          v-model="formData.frequency"
          density="comfortable"
          :items="filteredFrequencyOptions"
          label="Récurrence *"
          prepend-inner-icon="mdi-repeat"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-text-field
          v-model="formData.designation"
          density="comfortable"
          label="Thème / sujet *"
          prepend-inner-icon="mdi-text"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="6">
        <v-autocomplete
          v-model="formData.cibles"
          chips
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="full_name"
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

      <v-col cols="12" md="6">
        <v-select
          v-model="formData.moyens"
          chips
          density="comfortable"
          :items="moyenOptions"
          label="Moyens de diffusion *"
          multiple
          prepend-inner-icon="mdi-bullhorn"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12" md="6">
        <v-select
          v-model="periodMode"
          density="comfortable"
          :items="periodModeOptions"
          label="Mode de période (standard ou personnalisée) *"
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
          <v-chip v-if="formData.participantUserIds?.length" color="success" size="small" variant="tonal">
            {{ formData.participantUserIds.length }} sélectionné(s)
          </v-chip>
          <v-spacer />
          <v-btn
            size="small"
            variant="text"
            @click="formData.participantUserIds = selectVisibleCollaborators(formData.participantUserIds || [])"
          >
            Sélectionner la liste filtrée
          </v-btn>
          <v-btn
            size="small"
            variant="text"
            @click="formData.participantUserIds = []"
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

      <v-col v-if="periodMode === 'standard'" cols="12" md="3">
        <v-select
          v-model="standardPeriod.unit"
          density="comfortable"
          :items="standardPeriodUnits"
          label="Type de période *"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="periodMode === 'standard'" cols="12" md="3">
        <v-select
          v-model="standardPeriod.year"
          density="comfortable"
          :items="yearOptions"
          label="Année *"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="periodMode === 'standard'" cols="12" md="6">
        <v-select
          v-model="standardPeriod.value"
          density="comfortable"
          :items="standardValueOptions"
          label="Période standard *"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="periodMode === 'custom'" cols="12" md="6">
        <v-text-field
          v-model="formData.dateDebut"
          density="comfortable"
          label="Période du *"
          prepend-inner-icon="mdi-calendar-start"
          :rules="[rules.requiredIfCustom]"
          type="date"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="periodMode === 'custom'" cols="12" md="6">
        <v-text-field
          v-model="formData.dateFin"
          density="comfortable"
          label="Période au *"
          prepend-inner-icon="mdi-calendar-end"
          :rules="[rules.requiredIfCustom, rules.dateAfter]"
          type="date"
          variant="outlined"
        />
      </v-col>

      <!-- Organisateur interne -->
      <v-col cols="12" md="6">
        <v-autocomplete
          v-model="formData.organizerUserId"
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="id"
          :items="collaborators"
          label="Organisateur (responsable) *"
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

      <!-- Type de chargé de com -->
      <v-col cols="12" md="6">
        <v-select
          v-model="responsableType"
          density="comfortable"
          :items="[{ title: 'Interne', value: 'interne' }, { title: 'Externe', value: 'externe' }]"
          label="Type de chargé de com/sensibilisation *"
          prepend-inner-icon="mdi-account-tie"
          variant="outlined"
        />
      </v-col>

      <v-col v-if="responsableType === 'interne'" cols="12" md="6">
        <v-autocomplete
          v-model="formData.responsibleUserId"
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="id"
          :items="collaborators"
          label="Chargé de com/sensibilisation (interne) *"
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

      <v-col v-if="responsableType === 'externe'" cols="12" md="6">
        <v-text-field
          v-model="formData.responsable"
          density="comfortable"
          label="Chargé de com/sensibilisation (externe) *"
          prepend-inner-icon="mdi-account-tie"
          :rules="[rules.required]"
          variant="outlined"
        />
      </v-col>

      <!-- Participants internes -->
      <v-col cols="12" md="6">
        <v-autocomplete
          v-model="formData.participantUserIds"
          chips
          clearable
          density="comfortable"
          item-title="full_name"
          item-value="id"
          :items="collaborators"
          label="Participants internes"
          :loading="loadingCollaborators"
          multiple
          prepend-inner-icon="mdi-account-multiple"
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

      <v-col cols="12" md="6">
        <v-text-field
          v-model.number="formData.cout"
          density="comfortable"
          label="Budget estimé (FCFA)"
          prepend-inner-icon="mdi-wallet"
          type="number"
          variant="outlined"
        />
      </v-col>

      <v-col cols="12">
        <v-textarea
          v-model="formData.observations"
          density="comfortable"
          label="Objectifs / résultats attendus"
          prepend-inner-icon="mdi-comment-text"
          rows="3"
          variant="outlined"
        />
      </v-col>
    </v-row>

    <v-divider class="my-4" />

    <div class="d-flex justify-end gap-2">
      <v-btn
        variant="text"
        @click="$emit('cancel')"
      >
        Annuler
      </v-btn>
      <v-btn
        color="primary"
        :loading="loading"
        type="submit"
        variant="flat"
      >
        {{ isEdit ? 'Mettre à jour' : 'Créer' }}
      </v-btn>
    </div>
  </v-form>
</template>

<script setup lang="ts">
  import type { CommunicationFormData, CommunicationFrequency, CommunicationMoyen, CommunicationType } from '../../types/communication.types'
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useCollaborators } from '../../composables/useCollaborators'

  interface Props {
    initialData?: Partial<CommunicationFormData>
    isEdit?: boolean
    loading?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    loading: false,
  })

  const emit = defineEmits<{
    submit: [data: CommunicationFormData]
    cancel: []
  }>()

  const formRef = ref()

  const months = [
    'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
    'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc',
  ]

  const typeOptions: { title: string, value: CommunicationType }[] = [
    { title: 'Communication', value: 'communication' },
    { title: 'Sensibilisation', value: 'sensibilisation' },
  ]

  const {
    collaborators,
    collaboratorFilters,
    departmentOptions,
    loadingCollaborators,
    selectVisibleCollaborators,
    siteOptions,
  } = useCollaborators()

  const moyenOptions: CommunicationMoyen[] = [
    'Réunion',
    'Email',
    'Affichage',
    'Intranet/Site web',
    'Newsletter',
    'Formation',
    'Atelier',
    'Vidéo',
    'Autre',
  ]

  const frequencyOptions: { title: string, value: CommunicationFrequency }[] = [
    { title: 'Ponctuelle', value: 'ponctuelle' },
    { title: 'Annuelle', value: 'annuelle' },
    { title: 'Semestrielle', value: 'semestrielle' },
    { title: 'Trimestrielle', value: 'trimestrielle' },
    { title: 'Mensuelle', value: 'mensuelle' },
    { title: 'Biennale', value: 'biennale' },
    { title: 'Sur demande', value: 'sur_demande' },
  ]
  const periodMode = ref<'standard' | 'custom'>('custom')
  const recurringFrequencies: CommunicationFrequency[] = [
    'annuelle',
    'semestrielle',
    'trimestrielle',
    'mensuelle',
    'biennale',
    'sur_demande',
  ]
  const filteredFrequencyOptions = computed(() => {
    if (periodMode.value === 'custom') {
      return frequencyOptions.filter(option => option.value === 'ponctuelle')
    }
    return frequencyOptions.filter(option => recurringFrequencies.includes(option.value))
  })

  const periodModeOptions = [
    { title: 'Période standard (mois / trimestre)', value: 'standard' },
    { title: 'Période personnalisée', value: 'custom' },
  ]

  const standardPeriodUnits = [
    { title: 'Mois', value: 'month' },
    { title: 'Trimestre', value: 'quarter' },
  ]

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }).map((_, index) => currentYear - 1 + index)
  })

  const standardPeriod = reactive({
    unit: 'month',
    year: new Date().getFullYear(),
    value: new Date().getMonth() + 1,
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
    return months.map((label, index) => ({ title: label, value: index + 1 }))
  })

  const responsableType = ref<'interne' | 'externe'>(
    props.initialData?.responsibleUserId ? 'interne' : 'externe',
  )

  const formData = reactive<CommunicationFormData>({
    type: props.initialData?.type || 'communication',
    designation: props.initialData?.designation || '',
    cibles: props.initialData?.cibles || [],
    moyens: props.initialData?.moyens || [],
    chronogramme: props.initialData?.chronogramme || (Array.from({ length: 12 }).fill(false) as boolean[]),
    responsable: props.initialData?.responsable || '',
    organizerUserId: props.initialData?.organizerUserId ?? null,
    responsibleUserId: props.initialData?.responsibleUserId ?? null,
    participantUserIds: props.initialData?.participantUserIds || [],
    cout: props.initialData?.cout,
    dateDebut: props.initialData?.dateDebut || '',
    dateFin: props.initialData?.dateFin || '',
    periodMode: props.initialData?.periodMode || 'custom',
    frequency: props.initialData?.frequency || 'ponctuelle',
    observations: props.initialData?.observations || '',
  })

  const rules = {
    required: (v: any) => {
      if (Array.isArray(v)) return v.length > 0 || 'Ce champ est requis'
      return !!v || 'Ce champ est requis'
    },
    requiredIfCustom: (v: any) => {
      if (periodMode.value === 'custom') {
        return !!v || 'Ce champ est requis'
      }
      return true
    },
    dateAfter: (v: string) => {
      if (!v || !formData.dateDebut) return true
      return new Date(v) >= new Date(formData.dateDebut) || 'La date de fin doit être après la date de début'
    },
  }

  function applyStandardPeriodToDates () {
    if (periodMode.value !== 'standard') return

    if (standardPeriod.unit === 'quarter') {
      const startMonth = (standardPeriod.value - 1) * 3
      const startDate = new Date(standardPeriod.year, startMonth, 1)
      const endDate = new Date(standardPeriod.year, startMonth + 3, 0)
      formData.dateDebut = formatDate(startDate)
      formData.dateFin = formatDate(endDate)
      formData.chronogramme = Array.from({ length: 12 }).fill(false) as boolean[]
      for (let i = startMonth; i < startMonth + 3; i++) {
        formData.chronogramme[i] = true
      }
      return
    }

    const startDate = new Date(standardPeriod.year, standardPeriod.value - 1, 1)
    const endDate = new Date(standardPeriod.year, standardPeriod.value, 0)
    formData.dateDebut = formatDate(startDate)
    formData.dateFin = formatDate(endDate)
    formData.chronogramme = Array.from({ length: 12 }).fill(false) as boolean[]
    formData.chronogramme[standardPeriod.value - 1] = true
  }

  function applyCustomPeriodToChronogramme () {
    if (periodMode.value !== 'custom') return
    if (!formData.dateDebut || !formData.dateFin) return
    const start = new Date(formData.dateDebut)
    const end = new Date(formData.dateFin)
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return

    const monthsSelected = Array.from({ length: 12 }).fill(false) as boolean[]
    const cursor = new Date(start.getFullYear(), start.getMonth(), 1)
    const endCursor = new Date(end.getFullYear(), end.getMonth(), 1)
    while (cursor <= endCursor) {
      if (cursor.getFullYear() === start.getFullYear()) {
        monthsSelected[cursor.getMonth()] = true
      }
      cursor.setMonth(cursor.getMonth() + 1)
    }
    formData.chronogramme = monthsSelected
  }

  function formatDate (date: Date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  watch(() => [standardPeriod.unit, standardPeriod.value, standardPeriod.year], () => {
    applyStandardPeriodToDates()
  })

  watch(periodMode, mode => {
    formData.periodMode = mode
    if (mode === 'custom' && formData.frequency !== 'ponctuelle') {
      formData.frequency = 'ponctuelle'
    }
    if (mode === 'standard' && formData.frequency === 'ponctuelle') {
      formData.frequency = 'mensuelle'
    }
    if (mode === 'standard') {
      applyStandardPeriodToDates()
    }
  })

  watch(() => [formData.dateDebut, formData.dateFin], () => {
    applyCustomPeriodToChronogramme()
  })

  function inferStandardPeriod () {
    if (!props.initialData?.dateDebut || !props.initialData?.dateFin) return
    const start = new Date(props.initialData.dateDebut)
    const end = new Date(props.initialData.dateFin)
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return

    const startIsFirstDay = start.getDate() === 1
    const endIsLastDay = end.getDate() === new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate()
    if (!startIsFirstDay || !endIsLastDay) return

    if (start.getFullYear() !== end.getFullYear()) return

    const startMonth = start.getMonth()
    const endMonth = end.getMonth()
    const monthSpan = endMonth - startMonth + 1

    if (monthSpan === 1) {
      periodMode.value = 'standard'
      standardPeriod.unit = 'month'
      standardPeriod.year = start.getFullYear()
      standardPeriod.value = startMonth + 1
      return
    }

    if (monthSpan === 3 && startMonth % 3 === 0) {
      periodMode.value = 'standard'
      standardPeriod.unit = 'quarter'
      standardPeriod.year = start.getFullYear()
      standardPeriod.value = Math.floor(startMonth / 3) + 1
    }
  }

  async function handleSubmit () {
    if (periodMode.value === 'standard') {
      applyStandardPeriodToDates()
    } else {
      applyCustomPeriodToChronogramme()
    }
    const { valid } = await formRef.value.validate()
    if (valid) {
      emit('submit', formData)
    }
  }

  onMounted(() => {
    periodMode.value = formData.periodMode
    inferStandardPeriod()
  })
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
