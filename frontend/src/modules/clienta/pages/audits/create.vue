<script setup lang="ts">
  import type { CreateAuditDTO } from '@/api/services/audits.service'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useAudits } from '@/modules/clienta/composables/useAudits'
  import { useProcesses } from '@/modules/clienta/composables/useProcesses'
  import { useSites } from '@/modules/clienta/composables/useSites'
  import { useUsers } from '@/modules/clienta/composables/useUsers'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { useAuditProgramStore } from '@/stores/improvement/auditProgramStore'
  import { getErrorMessage } from '@/utils/errorMessage'

  type AuditTypeOption = CreateAuditDTO['type']
  type FrequencyOption = NonNullable<CreateAuditDTO['frequency']>

  interface AuditFormState {
    audit_program_id: number | null
    title: string
    type: AuditTypeOption
    site_id: number | null
    planned_date: string
    quarter: number | null
    frequency: FrequencyOption
    lead_auditor_id: number | null
    assigned_to: number | null
    audit_team_members: string[]
    auditee_ids: number[]
    process_ids: number[]
    scope: string
    objectives: string
    reference_documents: string
    risk_based_criteria: string
    axes: string[]
    m9_d2_address: string
    m9_d2_audit_dates: string
    m9_d2_criteria: string
    m9_d2_schedule: Array<{
      date_time: string
      object: string
      applicable_clauses: string
      auditee: string
      audit_team: string
    }>
  }

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const authStore = useAuthStore()

  const { createAudit } = useAudits()
  const { sites, fetchSites } = useSites()
  const { users, fetchUsers } = useUsers()
  const { processes, fetchProcesses } = useProcesses()
  const auditProgramStore = useAuditProgramStore()

  const loading = ref(false)
  const bootstrapLoading = ref(false)
  const error = ref('')
  const activePlanningTab = ref(0)
  const m9d2ObjectRefs = ref<any[]>([])

  const today = new Date()
  const defaultDate = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]

  const form = ref<AuditFormState>({
    audit_program_id: null,
    title: '',
    type: 'internal',
    site_id: authStore.currentSiteId ? Number(authStore.currentSiteId) : null,
    planned_date: defaultDate,
    quarter: null,
    frequency: 'annual',
    lead_auditor_id: null,
    assigned_to: null,
    audit_team_members: [],
    auditee_ids: [],
    process_ids: [],
    scope: '',
    objectives: '',
    reference_documents: '',
    risk_based_criteria: '',
    axes: ['Q'],
    m9_d2_address: '',
    m9_d2_audit_dates: '',
    m9_d2_criteria: '',
    m9_d2_schedule: [
      {
        date_time: '',
        object: '',
        applicable_clauses: '',
        auditee: '',
        audit_team: '',
      },
    ],
  })

  const typeOptions: Array<{ title: string, value: AuditTypeOption, icon: string, blurb: string }> = [
    {
      title: 'Audit interne',
      value: 'internal',
      icon: 'mdi-shield-check-outline',
      blurb: 'Pour piloter la conformité et l’amélioration continue en interne.',
    },
    {
      title: 'Audit externe',
      value: 'external',
      icon: 'mdi-domain',
      blurb: 'Pour les audits fournisseurs, clients ou prestataires externes.',
    },
    {
      title: 'Certification',
      value: 'certification',
      icon: 'mdi-certificate-outline',
      blurb: 'Pour préparer ou accompagner un audit de certification.',
    },
    {
      title: 'Audit processus',
      value: 'process',
      icon: 'mdi-source-branch',
      blurb: 'Pour concentrer l’analyse sur un ou plusieurs processus clés.',
    },
  ]

  const frequencyOptions: Array<{ title: string, value: FrequencyOption }> = [
    { title: 'Annuel', value: 'annual' },
    { title: 'Semestriel', value: 'biannual' },
    { title: 'Trimestriel', value: 'quarterly' },
    { title: 'Mensuel', value: 'monthly' },
    { title: 'Ponctuel', value: 'ad_hoc' },
  ]

  const axisOptions = [
    { title: 'Qualité', value: 'Q', color: 'primary' },
    { title: 'Hygiène / Santé', value: 'HS', color: 'success' },
    { title: 'Environnement', value: 'E', color: 'teal' },
  ]

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }, (_, index) => currentYear + 2 - index)
  })

  const selectedYear = computed(() => {
    if (!form.value.planned_date) {
      return new Date().getFullYear()
    }

    return new Date(form.value.planned_date).getFullYear()
  })

  const selectedSite = computed(() =>
    sites.value.find(site => Number(site.id) === Number(form.value.site_id)) || null,
  )

  const siteUsers = computed(() => users.value.filter(user => {
    if (!form.value.site_id) return true
    return Number(user.site_id) === Number(form.value.site_id)
  }))

  const auditors = computed(() => siteUsers.value.map(user => ({
    ...user,
    display_label: user.display_name || user.full_name || user.name || user.email,
  })))

  const auditees = auditors
  const auditeeNameOptions = computed(() => {
    const names = auditees.value
      .map(user => String(user.display_label || '').trim())
      .filter(Boolean)

    return Array.from(new Set(names)).toSorted((a, b) => a.localeCompare(b, 'fr'))
  })

  const auditorNameOptions = computed(() => {
    const names = auditors.value
      .map(user => String(user.display_label || '').trim())
      .filter(Boolean)

    return Array.from(new Set(names)).toSorted((a, b) => a.localeCompare(b, 'fr'))
  })
  const auditTeamRowOptions = computed(() => {
    const merged = [
      ...auditorNameOptions.value,
      ...form.value.audit_team_members.map(member => String(member || '').trim()).filter(Boolean),
    ]
    return Array.from(new Set(merged)).toSorted((a, b) => a.localeCompare(b, 'fr'))
  })

  const internalAuditorIds = computed(() => {
    if (form.value.audit_team_members.length === 0) return []

    const normalizedToId = new Map<string, number>()
    for (const user of auditors.value) {
      const key = String(user.display_label || '').trim().toLowerCase()
      if (key && !normalizedToId.has(key)) {
        normalizedToId.set(key, Number(user.id))
      }
    }

    return form.value.audit_team_members
      .map(member => normalizedToId.get(String(member || '').trim().toLowerCase()) || null)
      .filter((value): value is number => typeof value === 'number' && Number.isFinite(value))
  })
  const selectedAuditTypeLabel = computed(() =>
    typeOptions.find(option => option.value === form.value.type)?.title || 'Audit interne',
  )

  const processOptions = computed(() => {
    const seen = new Set<number>()

    return processes.value
      .filter((process: any) => {
        const processId = Number(process?.id || process?.attributes?.id || 0)
        if (!processId || seen.has(processId)) {
          return false
        }

        const processSiteId = Number(process?.site_id || process?.attributes?.site_id || 0)
        if (form.value.site_id && processSiteId && processSiteId !== Number(form.value.site_id)) {
          return false
        }

        seen.add(processId)
        return true
      })
      .map((process: any) => {
        const title = String(process?.title || process?.name || process?.attributes?.title || process?.attributes?.name || '').trim()
        const code = String(process?.code || process?.attributes?.code || '').trim()

        return {
          ...process,
          id: Number(process?.id || process?.attributes?.id || 0),
          display_label: code && title
            ? `${code} - ${title}`
            : (title || code || `Processus #${process?.id || process?.attributes?.id}`),
        }
      })
      .toSorted((a, b) => String(a.display_label).localeCompare(String(b.display_label), 'fr'))
  })

  const selectedProgramFromRoute = ref<any | null>(null)
  const lockedProgramId = computed(() => {
    const value = Number(route.query.program_id || 0)
    return value > 0 ? value : null
  })
  const isProgramLocked = computed(() => !!lockedProgramId.value)
  const isSiteLocked = computed(() => isProgramLocked.value && !!selectedProgramFromRoute.value?.site_id)

  function programOptionLabel (program: any) {
    const title = String(program?.title || '').trim()
    const ref = String(program?.ref || '').trim()
    if (ref && title) return `${ref} - ${title}`
    return title || ref || `Programme #${program?.id}`
  }

  const programOptions = computed(() => {
    const base = auditProgramStore.programs.filter(program => {
      const siteMatches = form.value.site_id ? Number(program.site_id) === Number(form.value.site_id) : true
      return siteMatches && Number(program.year) === Number(selectedYear.value)
    })

    if (selectedProgramFromRoute.value) {
      const exists = base.some(program => Number(program.id) === Number(selectedProgramFromRoute.value?.id))
      if (!exists) {
        base.unshift(selectedProgramFromRoute.value)
      }
    }

    return base
  })
  const hasProgramForSelectedContext = computed(() => programOptions.value.length > 0)
  const canSubmit = computed(() =>
    !loading.value
    && !!form.value.site_id
    && !!form.value.audit_program_id
    && hasProgramForSelectedContext.value,
  )

  const summaryItems = computed(() => [
    {
      label: 'Programme annuel',
      value: form.value.audit_program_id
        ? programOptionLabel(programOptions.value.find(program => Number(program.id) === Number(form.value.audit_program_id)))
        : 'Non rattaché',
    },
    {
      label: 'Site',
      value: selectedSite.value?.name || 'À définir',
    },
    {
      label: 'Responsable',
      value: auditors.value.find(user => user.id === form.value.lead_auditor_id)?.display_label || 'À choisir',
    },
    {
      label: 'Assigné à',
      value: auditors.value.find(user => user.id === form.value.assigned_to)?.display_label || 'À choisir',
    },
    {
      label: 'Équipe',
      value: `${form.value.audit_team_members.length} membre(s) / ${form.value.auditee_ids.length} audité(s)`,
    },
    {
      label: 'Lignes plan ',
      value: `${form.value.m9_d2_schedule.length}`,
    },
  ])

  function setM9D2ObjectRef (element: any, index: number) {
    m9d2ObjectRefs.value[index] = element
  }

  function addM9D2ScheduleRow () {
    form.value.m9_d2_schedule.unshift({
      date_time: '',
      object: '',
      applicable_clauses: '',
      auditee: '',
      audit_team: '',
    })
    void focusTopInsertedField(m9d2ObjectRefs.value, 0)
  }

  function removeM9D2ScheduleRow (index: number) {
    if (form.value.m9_d2_schedule.length <= 1) {
      return
    }
    form.value.m9_d2_schedule.splice(index, 1)
  }

  function syncQuarterWithDate () {
    if (!form.value.planned_date) {
      form.value.quarter = null
      return
    }

    const month = new Date(form.value.planned_date).getMonth()
    form.value.quarter = Math.floor(month / 3) + 1
  }

  function statusTone (count: number) {
    if (count >= 6) return 'success'
    if (count >= 3) return 'primary'
    return 'warning'
  }

  function goBack () {
    router.push('/company/performance/audits')
  }

  function goToPrograms () {
    router.push('/company/performance/audits/programme')
  }

  async function loadReferenceData () {
    bootstrapLoading.value = true

    try {
      await Promise.all([
        fetchSites({ per_page: 200 }),
        fetchUsers({ per_page: 300 }),
        fetchProcesses({ per_page: 300 }),
      ])

      if (form.value.site_id) {
        await auditProgramStore.fetchPrograms({
          site_id: Number(form.value.site_id),
          year: selectedYear.value,
          per_page: 100,
        })
      }
    } finally {
      bootstrapLoading.value = false
    }
  }

  async function applyRouteDefaults () {
    const querySiteId = Number(route.query.site_id || 0)
    if (querySiteId > 0) {
      form.value.site_id = querySiteId
    }

    const queryYear = Number(route.query.year || 0)
    if (queryYear >= 2000 && queryYear <= 2100) {
      form.value.planned_date = `${queryYear}-01-15`
      syncQuarterWithDate()
    }

    const queryProgramId = Number(route.query.program_id || 0)
    if (queryProgramId <= 0) {
      return
    }

    try {
      let selectedProgram = auditProgramStore.programs.find(program => Number(program.id) === queryProgramId)

      if (!selectedProgram) {
        selectedProgram = await auditProgramStore.fetchProgram(queryProgramId)
      }

      selectedProgramFromRoute.value = selectedProgram || null

      if (selectedProgram?.site_id) {
        form.value.site_id = Number(selectedProgram.site_id)
      }

      if (selectedProgram?.year) {
        form.value.planned_date = `${Number(selectedProgram.year)}-01-15`
        syncQuarterWithDate()
      }

      await refreshPrograms()

      form.value.audit_program_id = queryProgramId
    } catch (error_) {
      console.error('Impossible de présélectionner le programme depuis l’URL:', error_)
    }
  }

  async function refreshPrograms () {
    if (!form.value.site_id) {
      auditProgramStore.reset()
      form.value.audit_program_id = null
      return
    }

    await auditProgramStore.fetchPrograms({
      site_id: Number(form.value.site_id),
      year: selectedYear.value,
      per_page: 100,
    })

    if (lockedProgramId.value) {
      form.value.audit_program_id = lockedProgramId.value
      return
    }

    const selectedProgramStillVisible = programOptions.value.some(program => program.id === form.value.audit_program_id)
    if (!selectedProgramStillVisible) {
      form.value.audit_program_id = null
    }
  }

  async function handleSubmit () {
    error.value = ''

    if (!form.value.title.trim()) {
      error.value = 'Le titre de l’audit est requis.'
      return
    }
    if (!form.value.site_id) {
      error.value = 'Le site concerné est requis.'
      return
    }
    if (!form.value.planned_date) {
      error.value = 'La date prévue de l’audit est requise.'
      return
    }
    if (!form.value.audit_program_id) {
      error.value = 'Sélectionnez d\'abord un programme annuel d\'audit avant de planifier l\'audit.'
      return
    }
    if (!form.value.lead_auditor_id) {
      error.value = 'Le responsable d’audit est requis.'
      return
    }
    if (!form.value.scope.trim()) {
      error.value = 'Le périmètre de l’audit est requis.'
      return
    }
    if (!form.value.objectives.trim()) {
      error.value = 'Les objectifs de l’audit sont requis.'
      return
    }
    if (form.value.type === 'internal' && !form.value.assigned_to) {
      error.value = 'Le champ "Assigné à" est requis pour un audit interne.'
      return
    }
    if (form.value.type === 'internal' && !form.value.reference_documents.trim()) {
      error.value = 'Le champ "Documents de référence" est requis pour un audit interne.'
      return
    }

    loading.value = true

    try {
      const payload: CreateAuditDTO = {
        audit_program_id: form.value.audit_program_id,
        title: form.value.title.trim(),
        type: form.value.type,
        scope: form.value.scope.trim(),
        site_id: Number(form.value.site_id),
        planned_date: form.value.planned_date,
        quarter: form.value.quarter,
        frequency: form.value.frequency,
        lead_auditor_id: Number(form.value.lead_auditor_id),
        assigned_to: Number(form.value.assigned_to || form.value.lead_auditor_id || 0) || undefined,
        team_members: internalAuditorIds.value,
        auditor_ids: internalAuditorIds.value,
        auditee_ids: form.value.auditee_ids,
        process_ids: form.value.process_ids,
        objectives: form.value.objectives.trim(),
        reference_documents: form.value.reference_documents.trim() || undefined,
        risk_based_criteria: form.value.risk_based_criteria.trim() || undefined,
        axes: form.value.axes,
        m9_d2_traceability: {
          address: form.value.m9_d2_address.trim() || null,
          audit_dates: form.value.m9_d2_audit_dates.trim() || form.value.planned_date,
          audit_type: selectedAuditTypeLabel.value,
          purpose: form.value.objectives.trim() || null,
          scope: form.value.scope.trim() || null,
          criteria: form.value.m9_d2_criteria.trim() || null,
          audit_team: form.value.audit_team_members.join(', ') || null,
          auditees: auditees.value
            .filter(user => form.value.auditee_ids.includes(Number(user.id)))
            .map(user => user.display_label || user.name)
            .join(', ') || null,
          schedule: form.value.m9_d2_schedule
            .map(row => ({
              date_time: row.date_time?.trim() || null,
              object: row.object?.trim() || null,
              applicable_clauses: row.applicable_clauses?.trim() || null,
              auditee: row.auditee?.trim() || null,
              audit_team: row.audit_team?.trim() || null,
            }))
            .filter(row => row.date_time || row.object || row.applicable_clauses || row.auditee || row.audit_team),
          template: 'M9-D2',
          updated_at: new Date().toISOString(),
        },
      }

      const audit = await createAudit(payload)

      toast.success('Audit créé avec succès.')
      router.push(`/company/audits/${audit.id}`)
    } catch (error_) {
      error.value = getErrorMessage(error_, 'La création de l’audit a échoué.')
    } finally {
      loading.value = false
    }
  }

  watch(() => form.value.planned_date, () => {
    syncQuarterWithDate()
  }, { immediate: true })

  watch(() => [form.value.site_id, selectedYear.value], async ([siteId]) => {
    if (!siteId) return
    try {
      await refreshPrograms()
    } catch (error_) {
      console.error('Erreur lors du chargement des programmes d’audit:', error_)
    }
  }, { immediate: false })

  watch(() => form.value.site_id, siteId => {
    if (!siteId) {
      form.value.lead_auditor_id = null
      form.value.audit_team_members = []
      form.value.auditee_ids = []
      form.value.process_ids = []
      return
    }

    const siteUsersIds = new Set(siteUsers.value.map(user => user.id))
    if (form.value.lead_auditor_id && !siteUsersIds.has(form.value.lead_auditor_id)) {
      form.value.lead_auditor_id = null
    }
    if (form.value.assigned_to && !siteUsersIds.has(form.value.assigned_to)) {
      form.value.assigned_to = null
    }

    form.value.auditee_ids = form.value.auditee_ids.filter(id => siteUsersIds.has(id))
  })

  onMounted(async () => {
    await loadReferenceData()
    await applyRouteDefaults()
  })

  watch(() => form.value.lead_auditor_id, value => {
    if (!form.value.assigned_to && value) {
      form.value.assigned_to = value
    }
  })
