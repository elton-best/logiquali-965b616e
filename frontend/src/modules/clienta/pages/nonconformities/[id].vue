/**
 * Non-Conformity Details Page - Modern 2026 Design with Tabs
 * View and manage a specific non-conformity
 */

<script setup lang="ts">
  import type { NCStatus, UpdateNCDTO } from '@/api/services/nonconformities.service'
  import {
    Activity,

    AlertTriangle,
    ArrowLeft,
    Calendar,
    CheckCircle,
    Clock,
    Edit,
    FileText,
    History,
    Image,
    MapPin,
    MessageSquare,

    Shield,
    Target,

    User,
  } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useNonConformities } from '@/modules/clienta/composables/useNonConformities'
  import { useUsers } from '@/modules/clienta/composables/useUsers'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getErrorMessage } from '@/utils/errorMessage'
  import DocumentFlowButton from '@/components/documents/DocumentFlowButton.vue'
  import { nonConformityService } from '@/services/nonConformityService'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const {
    currentNC,
    loading,
    error,
    fetchNonConformity,
    updateStatus,
    assignResponsible,
  } = useNonConformities()

  const { users, fetchUsers } = useUsers()

  // Demo mode - use demo data if ID is 'demo'
  const _isDemoMode = ref(false)
  const _demoNC = ref({
    id: 999,
    reference: 'NC-2026-001',
    title: 'Équipements de protection non conformes',
    description: 'Plusieurs casques de sécurité présentent des fissures et des sangles usées, ne garantissant plus la protection adéquate des travailleurs.',
    type: 'majeure' as const,
    source: 'interne' as const,
    status: 'analysis' as NCStatus,
    severity: 4,
    site_id: 1,
    site: { id: 1, name: 'Site Principal - Paris' },
    detected_by: 'Marie Dupont',
    detected_date: '2026-01-15',
    created_at: '2026-01-15T09:30:00Z',
    updated_at: '2026-01-20T14:45:00Z',
    due_date: '2026-02-15',
    closure_date: null,
    root_cause: 'Absence de contrôle périodique des EPI et stockage inapproprié exposant les équipements aux UV.',
    immediate_action: 'Retrait immédiat des équipements défectueux et distribution de nouveaux casques de sécurité.',
    corrective_action: 'Mise en place d\'un programme de contrôle mensuel des EPI et création d\'un registre de suivi.',
    preventive_action: 'Formation du personnel sur l\'inspection visuelle des EPI et installation d\'un local de stockage adapté.',
    responsible_id: 2,
    responsible: {
      id: 2,
      name: 'Jean Martin',
      email: 'jean.martin@example.com',
    },
  })

  // Tabs
  const _activeTab = ref('details')
  const _tabs = [
    { value: 'details', label: 'Détails', icon: FileText },
    { value: 'analysis', label: 'Analyse', icon: Activity },
    { value: 'actions', label: 'Actions', icon: Target },
    { value: 'photos', label: 'Photos', icon: Image },
    { value: 'comments', label: 'Commentaires', icon: MessageSquare },
    { value: 'history', label: 'Historique', icon: History },
  ]

  // Comments
  const _newComment = ref('')
  const _comments = ref([
    {
      id: 1,
      user: 'Marie Dupont',
      avatar: 'MD',
      date: '2026-01-15 10:30',
      text: 'Non-conformité détectée lors de l\'inspection de routine. Nécessite une action immédiate.',
    },
    {
      id: 2,
      user: 'Jean Martin',
      avatar: 'JM',
      date: '2026-01-16 14:20',
      text: 'Analyse de la cause racine effectuée. Mise en place des actions correctives en cours.',
    },
    {
      id: 3,
      user: 'Sophie Bernard',
      avatar: 'SB',
      date: '2026-01-18 09:15',
      text: 'Nouveaux EPI commandés et formation du personnel planifiée pour la semaine prochaine.',
    },
  ])

  // History
  const _history = ref([
    {
      id: 1,
      action: 'NC créée',
      user: 'Marie Dupont',
      date: '2026-01-15 09:30',
      icon: FileText,
      color: 'primary',
    },
    {
      id: 2,
      action: 'Statut changé : Ouvert → En analyse',
      user: 'Jean Martin',
      date: '2026-01-16 10:00',
      icon: Activity,
      color: 'warning',
    },
    {
      id: 3,
      action: 'Responsable assigné : Jean Martin',
      user: 'Système',
      date: '2026-01-16 10:01',
      icon: User,
      color: 'info',
    },
    {
      id: 4,
      action: 'Action corrective ajoutée',
      user: 'Jean Martin',
      date: '2026-01-18 14:30',
      icon: Target,
      color: 'success',
    },
  ])

  // Demo photos
  const _demoPhotos = ref([
    'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?w=400',
    'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=400',
    'https://images.unsplash.com/photo-1581092580497-e0d23cbdf1dc?w=400',
  ])

  const isEditing = ref(false)
  const showAssignDialog = ref(false)
  const showStatusDialog = ref(false)
  const selectedResponsible = ref<number>(0)
  const selectedStatus = ref<NCStatus>('open')
  const statusComments = ref('')

  const editForm = ref<UpdateNCDTO>({})

  const statusFlow: NCStatus[] = ['open', 'analysis', 'corrective_action', 'verification', 'closed']

  const statusConfigs: Record<NCStatus, {
    color: string
    vuetifyColor: string
    icon: any
    label: string
    description: string
    nextActions: { status: NCStatus, label: string }[]
  }> = {
    open: {
      color: 'text-red-600 bg-red-100 dark:bg-red-900/30',
      vuetifyColor: 'error',
      icon: AlertTriangle,
      label: 'Ouvert',
      description: 'NC détectée, en attente d\'analyse',
      nextActions: [
        { status: 'analysis', label: 'Commencer l\'analyse' },
      ],
    },
    analysis: {
      color: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30',
      vuetifyColor: 'warning',
      icon: Activity,
      label: 'En analyse',
      description: 'Analyse de la cause racine en cours',
      nextActions: [
        { status: 'corrective_action', label: 'Passer à l\'action corrective' },
      ],
    },
    corrective_action: {
      color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
      vuetifyColor: 'info',
      icon: Edit,
      label: 'Action corrective',
      description: 'Mise en œuvre des actions correctives',
      nextActions: [
        { status: 'verification', label: 'Vérifier les actions' },
      ],
    },
    verification: {
      color: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30',
      vuetifyColor: 'secondary',
      icon: Shield,
      label: 'Vérification',
      description: 'Vérification de l\'efficacité des actions',
      nextActions: [
        { status: 'closed', label: 'Clôturer la NC' },
      ],
    },
    closed: {
      color: 'text-green-600 bg-green-100 dark:bg-green-900/30',
      vuetifyColor: 'success',
      icon: CheckCircle,
      label: 'Fermé',
      description: 'NC résolue et fermée',
      nextActions: [],
    },
  }

  const typeConfigs = {
    mineure: { color: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30', label: 'Mineure' },
    majeure: { color: 'text-orange-600 bg-orange-100 dark:bg-orange-900/30', label: 'Majeure' },
    critique: { color: 'text-red-600 bg-red-100 dark:bg-red-900/30', label: 'Critique' },
  }

  const sourceLabels = {
    audit: 'Audit',
    reclamation: 'Réclamation client',
    interne: 'Détection interne',
  }

  const currentStatusIndex = computed(() => {
    if (!currentNC.value) return 0
    return statusFlow.indexOf(currentNC.value.status)
  })

  const isOverdue = computed(() => {
    if (!currentNC.value || currentNC.value.status === 'closed' || !currentNC.value.due_date) {
      return false
    }
    return new Date(currentNC.value.due_date) < new Date()
  })

  const canEdit = computed(() => {
    return currentNC.value?.status !== 'closed'
  })

  function goBack () {
    router.push('/company/nonconformities')
  }

  async function loadData () {
    const id = Number.parseInt((route.params as { id: string }).id as string)
    if (Number.isNaN(id)) {
      router.push('/company/nonconformities')
      return
    }

    await Promise.all([
      fetchNonConformity(id),
      fetchUsers({ per_page: 100 }),
    ])
  }

  const ncGenerateDraftFn = async () => {
    const id = Number.parseInt((route.params as { id: string }).id as string)
    return nonConformityService.generateDocumentReport(id)
  }

  function startEdit () {
    if (currentNC.value) {
      editForm.value = {
        title: currentNC.value.title,
        description: currentNC.value.description,
        type: currentNC.value.type,
        source: currentNC.value.source,
        site_id: currentNC.value.site_id,
        detected_by: currentNC.value.detected_by,
        detected_date: currentNC.value.detected_date,
        severity: currentNC.value.severity,
        root_cause: currentNC.value.root_cause,
        immediate_action: currentNC.value.immediate_action,
        corrective_action: currentNC.value.corrective_action,
        preventive_action: currentNC.value.preventive_action,
        due_date: currentNC.value.due_date,
      }
      isEditing.value = true
    }
  }

  function openStatusDialog (status: NCStatus) {
    selectedStatus.value = status
    statusComments.value = ''
    showStatusDialog.value = true
  }

  async function handleStatusUpdate () {
    if (!currentNC.value) return

    try {
      await updateStatus(currentNC.value.id, {
        status: selectedStatus.value,
        comments: statusComments.value,
      })
      showStatusDialog.value = false
      await fetchNonConformity(currentNC.value.id)
      toast.success('Statut mis à jour.')
    } catch (error_) {
      console.error('Failed to update status:', error_)
      toast.error(getErrorMessage(error_, 'Impossible de mettre à jour le statut.'))
    }
  }

  function openAssignDialog () {
    selectedResponsible.value = currentNC.value?.responsible_id || 0
    showAssignDialog.value = true
  }

  async function handleAssign () {
    if (!currentNC.value || !selectedResponsible.value) return

    try {
      await assignResponsible(currentNC.value.id, {
        responsible_id: selectedResponsible.value,
      })
      showAssignDialog.value = false
      await fetchNonConformity(currentNC.value.id)
      toast.success('Responsable assigné.')
    } catch (error_) {
      console.error('Failed to assign responsible:', error_)
      toast.error(getErrorMessage(error_, 'Impossible d’assigner le responsable.'))
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  }

  function formatDateTime (date: string) {
    return new Date(date).toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  onMounted(() => {
    loadData()
  })
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <button
        class="inline-flex items-center gap-2 text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50 mb-4"
        @click="goBack"
      >
        <ArrowLeft class="w-4 h-4" />
        Retour à la liste
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !currentNC" class="card p-12 text-center">
      <div class="w-12 h-12 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4" />
      <p class="text-neutral-500">Chargement...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error && !currentNC" class="card p-6 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20">
      <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>

    <!-- NC Details -->
    <div v-else-if="currentNC" class="space-y-6">
      <!-- Title & Actions -->
      <div class="card p-6">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <span class="text-sm font-medium text-neutral-500">{{ currentNC.reference }}</span>
              <span
                :class="[
                  'badge inline-flex items-center gap-1.5',
                  typeConfigs[currentNC.type].color
                ]"
              >
                {{ typeConfigs[currentNC.type].label }}
              </span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-50 mb-2">
              {{ currentNC.title }}
            </h1>
            <p class="text-neutral-600 dark:text-neutral-400">
              {{ currentNC.description }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="canEdit && !isEditing"
              class="btn-secondary inline-flex items-center gap-2"
              @click="startEdit"
            >
              <Edit class="w-4 h-4" />
              Modifier
            </button>
          </div>
        </div>

        <!-- Overdue Warning -->
        <div v-if="isOverdue" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
          <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
            <Clock class="w-5 h-5" />
            <span class="font-medium">Cette NC est en retard !</span>
          </div>
        </div>
      </div>

      <!-- Status Timeline -->
      <div class="card p-6">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-6">
          Progression
        </h2>

        <div class="relative">
          <!-- Progress Line -->
          <div class="absolute top-5 left-0 right-0 h-1 bg-neutral-200 dark:bg-neutral-700" />
          <div
            class="absolute top-5 left-0 h-1 bg-primary-500 transition-all duration-500"
            :style="{ width: `${(currentStatusIndex / (statusFlow.length - 1)) * 100}%` }"
          />

          <!-- Status Steps -->
          <div class="relative flex justify-between">
            <div
              v-for="(status, index) in statusFlow"
              :key="status"
              class="flex flex-col items-center"
            >
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center mb-2 transition-all',
                  index <= currentStatusIndex
                    ? 'bg-primary-500 text-white'
                    : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-400'
                ]"
              >
                <component
                  :is="statusConfigs[status].icon"
                  class="w-5 h-5"
                />
              </div>
              <p
                :class="[
                  'text-xs font-medium text-center max-w-[80px]',
                  index <= currentStatusIndex
                    ? 'text-neutral-900 dark:text-neutral-50'
                    : 'text-neutral-400'
                ]"
              >
                {{ statusConfigs[status].label }}
              </p>
            </div>
          </div>
        </div>

        <!-- Current Status Info -->
        <div class="mt-6 p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
          <div class="flex items-start gap-3">
            <component
              :is="statusConfigs[currentNC.status].icon"
              :class="['w-6 h-6', statusConfigs[currentNC.status].color]"
            />
            <div class="flex-1">
              <h3 class="font-semibold text-neutral-900 dark:text-neutral-50">
                {{ statusConfigs[currentNC.status].label }}
              </h3>
              <p class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ statusConfigs[currentNC.status].description }}
              </p>
            </div>
          </div>
        </div>

        <!-- Next Actions -->
        <div
          v-if="statusConfigs[currentNC.status].nextActions.length > 0"
          class="mt-4 flex gap-2"
        >
          <button
            v-for="action in statusConfigs[currentNC.status].nextActions"
            :key="action.status"
            class="btn-primary inline-flex items-center gap-2"
            @click="openStatusDialog(action.status)"
          >
            <component :is="statusConfigs[action.status].icon" class="w-4 h-4" />
            {{ action.label }}
          </button>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Detection Info -->
          <div class="card p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
              Informations de détection
            </h2>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex items-start gap-3">
                <MapPin class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-sm text-neutral-500 dark:text-neutral-400">Site</p>
                  <p class="font-medium text-neutral-900 dark:text-neutral-50">
                    {{ currentNC.site?.name }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <User class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-sm text-neutral-500 dark:text-neutral-400">Détecté par</p>
                  <p class="font-medium text-neutral-900 dark:text-neutral-50">
                    {{ currentNC.detected_by }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Calendar class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-sm text-neutral-500 dark:text-neutral-400">Date de détection</p>
                  <p class="font-medium text-neutral-900 dark:text-neutral-50">
                    {{ formatDate(currentNC.detected_date) }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <FileText class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-sm text-neutral-500 dark:text-neutral-400">Source</p>
                  <p class="font-medium text-neutral-900 dark:text-neutral-50">
                    {{ sourceLabels[currentNC.source] }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Severity -->
            <div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-700">
              <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Sévérité</p>
              <div class="flex items-center gap-3">
                <div class="flex gap-1">
                  <div
                    v-for="i in 5"
                    :key="i"
                    :class="[
                      'w-3 h-6 rounded-sm',
                      i <= currentNC.severity ? 'bg-red-500' : 'bg-neutral-200 dark:bg-neutral-700'
                    ]"
                  />
                </div>
                <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">
                  {{ currentNC.severity }}/5
                </span>
              </div>
            </div>
          </div>

          <!-- Analysis & Actions -->
          <div class="card p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
              Analyse et Actions
            </h2>

            <div class="space-y-6">
              <!-- Root Cause -->
              <div>
                <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Cause racine
                </h3>
                <div class="p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
                  <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    {{ currentNC.root_cause || 'Non renseignée' }}
                  </p>
                </div>
              </div>

              <!-- Immediate Action -->
              <div>
                <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Action immédiate
                </h3>
                <div class="p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
                  <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    {{ currentNC.immediate_action || 'Non renseignée' }}
                  </p>
                </div>
              </div>

              <!-- Corrective Action -->
              <div>
                <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Action corrective
                </h3>
                <div class="p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
                  <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    {{ currentNC.corrective_action || 'Non renseignée' }}
                  </p>
                </div>
              </div>

              <!-- Preventive Action -->
              <div>
                <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                  Action préventive
                </h3>
                <div class="p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
                  <p class="text-sm text-neutral-600 dark:text-neutral-400">
                    {{ currentNC.preventive_action || 'Non renseignée' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Metadata & Actions -->
        <div class="space-y-6">
          <!-- Responsible -->
          <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">
                Responsable
              </h3>
              <button
                v-if="canEdit"
                class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                @click="openAssignDialog"
              >
                Modifier
              </button>
            </div>

            <div v-if="currentNC.responsible" class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                <User class="w-5 h-5 text-primary-600 dark:text-primary-400" />
              </div>
              <div>
                <p class="font-medium text-neutral-900 dark:text-neutral-50">
                  {{ currentNC.responsible.name }}
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                  {{ currentNC.responsible.email }}
                </p>
              </div>
            </div>
            <p v-else class="text-sm text-neutral-500">
              Non assigné
            </p>
          </div>

          <!-- Dates -->
          <div class="card p-6">
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
              Dates
            </h3>

            <div class="space-y-3">
              <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Créée le</p>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-50">
                  {{ formatDateTime(currentNC.created_at) }}
                </p>
              </div>

              <div v-if="currentNC.due_date">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Échéance</p>
                <p
                  :class="[
                    'text-sm font-medium',
                    isOverdue
                      ? 'text-red-600 dark:text-red-400'
                      : 'text-neutral-900 dark:text-neutral-50'
                  ]"
                >
                  {{ formatDate(currentNC.due_date) }}
                </p>
              </div>

              <div v-if="currentNC.closure_date">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Clôturée le</p>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-50">
                  {{ formatDateTime(currentNC.closure_date) }}
                </p>
              </div>

              <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Dernière mise à jour</p>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-50">
                  {{ formatDateTime(currentNC.updated_at) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Related Documents Section -->
          <div class="card p-6">
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
              Documents liés
            </h3>
            <DocumentFlowButton
              :generate-draft-fn="ncGenerateDraftFn"
              download-filename="rapport-nc.pdf"
              :workflow-status="currentNC?.report_document_workflow_status ?? null"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Assign Responsible Dialog -->
    <div
      v-if="showAssignDialog"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showAssignDialog = false"
    >
      <div class="card p-6 max-w-md w-full">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
          Assigner un responsable
        </h2>

        <div class="mb-6">
          <label class="label">Sélectionner un utilisateur</label>
          <select v-model.number="selectedResponsible" class="input w-full">
            <option disabled value="0">Choisir un responsable</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} - {{ user.email }}
            </option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button
            class="btn-secondary"
            @click="showAssignDialog = false"
          >
            Annuler
          </button>
          <button
            class="btn-primary"
            :disabled="!selectedResponsible || loading"
            @click="handleAssign"
          >
            Assigner
          </button>
        </div>
      </div>
    </div>

    <!-- Update Status Dialog -->
    <div
      v-if="showStatusDialog"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showStatusDialog = false"
    >
      <div class="card p-6 max-w-md w-full">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
          Changer le statut
        </h2>

        <div class="mb-4 p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-lg">
          <div class="flex items-center gap-3">
            <component
              :is="statusConfigs[selectedStatus].icon"
              :class="['w-6 h-6', statusConfigs[selectedStatus].color]"
            />
            <div>
              <p class="font-medium text-neutral-900 dark:text-neutral-50">
                {{ statusConfigs[selectedStatus].label }}
              </p>
              <p class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ statusConfigs[selectedStatus].description }}
              </p>
            </div>
          </div>
        </div>

        <div class="mb-6">
          <label class="label">Commentaires (optionnel)</label>
          <textarea
            v-model="statusComments"
            class="input w-full"
            placeholder="Ajouter des commentaires sur ce changement de statut..."
            rows="4"
          />
        </div>

        <div class="flex items-center justify-end gap-3">
          <button
            class="btn-secondary"
            @click="showStatusDialog = false"
          >
            Annuler
          </button>
          <button
            class="btn-primary"
            :disabled="loading"
            @click="handleStatusUpdate"
          >
            Confirmer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
