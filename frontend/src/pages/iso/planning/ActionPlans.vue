<template>
  <ClientALayout>
    <div class="sm-shell px-1 pb-6">
      <ActionPlansHero
        :completion-rate="completionRate"
        :display-mode="displayMode"
        :display-mode-options="displayModeOptions"
        :plan-format="planFormat"
        :plan-format-options="planFormatOptions"
        :plan-status="planStatus"
        :plan-status-color="planStatusColor"
        :plan-status-title="planStatusTitle"
        :plan-statuses="planStatuses"
        :selected-year="selectedYear"
        @update:display-mode="displayMode = $event as 'court' | 'liste'"
        @update:plan-format="planFormat = $event as 'jour' | 'semaine' | 'mois'"
        @update:plan-status="
          planStatus = $event as
            | 'draft'
            | 'validated'
            | 'in_progress'
            | 'completed'
        "
        @update:selected-year="selectedYear = $event"
      />

      <ActionPlansMetrics
        :activities-count="activities.length"
        :completion-rate="completionRate"
        :covered-months-count="coveredMonthsCount"
        distribution-label="grandes tâches"
        :done-sub-activities="doneSubActivities"
        :in-progress-sub-activities="inProgressSubActivities"
        :not-started-sub-activities="notStartedSubActivities"
        :sub-activities-count="subActivitiesCount"
      />

      <ActionPlansInfoBanner />

      <ActionPlansNote :note="note" @update:note="note = $event" />

      <ActionPlansTable
        :activities="activities"
        :activity-status-options="activityStatusOptions"
        :can-save="canSave"
        :collapsed-activities="collapsedActivities"
        :exporting="exporting"
        :has-site="Boolean(authStore.currentSiteId)"
        :loading="loading"
        :month-columns="monthColumns"
        :row-progress-options="rowProgressOptions"
        :saving="saving"
        :sub-activities-count="subActivitiesCount"
        @add-activity="addActivity"
        @add-sub-activity="addSubActivity"
        @collapse-all="collapseAll"
        @expand-all="expandAll"
        @export="exportPlanXlsx"
        @remove-activity="removeActivity"
        @remove-sub-activity="removeSubActivity"
        @save="savePlan"
        @toggle-activity="toggleActivityCollapse"
        @toggle-month="toggleMonth"
        @update-activity-progress="updateActivityProgress"
        @update-activity-schedule="updateActivitySchedule"
        @update-activity-status="updateActivityStatus"
      />
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import ActionPlansHero from '@/pages/iso/planning/components/ActionPlansHero.vue'
  import ActionPlansInfoBanner from '@/pages/iso/planning/components/ActionPlansInfoBanner.vue'
  import ActionPlansMetrics from '@/pages/iso/planning/components/ActionPlansMetrics.vue'
  import ActionPlansNote from '@/pages/iso/planning/components/ActionPlansNote.vue'
  import { useAuthStore } from '@/stores/auth'

  type MonthKey
    = | 'jan'
      | 'fev'
      | 'mar'
      | 'avr'
      | 'mai'
      | 'juin'
      | 'juil'
      | 'aout'
      | 'sept'
      | 'oct'
      | 'nov'
      | 'dec'

  interface SmSubActivity {
    id: string
    label: string
    months: Record<MonthKey, boolean>
    progress: '' | 'en_cours' | 'fait'
    observation: string
  }

  type ActivityStatus = 'not_started' | 'in_progress' | 'completed'

  interface SmActivity {
    id: string
    label: string
    startDate: string
    plannedDays: number
    endDate: string
    status: ActivityStatus
    progress: number
    responsible: string
    contributors: string
    report: string
    subActivities: SmSubActivity[]
  }

  interface PlanRecord {
    id: number
    site_id: number | null
    type: string
    title: string
    year: number
    status: 'draft' | 'validated' | 'in_progress' | 'completed' | 'archived'
    content?: {
      note?: string
      format_plan?: 'jour' | 'semaine' | 'mois'
      display_mode?: 'court' | 'liste'
      activities?: unknown
    }
  }

  interface ConsolidatedActionItem {
    id: string
    type: string
    source_id: number
    tracking_id: number | null
    related_activity: string
    start_date: string | null
    end_date: string | null
    responsible_name: string | null
    involved_people: string | null
    recurrence: 'one_time' | 'weekly' | 'monthly' | 'quarterly' | 'semiannual' | 'annual'
    is_locked: boolean
    can_track: boolean
    window_open: boolean
    is_verifier: boolean
    tracking: null | {
      id: number
      status: 'non_demarre' | 'en_cours' | 'termine'
      progress_rate: number
      notes: string | null
    }
  }

  const SMQ_DEFAULT_NOTE
    = 'NOTE : Cette planification est complétée par le plan de sensibilisation, le plan de formation, le programme d\'audit et autres planifications.'

  const monthColumns: Array<{ key: MonthKey, label: string }> = [
    { key: 'jan', label: 'Jan' },
    { key: 'fev', label: 'Fév' },
    { key: 'mar', label: 'Mar' },
    { key: 'avr', label: 'Avr' },
    { key: 'mai', label: 'Mai' },
    { key: 'juin', label: 'Juin' },
    { key: 'juil', label: 'Juil' },
    { key: 'aout', label: 'Août' },
    { key: 'sept', label: 'Sept' },
    { key: 'oct', label: 'Oct' },
    { key: 'nov', label: 'Nov' },
    { key: 'dec', label: 'Déc' },
  ]

  const defaultSubActivityLabels = [
    'Etablissement et réception des rapports mensuels et par processus',
    'Rédaction et diffusion du rapport semestriel de performance global',
    'Remplissage du tableau de bord',
    'Analyse des informations sur les risques et opportunités',
    'Evaluation de l\'efficacité des actions de maîtrise des risques et opportunités du SMQ',
    'Actualisation des risques/opportunités et du plan de maîtrise',
    'Diffusion du plan de maîtrise des risques et opportunités',
    'Organisation des activités d\'évaluation et d\'amélioration du SMQ',
    'Collecte et analyse de la satisfaction du personnel et des fournisseurs',
    'Collecte de données et analyse de la performance des fournisseurs et du personnel',
    'Analyse des retours d\'information clients (satisfaction et réclamations)',
    'Organisation des activités d\'audit',
    'Audit de certification',
    'Audit interne',
    'Organisation de la revue de direction',
    'Préparation de la revue de direction',
    'Déroulement de la séance de revue de direction',
    'Rédaction et diffusion du rapport de revue de direction',
  ]

  const planStatuses = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'Validé', value: 'validated' },
    { title: 'En cours de validation', value: 'in_progress' },
    { title: 'Terminé', value: 'completed' },
    { title: 'Archivé', value: 'archived' },
  ] as const

  const rowProgressOptions = [
    { title: 'Non démarré', value: '' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Fait', value: 'fait' },
  ] as const

  const planFormatOptions = [
    { title: 'Jours', value: 'jour' },
    { title: 'Semaines', value: 'semaine' },
    { title: 'Mois', value: 'mois' },
  ] as const

  const displayModeOptions = [
    { title: 'Liste', value: 'liste' },
    { title: 'Court', value: 'court' },
  ] as const

  const activityStatusOptions = [
    { title: 'Non démarré', value: 'not_started' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Terminé', value: 'completed' },
  ] as const

  let activitySequence = 0
  let subActivitySequence = 0

  function newActivityId (): string {
    activitySequence += 1
    return `smq-activity-${Date.now()}-${activitySequence}`
  }

  function newSubActivityId (): string {
    subActivitySequence += 1
    return `smq-subactivity-${Date.now()}-${subActivitySequence}`
  }

  const authStore = useAuthStore()
  const toast = useToast()

  const selectedYear = ref(new Date().getFullYear())
  const planStatus = ref<'draft' | 'validated' | 'in_progress' | 'completed' | 'archived'>(
    resolveDefaultPlanStatus(),
  )
  const planFormat = ref<'jour' | 'semaine' | 'mois'>('jour')
  const displayMode = ref<'court' | 'liste'>('liste')
  const note = ref(SMQ_DEFAULT_NOTE)
  const activities = ref<SmActivity[]>(buildDefaultActivities())
  const collapsedActivities = ref<Set<string>>(new Set())
  const planId = ref<number | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const exporting = ref(false)
  const trackingLoading = ref(false)
  const consolidatedActions = ref<ConsolidatedActionItem[]>([])

  const trackingHeaders = [
    { title: 'Activité / sous-activité liée', key: 'related_activity' },
    { title: 'Date début', key: 'start_date' },
    { title: 'Date fin', key: 'end_date' },
    { title: 'Responsable action', key: 'responsible_name' },
    { title: 'Responsables impliqués', key: 'involved_people' },
    { title: 'Suivi', key: 'follow_up', sortable: false },
  ]

  const statusItems = [
    { title: 'Non démarré', value: 'non_demarre' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Terminé', value: 'termine' },
  ] as const

  const followUpDialog = ref({
    open: false,
    item: null as ConsolidatedActionItem | null,
    status: 'non_demarre' as 'non_demarre' | 'en_cours' | 'termine',
    progress_rate: 0,
    notes: '',
    verifier_comment: '',
    deadline: '',
    saving: false,
  })

  const subActivitiesCount = computed(() => {
    return activities.value.reduce(
      (acc, activity) => acc + activity.subActivities.length,
      0,
    )
  })

  const completionRate = computed(() => {
    if (activities.value.length === 0) return 0

    const totalProgress = activities.value.reduce(
      (acc, activity) => acc + normalizeActivityProgress(activity.status, activity.progress),
      0,
    )

    return Math.round(totalProgress / activities.value.length)
  })

  const doneSubActivities = computed(() => {
    return activities.value.filter(activity => activity.status === 'completed').length
  })

  const inProgressSubActivities = computed(() => {
    return activities.value.filter(activity => activity.status === 'in_progress').length
  })

  const notStartedSubActivities = computed(() => {
    return Math.max(0, activities.value.length - doneSubActivities.value - inProgressSubActivities.value)
  })

  const coveredMonthsCount = computed(() => {
    const monthSet = new Set<string>()
    for (const activity of activities.value) {
      for (const sub of activity.subActivities) {
        for (const month of monthColumns) {
          if (sub.months[month.key]) {
            monthSet.add(month.key)
          }
        }
      }
    }
    return monthSet.size
  })

  const canSave = computed(() => {
    return (
      Boolean(authStore.currentSiteId)
      && planStatus.value !== 'archived'
      && activities.value.length > 0
      && activities.value.every(activity => {
        const hasActivityLabel = activity.label.trim().length > 0
        const hasStartDate = activity.startDate.trim().length > 0
        const hasPlannedDays = Number(activity.plannedDays) > 0
        const hasSubActivities = activity.subActivities.length > 0
        const subActivitiesAreValid = activity.subActivities.every(
          sub => sub.label.trim().length > 0,
        )
        return hasActivityLabel
          && hasStartDate
          && hasPlannedDays
          && hasSubActivities
          && subActivitiesAreValid
      })
    )
  })

  onMounted(async () => {
    await loadPlanForYear()
    await fetchConsolidatedActions()
  })

  watch(selectedYear, async () => {
    await loadPlanForYear()
  })

  watch(
    () => authStore.user,
    () => {
      if (!planId.value) {
        planStatus.value = resolveDefaultPlanStatus()
      }
    },
    { deep: true },
  )

  function getRoleName (role: any): string | null {
    if (typeof role === 'string') return role
    if (typeof role?.name === 'string') return role.name
    if (typeof role?.attributes?.name === 'string') return role.attributes.name
    return null
  }

  function hasRole (user: any, roleName: string): boolean {
    const roleNames = Array.isArray(user?.role_names) ? user.role_names : []
    if (roleNames.includes(roleName)) return true

    if (typeof user?.access_role === 'string' && user.access_role === roleName) {
      return true
    }

    const roles = user?.roles
    if (!Array.isArray(roles)) return false
    return roles.some((role: any) => getRoleName(role) === roleName)
  }

  function isEnterpriseAdminUser (user: any): boolean {
    if (!user || user.user_type !== 'company') return false
    if (hasRole(user, 'admin_entreprise')) return true

    if (user?.enterprise?.email && user?.email) {
      return (
        String(user.enterprise.email).toLowerCase()
        === String(user.email).toLowerCase()
      )
    }

    return false
  }

  function resolveDefaultPlanStatus ():
    | 'draft'
    | 'validated'
    | 'in_progress'
    | 'completed' {
    const user = authStore.user as any

    if (user?.user_type === 'super_admin' || isEnterpriseAdminUser(user)) {
      return 'validated'
    }

    return 'in_progress'
  }

  function createEmptyMonths (): Record<MonthKey, boolean> {
    return {
      jan: false,
      fev: false,
      mar: false,
      avr: false,
      mai: false,
      juin: false,
      juil: false,
      aout: false,
      sept: false,
      oct: false,
      nov: false,
      dec: false,
    }
  }

  function createSubActivity (label = ''): SmSubActivity {
    return {
      id: newSubActivityId(),
      label,
      months: createEmptyMonths(),
      progress: '',
      observation: '',
    }
  }

  function buildDefaultActivities (): SmActivity[] {
    const defaultStartDate = new Date().toISOString().split('T')[0] || ''

    return [
      {
        id: newActivityId(),
        label: 'Planification du SMQ',
        startDate: defaultStartDate,
        plannedDays: 30,
        endDate: computeEndDate(defaultStartDate, 30),
        status: 'not_started',
        progress: 0,
        responsible: '',
        contributors: '',
        report: '',
        subActivities: defaultSubActivityLabels.map(item =>
          createSubActivity(item),
        ),
      },
    ]
  }

  function resolvePlanSiteId (raw: any, attrs: any): number | null {
    const relationSite = raw?.relationships?.site

    const relationSiteCandidate
      = relationSite?.id
        ?? relationSite?.data?.id
        ?? relationSite?.attributes?.id
        ?? attrs.site_id
        ?? raw?.site_id
        ?? raw?.siteId
        ?? 0
    const relationSiteId = Number(relationSiteCandidate) || null

    return relationSiteId
  }

  function resolvePlanContent (attrs: any): PlanRecord['content'] {
    const rawContent = attrs?.content
    if (rawContent && typeof rawContent === 'object') {
      return rawContent
    }

    if (typeof rawContent === 'string' && rawContent.trim().length > 0) {
      try {
        const parsed = JSON.parse(rawContent)
        if (parsed && typeof parsed === 'object') {
          return parsed
        }
      } catch {
      // Ignore invalid JSON and fallback to an empty content object.
      }
    }

    return {}
  }

  function normalizePlanRecord (raw: any): PlanRecord {
    const attrs
      = raw?.attributes && typeof raw.attributes === 'object'
        ? raw.attributes
        : raw

    return {
      id: Number(raw?.id || attrs?.id || 0),
      site_id: resolvePlanSiteId(raw, attrs),
      type: String(attrs?.type || ''),
      title: String(attrs?.title || ''),
      year: Number(attrs?.year || 0),
      status: normalizePlanStatus(attrs?.status),
      content: resolvePlanContent(attrs),
    }
  }

  function normalizePlanStatus (
    value: unknown,
  ): 'draft' | 'validated' | 'in_progress' | 'completed' | 'archived' {
    if (
      value === 'validated'
      || value === 'in_progress'
      || value === 'completed'
      || value === 'archived'
    ) {
      return value
    }
    return 'draft'
  }

  function resetFormToDefault (): void {
    planId.value = null
    note.value = SMQ_DEFAULT_NOTE
    planStatus.value = resolveDefaultPlanStatus()
    planFormat.value = 'jour'
    displayMode.value = 'liste'
    activities.value = buildDefaultActivities()
  }

  async function loadPlanForYear (): Promise<void> {
    if (!authStore.currentSiteId) {
      resetFormToDefault()
      return
    }

    loading.value = true
    try {
      const { data } = await api.get('/plans', {
        params: {
          site_id: authStore.currentSiteId,
          type: 'smq',
          year: selectedYear.value,
          per_page: 100,
        },
      })

      const rawItems = Array.isArray(data?.data)
        ? data.data
        : (Array.isArray(data)
          ? data
          : [])

      const records = rawItems.map(item => normalizePlanRecord(item))

      const matched = records.find(item => {
        const sameYear = item.year === selectedYear.value
        const sameType = item.type === 'smq'
        const sameSite
          = item.site_id === null || item.site_id === authStore.currentSiteId
        return sameYear && sameType && sameSite
      })

      if (!matched) {
        resetFormToDefault()
        return
      }

      planId.value = matched.id
      planStatus.value = matched.status
      planFormat.value = matched.content?.format_plan === 'semaine' || matched.content?.format_plan === 'mois'
        ? matched.content.format_plan
        : 'jour'
      displayMode.value = matched.content?.display_mode === 'court' ? 'court' : 'liste'
      note.value
        = typeof matched.content?.note === 'string'
          && matched.content.note.trim().length > 0
          ? matched.content.note
          : SMQ_DEFAULT_NOTE

      const persistedActivities = Array.isArray(matched.content?.activities)
        ? matched.content.activities
        : []

      activities.value
        = persistedActivities.length > 0
          ? persistedActivities.map((item, index) =>
            normalizeActivity(item, index),
          )
          : buildDefaultActivities()
    } catch (error) {
      console.error('[PlansSM] Chargement échoué', error)
      toast.error('Impossible de charger le plan du SM pour cette année.')
      resetFormToDefault()
    } finally {
      loading.value = false
    }
  }

  function normalizeActivity (raw: unknown, index: number): SmActivity {
    const source
      = raw && typeof raw === 'object' ? (raw as Record<string, any>) : {}

    const rawSubActivities = Array.isArray(source.subActivities)
      ? source.subActivities
      : []

    const normalizedSubActivities
      = rawSubActivities.length > 0
        ? rawSubActivities.map((item, subIndex) =>
          normalizeSubActivity(item, subIndex),
        )
        : [normalizeLegacySubActivity(source, index)]

    const activity: SmActivity = {
      id: newActivityId(),
      label: String(source.label || `Activité ${index + 1}`),
      startDate: normalizeDateInput(source.startDate || source.start_date),
      plannedDays: normalizePositiveDays(source.plannedDays || source.planned_days),
      endDate: '',
      status: normalizeActivityStatus(source.status),
      progress: normalizeRawProgress(source.progress),
      responsible: String(source.responsible || ''),
      contributors: String(source.contributors || ''),
      report: String(source.report || ''),
      subActivities: normalizedSubActivities,
    }

    syncActivitySchedule(activity)

    return activity
  }

  function normalizeLegacySubActivity (
    source: Record<string, any>,
    index: number,
  ): SmSubActivity {
    const label = String(source.label || `Sous-activité ${index + 1}`)
    return normalizeSubActivity(
      {
        label,
        months: source.months,
        progress: source.progress,
        observation: source.observation,
        ...source,
      },
      index,
    )
  }

  function normalizeSubActivity (raw: unknown, index: number): SmSubActivity {
    const source
      = raw && typeof raw === 'object' ? (raw as Record<string, any>) : {}
    const sourceMonths
      = source.months && typeof source.months === 'object'
        ? (source.months as Record<string, any>)
        : {}

    const months = createEmptyMonths()

    for (const month of monthColumns) {
      const v = sourceMonths[month.key] ?? source[month.key]
      months[month.key] = Boolean(v)
    }

    const progress
      = source.progress === 'en_cours' || source.progress === 'fait'
        ? source.progress
        : ''

    return {
      id: newSubActivityId(),
      label: String(source.label || `Sous-activité ${index + 1}`),
      months,
      progress,
      observation: String(source.observation || ''),
    }
  }

  function normalizeDateInput (value: unknown): string {
    return typeof value === 'string' && value.trim().length > 0
      ? value
      : (new Date().toISOString().split('T')[0] || '')
  }

  function normalizePositiveDays (value: unknown): number {
    const parsed = Number(value)
    if (Number.isFinite(parsed) && parsed > 0) {
      return Math.min(365, Math.round(parsed))
    }

    return 5
  }

  function normalizeActivityStatus (value: unknown): ActivityStatus {
    if (value === 'in_progress' || value === 'completed') {
      return value
    }

    return 'not_started'
  }

  function normalizeRawProgress (value: unknown): number {
    const parsed = Number(value)
    if (Number.isFinite(parsed)) {
      return Math.max(0, Math.min(100, Math.round(parsed)))
    }

    return 0
  }

  function normalizeActivityProgress (status: ActivityStatus, progress: number): number {
    if (status === 'completed') {
      return 100
    }

    if (status === 'not_started') {
      return 0
    }

    return Math.max(1, Math.min(99, normalizeRawProgress(progress) || 50))
  }

  function computeEndDate (startDate: string, plannedDays: number): string {
    if (!startDate || plannedDays <= 0) {
      return ''
    }

    const start = new Date(`${startDate}T00:00:00`)
    if (Number.isNaN(start.getTime())) {
      return ''
    }

    start.setDate(start.getDate() + plannedDays - 1)
    return start.toISOString().split('T')[0] || ''
  }

  function syncActivitySchedule (activity: SmActivity): void {
    activity.startDate = normalizeDateInput(activity.startDate)
    activity.plannedDays = normalizePositiveDays(activity.plannedDays)
    activity.endDate = computeEndDate(activity.startDate, activity.plannedDays)
    activity.progress = normalizeActivityProgress(activity.status, activity.progress)
  }

  function toggleActivityCollapse (activityId: string): void {
    if (collapsedActivities.value.has(activityId)) {
      collapsedActivities.value.delete(activityId)
    } else {
      collapsedActivities.value.add(activityId)
    }
  }

  function collapseAll (): void {
    collapsedActivities.value = new Set(activities.value.map(a => a.id))
  }

  function expandAll (): void {
    collapsedActivities.value.clear()
  }

  function addActivity (): void {
    const startDate = new Date().toISOString().split('T')[0] || ''
    const activity: SmActivity = {
      id: newActivityId(),
      label: '',
      startDate,
      plannedDays: 5,
      endDate: '',
      status: 'not_started',
      progress: 0,
      responsible: '',
      contributors: '',
      report: '',
      subActivities: [createSubActivity()],
    }

    syncActivitySchedule(activity)
    activities.value.push(activity)
  }

  function removeActivity (index: number): void {
    if (activities.value.length <= 1) {
      return
    }
    activities.value.splice(index, 1)
  }

  function addSubActivity (activityIndex: number): void {
    const activity = activities.value[activityIndex]
    if (!activity) {
      return
    }

    activity.subActivities.push(createSubActivity())
  }

  function removeSubActivity (payload: {
    activityIndex: number
    subActivityIndex: number
  }): void {
    const { activityIndex, subActivityIndex } = payload
    const activity = activities.value[activityIndex]
    if (!activity || activity.subActivities.length <= 1) {
      return
    }

    activity.subActivities.splice(subActivityIndex, 1)
  }

  function toggleMonth (payload: {
    activityIndex: number
    subActivityIndex: number
    monthKey: string
  }): void {
    const { activityIndex, subActivityIndex, monthKey } = payload
    const month = monthKey as MonthKey
    const target
      = activities.value[activityIndex]?.subActivities[subActivityIndex]
    if (!target) {
      return
    }

    target.months[month] = !target.months[month]
  }

  function updateActivitySchedule (activityIndex: number): void {
    const activity = activities.value[activityIndex]
    if (!activity) {
      return
    }

    syncActivitySchedule(activity)
  }

  function updateActivityStatus (payload: {
    activityIndex: number
    status: ActivityStatus
  }): void {
    const activity = activities.value[payload.activityIndex]
    if (!activity) {
      return
    }

    activity.status = payload.status
    syncActivitySchedule(activity)
  }

  function updateActivityProgress (payload: {
    activityIndex: number
    progress: number
  }): void {
    const activity = activities.value[payload.activityIndex]
    if (!activity) {
      return
    }

    const normalized = normalizeRawProgress(payload.progress)
    activity.progress = normalized

    if (normalized >= 100) {
      activity.status = 'completed'
    } else if (normalized <= 0) {
      activity.status = 'not_started'
    } else {
      activity.status = 'in_progress'
    }

    syncActivitySchedule(activity)
  }

  async function savePlan (): Promise<void> {
    if (!authStore.currentSiteId) {
      toast.error('Sélectionnez un site avant d\'enregistrer.')
      return
    }

    if (!canSave.value) {
      toast.error('Renseignez toutes les activités et sous-activités.')
      return
    }

    saving.value = true
    try {
      const payload = {
        site_id: authStore.currentSiteId,
        type: 'smq',
        title: `Planification du SMQ ${selectedYear.value}`,
        year: selectedYear.value,
        status: planStatus.value,
        content: {
          note: note.value,
          format_plan: planFormat.value,
          display_mode: displayMode.value,
          activities: activities.value.map(activity => ({
            label: activity.label.trim(),
            start_date: activity.startDate,
            planned_days: normalizePositiveDays(activity.plannedDays),
            end_date: computeEndDate(activity.startDate, activity.plannedDays),
            status: activity.status,
            progress: normalizeActivityProgress(activity.status, activity.progress),
            responsible: activity.responsible.trim(),
            contributors: activity.contributors.trim(),
            report: activity.report.trim(),
            subActivities: activity.subActivities.map(sub => ({
              label: sub.label.trim(),
              months: sub.months,
              progress: sub.progress,
              observation: sub.observation,
            })),
          })),
        },
      }

      if (planId.value) {
        await api.put(`/plans/${planId.value}`, payload)
      } else {
        const { data } = await api.post('/plans', payload)
        const created = normalizePlanRecord(data?.data || data)
        planId.value = created.id || null
      }

      toast.success('Plan du SM enregistré avec succès.')
      await loadPlanForYear()
    } catch (error) {
      console.error('[PlansSM] Enregistrement échoué', error)
      toast.error('Erreur lors de l\'enregistrement du plan du SM.')
    } finally {
      saving.value = false
    }
  }

  async function exportPlanXlsx (): Promise<void> {
    if (!authStore.currentSiteId) {
      toast.error('Sélectionnez un site avant l\'export.')
      return
    }

    if (!planId.value && canSave.value) {
      await savePlan()
    }

    exporting.value = true
    try {
      const response = await api.get('/plans/export-xlsx', {
        params: {
          site_id: authStore.currentSiteId,
          type: 'smq',
          year: selectedYear.value,
        },
        responseType: 'blob',
      })

      const blob = response.data as Blob
      const filename
        = extractFilenameFromHeaders(response.headers?.['content-disposition'])
          || `plans_du_sm_${selectedYear.value}.xlsx`

      const link = document.createElement('a')
      const url = window.URL.createObjectURL(blob)
      link.href = url
      link.setAttribute('download', filename)
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      toast.success('Export Excel généré avec succès.')
    } catch (error: any) {
      console.error('[PlansSM] Export échoué', error)
      const message
        = error?.response?.status === 404
          ? 'Aucun plan sauvegardé à exporter pour cette année.'
          : 'Erreur lors de l’export du plan du SM.'
      toast.error(message)
    } finally {
      exporting.value = false
    }
  }

  function extractFilenameFromHeaders (
    contentDisposition: unknown,
  ): string | null {
    const disposition = String(contentDisposition || '')

    const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i)
    if (utf8Match?.[1]) {
      return decodeURIComponent(utf8Match[1]).replace(/["']/g, '')
    }

    const simpleMatch = disposition.match(/filename="?([^"]+)"?/i)
    if (simpleMatch?.[1]) {
      return simpleMatch[1]
    }

    return null
  }

  function planStatusTitle (
    status: 'draft' | 'validated' | 'in_progress' | 'completed' | 'archived',
  ): string {
    if (status === 'validated') return 'Validé'
    if (status === 'in_progress') return 'En cours de validation'
    if (status === 'completed') return 'Terminé'
    if (status === 'archived') return 'Archivé'
    return 'Brouillon'
  }

  function planStatusColor (
    status: 'draft' | 'validated' | 'in_progress' | 'completed' | 'archived',
  ): string {
    if (status === 'validated') return 'success'
    if (status === 'in_progress') return 'warning'
    if (status === 'completed') return 'info'
    if (status === 'archived') return 'grey-darken-1'
    return 'grey'
  }

  function formatDate (value: string | null): string {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '—'
    return date.toLocaleDateString('fr-FR')
  }

  function canOpenFollowUp (item: ConsolidatedActionItem): boolean {
    if (!item.can_track) return false
    if (item.is_verifier) return true
    if (item.is_locked) return false
    return item.window_open
  }

  function openFollowUp (item: ConsolidatedActionItem): void {
    const baseStatus = item.tracking?.status || 'non_demarre'
    const baseRate = item.tracking?.progress_rate ?? (baseStatus === 'termine' ? 100 : 0)
    followUpDialog.value = {
      open: true,
      item,
      status: baseStatus,
      progress_rate: baseRate,
      notes: item.tracking?.notes || '',
      verifier_comment: '',
      deadline: item.end_date || '',
      saving: false,
    }
  }

  async function fetchConsolidatedActions (): Promise<void> {
    trackingLoading.value = true
    try {
      const { data } = await api.get('/action-plans/tracking', {
        params: { site_id: authStore.currentSiteId || undefined },
      })
      const rows = Array.isArray(data?.data) ? data.data : []
      consolidatedActions.value = rows.map((row: any) => ({
        id: String(row.id),
        type: String(row.type || 'action'),
        source_id: Number(row.source_id || 0),
        tracking_id: row.tracking?.id ? Number(row.tracking.id) : null,
        related_activity: String(row.related_activity || 'Action'),
        start_date: row.start_date || null,
        end_date: row.end_date || null,
        responsible_name: row.responsible_name || null,
        involved_people: row.involved_people || null,
        recurrence: row.recurrence || 'one_time',
        is_locked: Boolean(row.is_locked),
        can_track: Boolean(row.can_track),
        window_open: Boolean(row.window_open),
        is_verifier: Boolean(row.is_verifier),
        tracking: row.tracking || null,
      }))
    } catch {
      consolidatedActions.value = []
      toast.error('Impossible de charger le suivi des actions.')
    } finally {
      trackingLoading.value = false
    }
  }

  async function saveFollowUp (): Promise<void> {
    const d = followUpDialog.value
    if (!d.item) return
    d.saving = true
    try {
      const payload: any = {
        status: d.status,
        progress_rate: Number(d.progress_rate),
        notes: d.notes || null,
      }
      if (d.item.tracking_id && d.item.is_verifier) {
        payload.verifier_comment = d.verifier_comment || 'Mise à jour vérificateur'
        await api.patch(`/action-plans/tracking/${d.item.tracking_id}/verify`, payload)
      } else {
        await api.post(`/action-plans/tracking/${d.item.type}/${d.item.source_id}`, payload)
      }
      if (d.item.is_verifier && d.deadline && d.deadline !== d.item.end_date) {
        await api.patch(`/action-plans/tracking/${d.item.type}/${d.item.source_id}/deadline`, {
          deadline: d.deadline,
          reason: d.verifier_comment || null,
        })
      }
      d.open = false
      toast.success('Suivi enregistré avec succès.')
      await fetchConsolidatedActions()
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors de l’enregistrement du suivi.')
    } finally {
      d.saving = false
    }
  }
</script>

<style scoped>
.sm-shell {
  background:
    radial-gradient(circle at 6% 0%, rgb(15 23 42 / 0.05), transparent 40%),
    radial-gradient(
      circle at 100% 100%,
      rgb(59 130 246 / 0.06),
      transparent 35%
    );
}

:deep(.plan-hero) {
  position: relative;
  overflow: hidden;
  background: linear-gradient(
    135deg,
    rgb(255 255 255 / 1) 0%,
    rgb(239 246 255 / 0.98) 50%,
    rgb(219 234 254 / 0.85) 100%
  );
  border: 1px solid rgb(226 232 240 / 0.8);
  box-shadow:
    0 10px 40px rgb(15 23 42 / 0.08),
    0 2px 8px rgb(15 23 42 / 0.04);
}

:deep(.hero-content) {
  padding: 2rem;
}

@media (min-width: 640px) {
  :deep(.hero-content) {
    padding: 2.5rem;
  }
}

:deep(.hero-header) {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

@media (min-width: 1280px) {
  :deep(.hero-header) {
    flex-direction: row;
    align-items: flex-start;
    justify-content: space-between;
  }
}

:deep(.hero-text-section) {
  flex: 1;
  max-width: 46rem;
}

:deep(.hero-badge) {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.875rem;
  background: linear-gradient(
    135deg,
    rgb(59 130 246 / 0.12),
    rgb(37 99 235 / 0.08)
  );
  border: 1px solid rgb(59 130 246 / 0.2);
  border-radius: 9999px;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: rgb(30 64 175);
  margin-bottom: 1rem;
}

:deep(.hero-title-row) {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.875rem;
}

:deep(.hero-title) {
  font-size: 1.875rem;
  font-weight: 800;
  line-height: 1.2;
  color: rgb(15 23 42);
  letter-spacing: -0.02em;
}

@media (min-width: 640px) {
  :deep(.hero-title) {
    font-size: 2.25rem;
  }
}

:deep(.hero-chips) {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

:deep(.status-chip),
:deep(.year-chip) {
  font-weight: 600;
  box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
}

:deep(.hero-description) {
  font-size: 0.9375rem;
  line-height: 1.65;
  color: rgb(71 85 105);
  max-width: 65ch;
}

@media (min-width: 640px) {
  :deep(.hero-description) {
    font-size: 1rem;
  }
}

:deep(.hero-controls-section) {
  flex-shrink: 0;
}

@media (min-width: 1280px) {
  :deep(.hero-controls-section) {
    width: 22rem;
  }
}

:deep(.controls-card) {
  background: linear-gradient(
    180deg,
    rgb(255 255 255 / 0.95),
    rgb(248 250 252 / 0.9)
  );
  border: 1px solid rgb(226 232 240 / 0.8);
  border-radius: 1rem;
  padding: 1.25rem;
  box-shadow: 0 8px 24px rgb(15 23 42 / 0.08);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

:deep(.control-group) {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

:deep(.control-label) {
  display: flex;
  align-items: center;
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgb(51 65 85);
}

:deep(.control-input .v-field) {
  border-radius: 0.75rem;
  box-shadow: 0 2px 8px rgb(15 23 42 / 0.06);
  transition: all 0.2s ease;
}

:deep(.control-input .v-field:hover) {
  box-shadow: 0 4px 12px rgb(15 23 42 / 0.1);
}

:deep(.metrics-grid) {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1rem;
}

@media (min-width: 640px) {
  :deep(.metrics-grid) {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  :deep(.metrics-grid) {
    grid-template-columns: repeat(4, 1fr);
  }
}

:deep(.metric-card) {
  background: linear-gradient(
    135deg,
    rgb(255 255 255 / 1) 0%,
    rgb(248 250 252 / 0.95) 100%
  );
  border: 1px solid rgb(226 232 240 / 0.8);
  border-radius: 1rem;
  padding: 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  box-shadow: 0 4px 16px rgb(15 23 42 / 0.06);
  transition: all 0.25s ease;
}

:deep(.metric-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgb(15 23 42 / 0.1);
  border-color: rgb(203 213 225);
}

:deep(.metric-icon-wrapper) {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

:deep(.metric-icon-wrapper.primary) {
  background: linear-gradient(
    135deg,
    rgb(59 130 246 / 0.15),
    rgb(37 99 235 / 0.1)
  );
  color: rgb(37 99 235);
}

:deep(.metric-icon-wrapper.secondary) {
  background: linear-gradient(
    135deg,
    rgb(139 92 246 / 0.15),
    rgb(124 58 237 / 0.1)
  );
  color: rgb(124 58 237);
}

:deep(.metric-icon-wrapper.success) {
  background: linear-gradient(
    135deg,
    rgb(34 197 94 / 0.15),
    rgb(22 163 74 / 0.1)
  );
  color: rgb(22 163 74);
}

:deep(.metric-icon-wrapper.info) {
  background: linear-gradient(
    135deg,
    rgb(14 165 233 / 0.15),
    rgb(2 132 199 / 0.1)
  );
  color: rgb(2 132 199);
}

:deep(.metric-content) {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

:deep(.metric-label) {
  font-size: 0.8125rem;
  font-weight: 500;
  color: rgb(100 116 139);
}

:deep(.metric-value) {
  font-size: 1.75rem;
  font-weight: 800;
  line-height: 1;
  color: rgb(15 23 42);
}

:deep(.metric-value-inline) {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1;
  color: rgb(15 23 42);
}

:deep(.progress-card) {
  grid-column: span 1;
}

@media (min-width: 640px) {
  :deep(.progress-card) {
    grid-column: span 2;
  }
}

@media (min-width: 1024px) {
  :deep(.progress-card) {
    grid-column: span 1;
  }
}

:deep(.progress-bar) {
  border-radius: 9999px;
  box-shadow: inset 0 1px 3px rgb(0 0 0 / 0.1);
}

:deep(.distribution-card) {
  grid-column: span 1;
}

@media (min-width: 640px) {
  :deep(.distribution-card) {
    grid-column: span 2;
  }
}

@media (min-width: 1024px) {
  :deep(.distribution-card) {
    grid-column: span 1;
  }
}

:deep(.distribution-chips) {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

:deep(.dist-chip) {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

:deep(.dist-chip.success) {
  background: rgb(34 197 94 / 0.12);
  color: rgb(22 101 52);
}

:deep(.dist-chip.warning) {
  background: rgb(251 146 60 / 0.12);
  color: rgb(154 52 18);
}

:deep(.dist-chip.grey) {
  background: rgb(148 163 184 / 0.12);
  color: rgb(51 65 85);
}

:deep(.dist-dot) {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
}

:deep(.dist-chip.success .dist-dot) {
  background: rgb(34 197 94);
}

:deep(.dist-chip.warning .dist-dot) {
  background: rgb(251 146 60);
}

:deep(.dist-chip.grey .dist-dot) {
  background: rgb(148 163 184);
}

:deep(.metric-footer) {
  display: flex;
  align-items: center;
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: rgb(100 116 139);
}

:deep(.info-banner) {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem;
  background: linear-gradient(
    135deg,
    rgb(219 234 254 / 0.5),
    rgb(191 219 254 / 0.4)
  );
  border: 1px solid rgb(147 197 253 / 0.5);
  border-radius: 1rem;
  box-shadow: 0 4px 12px rgb(59 130 246 / 0.08);
}

:deep(.info-icon) {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.75rem;
  background: linear-gradient(
    135deg,
    rgb(59 130 246 / 0.2),
    rgb(37 99 235 / 0.15)
  );
  color: rgb(37 99 235);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

:deep(.info-content) {
  flex: 1;
}

:deep(.info-title) {
  font-size: 0.875rem;
  font-weight: 700;
  color: rgb(30 58 138);
  margin-bottom: 0.25rem;
}

:deep(.info-text) {
  font-size: 0.875rem;
  line-height: 1.5;
  color: rgb(30 64 175);
}

:deep(.note-card) {
  background: linear-gradient(
    180deg,
    rgb(255 255 255 / 1) 0%,
    rgb(248 250 252 / 0.65) 100%
  );
  border-color: rgb(226 232 240 / 1) !important;
}
</style>