</script>

<template>
  <ClientALayout current-page="audits">
    <v-container class="pa-2 pa-md-4 audit-create-page" fluid>
      <PageHeader
        icon="mdi-clipboard-plus-outline"
        subtitle="Construisez un audit clair, rattaché à votre programme annuel et prêt à être piloté."
        title="Créer un audit"
      >
        <template #actions>
          <v-btn prepend-icon="mdi-arrow-left" rounded="lg" variant="outlined" @click="goBack">
            Retour aux audits
          </v-btn>
          <v-btn
            color="primary"
            :disabled="!canSubmit"
            :loading="loading"
            prepend-icon="mdi-check-circle-outline"
            rounded="lg"
            @click="handleSubmit"
          >
            Enregistrer l'audit
          </v-btn>
        </template>
      </PageHeader>

      <v-row class="ga-0" dense>
        <v-col cols="12">
          <v-card class="hero-card mb-4" elevation="0" rounded="xl">
            <v-card-text class="pa-4 pa-md-4">
              <div class="hero-grid">
                <div>
                  <div class="hero-kicker mb-2">Planification moderne</div>
                  <h2 class="text-h4 font-weight-black mb-3">Un flux plus net pour programmer vos audits</h2>
                  <p class="text-body-1 text-medium-emphasis mb-0">
                    Rattachez l’audit à un programme annuel, mobilisez la bonne équipe et préparez une exécution propre
                    sur desktop comme sur mobile.
                  </p>
                </div>
                <div class="hero-stats">
                  <div class="hero-stat">
                    <span>Programmes disponibles</span>
                    <strong>{{ programOptions.length }}</strong>
                  </div>
                  <div class="hero-stat">
                    <span>Collaborateurs du site</span>
                    <strong>{{ auditors.length }}</strong>
                  </div>
                  <div class="hero-stat">
                    <span>Processus mobilisables</span>
                    <strong>{{ processOptions.length }}</strong>
                  </div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="8">
          <v-card class="form-card mb-4 planning-tabs-card" elevation="0" rounded="xl">
            <v-card-text class="pa-2 pa-md-3">
              <v-tabs
                v-model="activePlanningTab"
                class="planning-tabs"
                color="primary"
                density="comfortable"
                grow
                show-arrows
              >
                <v-tab :value="0">1. Cadrage</v-tab>
                <v-tab :value="1">2. Infos clés</v-tab>
                <v-tab :value="2">3. Équipe</v-tab>
                <v-tab :value="3">4. Périmètre</v-tab>
                <v-tab :value="4">5. Séquencement</v-tab>
              </v-tabs>
            </v-card-text>
          </v-card>

          <v-card v-show="activePlanningTab === 0" class="form-card mb-4 compact-form" elevation="0" rounded="xl">
            <v-card-text class="pa-4 pa-md-4">
              <div class="section-header">
                <div>
                  <div class="section-kicker">1. Cadre d’audit</div>
                  <h3 class="text-h6 font-weight-bold mb-1">Positionnement dans le programme annuel</h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Le programme annuel regroupe plusieurs plans d’audit. Vous pouvez rattacher cet audit à celui de l’année en cours.
                  </p>
                </div>
                <v-chip color="primary" size="small" variant="tonal">
                  Année {{ selectedYear }}
                </v-chip>
              </div>

              <v-alert
                v-if="error"
                class="mb-5"
                closable
                type="error"
                variant="tonal"
                @click:close="error = ''"
              >
                {{ error }}
              </v-alert>

              <v-row>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.site_id"
                    :disabled="isSiteLocked"
                    item-title="name"
                    item-value="id"
                    :items="sites"
                    label="Site concerné *"
                    :loading="bootstrapLoading"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="8">
                  <v-select
                    v-model="form.audit_program_id"
                    :disabled="isProgramLocked"
                    :item-title="programOptionLabel"
                    item-value="id"
                    :items="programOptions"
                    label="Programme annuel associé *"
                    :loading="auditProgramStore.loading"
                    no-data-text="Aucun programme annuel pour ce site et cette année"
                    rounded="lg"
                    variant="outlined"
                  >
                    <template #append-inner>
                      <v-tooltip location="top" text="Obligatoire : créez d'abord le programme annuel puis rattachez ce plan d'audit.">
                        <template #activator="{ props }">
                          <v-icon v-bind="props" color="medium-emphasis" size="18">mdi-help-circle-outline</v-icon>
                        </template>
                      </v-tooltip>
                    </template>
                  </v-select>
                </v-col>
                <v-col v-if="isProgramLocked && form.audit_program_id" cols="12">
                  <v-alert border="start" density="comfortable" type="info" variant="tonal">
                    Le programme d'audit est présélectionné automatiquement depuis le programme annuel et verrouillé en lecture seule.
                  </v-alert>
                </v-col>
                <v-col v-if="form.site_id && !hasProgramForSelectedContext" cols="12">
                  <v-alert border="start" type="warning" variant="tonal">
                    Aucun programme annuel trouvé pour ce site et l'année {{ selectedYear }}.
                    Créez d'abord le programme d'audit avant de planifier un audit.
                    <template #append>
                      <v-btn
                        color="warning"
                        prepend-icon="mdi-calendar-plus"
                        size="small"
                        variant="outlined"
                        @click="goToPrograms"
                      >
                        Créer un programme
                      </v-btn>
                    </template>
                  </v-alert>
                </v-col>
              </v-row>

              <div class="type-grid mt-1">
                <button
                  v-for="option in typeOptions"
                  :key="option.value"
                  class="type-card"
                  :class="{ 'type-card--active': form.type === option.value }"
                  type="button"
                  @click="form.type = option.value"
                >
                  <v-icon :color="form.type === option.value ? 'primary' : 'medium-emphasis'" size="22">
                    {{ option.icon }}
                  </v-icon>
                  <div>
                    <div class="type-card-title">{{ option.title }}</div>
                    <div class="type-card-text">{{ option.blurb }}</div>
                  </div>
                </button>
              </div>
            </v-card-text>
          </v-card>

          <v-card v-show="activePlanningTab === 1" class="form-card mb-4 compact-form" elevation="0" rounded="xl">
            <v-card-text class="pa-4 pa-md-4">
              <div class="section-header mb-4">
                <div>
                  <div class="section-kicker">2. Informations clés</div>
                  <h3 class="text-h6 font-weight-bold mb-1">Identité et calendrier de l’audit</h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Un affichage clair, des champs alignés et une lecture immédiate de ce qui sera lancé.
                  </p>
                </div>
              </div>

              <v-sheet class="traceability-shell mt-2 mb-4 pa-3 pa-md-4" rounded="lg">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="text-subtitle-2 font-weight-bold">Informations clés</div>
                  <v-chip color="primary" size="x-small" variant="tonal">Traçabilité</v-chip>
                </div>
                <v-row>
                  <v-col cols="12" md="5">
                    <v-text-field
                      v-model="form.m9_d2_address"
                      label="Adresse de l’organisme"
                      placeholder="Adresse complète du site audité"
                      rounded="lg"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <AppDatePickerField
                      v-model="form.m9_d2_audit_dates"
                      label="Date(s) d’audit"
                      mode="date"
                      placeholder="Sélectionner la date d'audit"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="form.m9_d2_criteria"
                      label="Critères d’audit"
                      placeholder="ISO 9001..."
                      rounded="lg"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
              </v-sheet>

              <v-spacer class="my-5" />

              <v-row>
                <v-col cols="12" md="8">
                  <v-text-field
                    v-model="form.title"
                    label="Titre de l’audit *"
                    placeholder="Ex. Audit interne ISO 9001 du site principal"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <AppDatePickerField
                    v-model="form.planned_date"
                    label="Date prévue *"
                    mode="date"
                    required
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.quarter"
                    :items="[1, 2, 3, 4]"
                    label="Trimestre"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.frequency"
                    item-title="title"
                    item-value="value"
                    :items="frequencyOptions"
                    label="Fréquence"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
              </v-row>

            </v-card-text>
          </v-card>

          <v-card v-show="activePlanningTab === 2" class="form-card mb-4 compact-form" elevation="0" rounded="xl">
            <v-card-text class="pa-4 pa-md-4">
              <div class="section-header mb-4">
                <div>
                  <div class="section-kicker">3. Pilotage et équipe</div>
                  <h3 class="text-h6 font-weight-bold mb-1">Les bonnes personnes, sur le bon site</h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Les audités proviennent des collaborateurs du site sélectionné, et l’équipe d’audit peut inclure des externes.
                  </p>
                </div>
              </div>

              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.lead_auditor_id"
                    item-title="display_label"
                    item-value="id"
                    :items="auditors"
                    label="Responsable d’audit *"
                    no-data-text="Aucun collaborateur disponible sur ce site"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.assigned_to"
                    item-title="display_label"
                    item-value="id"
                    :items="auditors"
                    label="Assigné à (suivi audit) *"
                    no-data-text="Aucun collaborateur disponible sur ce site"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.axes"
                    chips
                    item-title="title"
                    item-value="value"
                    :items="axisOptions"
                    label="Axes QHSE"
                    multiple
                    rounded="lg"
                    variant="outlined"
                  >
                    <template #selection="{ item }">
                      <v-chip :color="item.raw.color" size="small" variant="tonal">
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-select>
                </v-col>
                <v-col cols="12">
                  <v-combobox
                    v-model="form.audit_team_members"
                    chips
                    closable-chips
                    hint="Vous pouvez saisir des personnes externes ou choisir parmi les collaborateurs du site."
                    :items="auditorNameOptions"
                    label="Équipe d’audit (interne/externe)"
                    multiple
                    no-data-text="Tapez un nom externe ou sélectionnez un collaborateur du site"
                    persistent-hint
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-select
                    v-model="form.auditee_ids"
                    chips
                    closable-chips
                    item-title="display_label"
                    item-value="id"
                    :items="auditees"
                    label="Audités concernés"
                    multiple
                    no-data-text="Aucun collaborateur disponible sur ce site"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <v-card v-show="activePlanningTab === 3" class="form-card compact-form" elevation="0" rounded="xl">
            <v-card-text class="pa-4 pa-md-4">
              <div class="section-header mb-4">
                <div>
                  <div class="section-kicker">4. Périmètre métier</div>
                  <h3 class="text-h6 font-weight-bold mb-1">Objectifs, processus et logique de risque</h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Cette section reprend les éléments attendus pour un plan d’audit exploitable dès sa création.
                  </p>
                </div>
              </div>

              <v-row>
                <v-col cols="12">
                  <v-select
                    v-model="form.process_ids"
                    chips
                    closable-chips
                    item-title="display_label"
                    item-value="id"
                    :items="processOptions"
                    label="Processus concernés"
                    multiple
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.scope"
                    label="Périmètre de l’audit *"
                    placeholder="Décrivez les activités, services, sites, ateliers ou processus couverts."
                    rounded="lg"
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.objectives"
                    label="Objectifs de l’audit *"
                    placeholder="Ex. Vérifier la conformité, évaluer l’efficacité du système, identifier les pistes d’amélioration."
                    rounded="lg"
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.risk_based_criteria"
                    label="Critères basés sur les risques"
                    placeholder="Ex. risques processus, non-conformités récurrentes, incidents, exigences clients."
                    rounded="lg"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.reference_documents"
                    label="Documents de référence *"
                    placeholder="Ex. ISO 9001:2015, procédures internes, politiques, instructions, exigences clients"
                    rounded="lg"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <v-card v-show="activePlanningTab === 4" class="form-card compact-form mt-4" elevation="0" rounded="xl">
            <v-card-text class="pa-4 pa-md-4">
              <div class="section-header mb-4">
                <div>
                  <div class="section-kicker">5. Séquencement opérationnel</div>
                  <h3 class="text-h6 font-weight-bold mb-1">Planning détaillé M9-D2</h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Organisez les créneaux, objets d’audit et clauses applicables pour chaque ligne du plan.
                  </p>
                </div>
              </div>

              <div class="d-flex align-center justify-space-between mt-2 mb-3">
                <div class="text-subtitle-2 font-weight-bold">Planning détaillé (M9-D2)</div>
                <v-btn
                  color="primary"
                  prepend-icon="mdi-plus"
                  rounded="lg"
                  size="small"
                  variant="tonal"
                  @click="addM9D2ScheduleRow"
                >
                  Ajouter une ligne
                </v-btn>
              </div>

              <div class="m9d2-grid">
                <div v-for="(row, index) in form.m9_d2_schedule" :key="`m9d2-row-${index}`" class="m9d2-row">
                  <v-row dense>
                    <v-col cols="12" md="3">
                      <AppDatePickerField
                        v-model="row.date_time"
                        label="Date/heure"
                        mode="datetime"
                        placeholder="Choisir date et heure"
                      />
                    </v-col>
                    <v-col cols="12" md="3">
                      <v-text-field
                        :ref="el => setM9D2ObjectRef(el, index)"
                        v-model="row.object"
                        label="Objet (activité/processus)"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-text-field
                        v-model="row.applicable_clauses"
                        label="Clauses applicables"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-select
                        v-model="row.auditee"
                        :items="auditeeNameOptions"
                        label="Audité"
                        no-data-text="Aucun collaborateur du site"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="2">
                      <v-combobox
                        v-model="row.audit_team"
                        :items="auditTeamRowOptions"
                        label="Équipe d’audit"
                        no-data-text="Saisir un nom externe ou choisir un membre"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <div class="d-flex justify-end">
                        <v-btn
                          color="error"
                          :disabled="form.m9_d2_schedule.length <= 1"
                          icon="mdi-delete-outline"
                          size="small"
                          variant="text"
                          @click="removeM9D2ScheduleRow(index)"
                        />
                      </div>
                    </v-col>
                  </v-row>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="4">
          <v-card class="summary-card mb-4" elevation="0" rounded="xl">
            <v-card-text class="pa-4">
              <div class="section-kicker mb-2">Synthèse</div>
              <h3 class="text-h6 font-weight-bold mb-4">Vue de pilotage</h3>

              <div class="summary-list">
                <div v-for="item in summaryItems" :key="item.label" class="summary-item">
                  <span>{{ item.label }}</span>
                  <strong>{{ item.value }}</strong>
                </div>
              </div>

              <div class="summary-kpis mt-5">
                <div class="summary-kpi">
                  <span>Processus ciblés</span>
                  <v-chip :color="statusTone(form.process_ids.length)" size="small" variant="tonal">
                    {{ form.process_ids.length }}
                  </v-chip>
                </div>
                <div class="summary-kpi">
                  <span>Axes QHSE</span>
                  <v-chip color="primary" size="small" variant="tonal">
                    {{ form.axes.length }}
                  </v-chip>
                </div>
                <div class="summary-kpi">
                  <span>Trimestre</span>
                  <v-chip color="secondary" size="small" variant="tonal">
                    {{ form.quarter || '-' }}
                  </v-chip>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- <v-card class="summary-card" elevation="0" rounded="xl">
            <v-card-text class="pa-4">
              <div class="section-kicker mb-2">Conseils de saisie</div>
              <h3 class="text-h6 font-weight-bold mb-4">Pour une création propre</h3>

              <div class="tips-list">
                <div class="tip-item">
                  <v-icon color="primary" size="18">mdi-check-circle-outline</v-icon>
                  <span>Commencez toujours par créer le programme annuel, puis rattachez chaque plan d’audit.</span>
                </div>
                <div class="tip-item">
                  <v-icon color="primary" size="18">mdi-check-circle-outline</v-icon>
                  <span>Définissez un périmètre concret pour faciliter la checklist et le rapport.</span>
                </div>
                <div class="tip-item">
                  <v-icon color="primary" size="18">mdi-check-circle-outline</v-icon>
                  <span>Gardez les auditeurs et audités sur le même site pour éviter les listes vides.</span>
                </div>
              </div>

              <v-divider class="my-4" />

              <div class="d-flex flex-column ga-3">
                <v-btn
                  block
                  color="primary"
                  :disabled="!canSubmit"
                  :loading="loading"
                  prepend-icon="mdi-content-save-outline"
                  rounded="lg"
                  size="large"
                  @click="handleSubmit"
                >
                  Créer l'audit
                </v-btn>
                <v-btn block prepend-icon="mdi-arrow-left" rounded="lg" variant="outlined" @click="goBack">
                  Revenir à la liste
                </v-btn>
              </div>
            </v-card-text>
          </v-card> -->
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<style scoped>
  .audit-create-page {
    --hero-bg: linear-gradient(135deg, rgba(11, 94, 215, 0.09), rgba(15, 118, 110, 0.08));
    --card-border: rgba(15, 23, 42, 0.08);
  }

  .hero-card,
  .form-card,
  .summary-card {
    border: 1px solid var(--card-border);
    background: #fff;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05);
  }

  .planning-tabs-card {
    position: sticky;
    top: 12px;
    z-index: 3;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
  }

  .planning-tabs :deep(.v-tab) {
    font-weight: 700;
    text-transform: none;
    min-height: 42px;
  }

  .hero-card {
    background: var(--hero-bg);
    overflow: hidden;
  }

  .hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr);
    gap: 14px;
    align-items: start;
  }

  .hero-kicker,
  .section-kicker {
    color: rgb(8, 94, 172);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .hero-stats {
    display: grid;
    gap: 8px;
  }

  .hero-stat {
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
  }

  .hero-stat span,
  .summary-item span,
  .summary-kpi span,
  .tip-item span {
    color: rgba(15, 23, 42, 0.72);
  }

  .hero-stat strong,
  .summary-item strong {
    font-size: 1.05rem;
    font-weight: 800;
    color: rgb(15, 23, 42);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: start;
  }

  .type-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .type-card {
    width: 100%;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px;
    background: #fff;
    padding: 12px;
    display: flex;
    gap: 10px;
    align-items: start;
    text-align: left;
    transition: all 0.2s ease;
  }

  .type-card:hover,
  .type-card--active {
    border-color: rgba(11, 94, 215, 0.28);
    box-shadow: 0 14px 30px rgba(11, 94, 215, 0.08);
    transform: translateY(-1px);
  }

  .type-card-title {
    font-weight: 700;
    color: rgb(15, 23, 42);
    margin-bottom: 4px;
  }

  .type-card-text {
    color: rgba(15, 23, 42, 0.7);
    font-size: 0.92rem;
    line-height: 1.45;
  }

  .traceability-shell {
    border: 1px solid rgba(11, 94, 215, 0.14);
    background:
      linear-gradient(135deg, rgba(11, 94, 215, 0.05), rgba(15, 118, 110, 0.04)),
      #fff;
  }

  .m9d2-grid {
    display: grid;
    gap: 10px;
  }

  .m9d2-row {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px;
    background: rgba(248, 250, 252, 0.8);
    padding: 10px;
  }

  .summary-list,
  .tips-list {
    display: grid;
    gap: 8px;
  }

  .summary-item,
  .summary-kpi,
  .tip-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .summary-item {
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
  }

  .summary-kpis {
    display: grid;
    gap: 8px;
  }

  .tip-item {
    justify-content: flex-start;
  }

  .compact-form :deep(.v-input) {
    margin-bottom: 2px;
  }

  .compact-form :deep(.v-input__details) {
    min-height: 12px;
    padding-top: 2px;
  }

  .compact-form :deep(.v-field.v-field--variant-outlined) {
    border-radius: 12px;
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
  }

  .compact-form :deep(.v-field.v-field--variant-outlined .v-field__outline) {
    --v-field-border-opacity: 1;
    color: rgba(148, 163, 184, 0.55);
  }

  .compact-form :deep(.v-input--focused .v-field.v-field--variant-outlined .v-field__outline) {
    color: rgba(37, 99, 235, 0.75);
  }

  .compact-form :deep(.v-field__input) {
    min-height: 44px;
  }

  .compact-form :deep(.v-field__append-inner > .v-icon),
  .compact-form :deep(.v-field__prepend-inner > .v-icon) {
    color: #64748b;
  }

  @media (max-width: 1279px) {
    .hero-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 959px) {
    .type-grid {
      grid-template-columns: 1fr;
    }

    .section-header {
      flex-direction: column;
    }
  }
</style>
