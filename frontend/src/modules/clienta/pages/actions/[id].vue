/** * Action Details Page * View and manage a specific corrective/preventive
action */

<script setup lang="ts">
  import type {
    ActionStatus,
    UpdateActionDTO,
  } from '@/api/services/actions.service'
  import {
    Activity,
    ArrowLeft,
    Calendar,
    CheckCircle,
    CheckSquare,
    DollarSign,
    Edit,
    FileText,
    ListTodo,
    MapPin,
    Play,
    Save,
    Star,
    TrendingUp,
    User,
    X,
  } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useActions } from '@/modules/clienta/composables/useActions'
  import { useUsers } from '@/modules/clienta/composables/useUsers'

  const router = useRouter()
  const route = useRoute()
  const {
    currentAction,
    loading,
    error,
    fetchAction,
    updateAction,
    updateProgress,
    updateStatus,
    verifyEffectiveness,
    deleteAction,
  } = useActions()

  const { fetchUsers } = useUsers()

  const isEditing = ref(false)
  const showProgressDialog = ref(false)
  const showStatusDialog = ref(false)
  const showEffectivenessDialog = ref(false)
  const progressValue = ref(0)
  const progressComments = ref('')
  const selectedStatus = ref<ActionStatus>('draft')
  const statusComments = ref('')
  const effectivenessRating = ref(3)
  const effectivenessComments = ref('')

  const editForm = ref<UpdateActionDTO>({})

  const statusConfigs: Record<
    ActionStatus,
    {
      color: string
      icon: any
      label: string
      description: string
    }
  > = {
    draft: {
      color: 'text-gray-600 bg-gray-100 dark:bg-gray-900/30',
      icon: Edit,
      label: 'Brouillon',
      description: 'Action en cours de rédaction',
    },
    assigned: {
      color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
      icon: ListTodo,
      label: 'Assignée',
      description: 'Action assignée à un responsable',
    },
    in_progress: {
      color: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30',
      icon: Play,
      label: 'En cours',
      description: 'Action en cours d\'exécution',
    },
    completed: {
      color: 'text-green-600 bg-green-100 dark:bg-green-900/30',
      icon: CheckCircle,
      label: 'Terminé',
      description: 'Action terminée, en attente de vérification',
    },
    verified: {
      color: 'text-teal-600 bg-teal-100 dark:bg-teal-900/30',
      icon: CheckSquare,
      label: 'Vérifiée',
      description: 'Efficacité vérifiée',
    },
    closed: {
      color: 'text-green-700 bg-green-200 dark:bg-green-800/30',
      icon: CheckCircle,
      label: 'Fermée',
      description: 'Action fermée',
    },
    cancelled: {
      color: 'text-red-600 bg-red-100 dark:bg-red-900/30',
      icon: X,
      label: 'Annulée',
      description: 'Action annulée',
    },
  }

  const priorityConfigs = {
    low: {
      color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
      label: 'Basse',
    },
    medium: {
      color: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30',
      label: 'Moyenne',
    },
    high: {
      color: 'text-orange-600 bg-orange-100 dark:bg-orange-900/30',
      label: 'Haute',
    },
    urgent: {
      color: 'text-red-600 bg-red-100 dark:bg-red-900/30',
      label: 'Urgente',
    },
  }

  const typeLabels = {
    corrective: 'Corrective',
    preventive: 'Préventive',
  }

  const categoryLabels = {
    qualite: 'Qualité',
    hygiene: 'Hygiène',
    securite: 'Sécurité',
    environnement: 'Environnement',
  }

  const sourceTypeLabels = {
    nc: 'Non-Conformité',
    audit: 'Audit',
    risk: 'Risque',
  }

  const isOverdue = computed(() => {
    if (
      !currentAction.value
      || currentAction.value.status === 'completed'
      || currentAction.value.status === 'verified'
      || currentAction.value.status === 'closed'
      || currentAction.value.status === 'cancelled'
      || !currentAction.value.due_date
    ) {
      return false
    }
    return new Date(currentAction.value.due_date) < new Date()
  })

  const canEdit = computed(() => {
    return (
      currentAction.value?.status !== 'closed'
      && currentAction.value?.status !== 'cancelled'
    )
  })

  const canUpdateProgress = computed(() => {
    return currentAction.value?.status === 'in_progress'
  })

  const canVerify = computed(() => {
    return currentAction.value?.status === 'completed'
  })

  function goBack () {
    router.push('/company/actions')
  }

  async function loadData () {
    const id = Number.parseInt((route.params as { id: string }).id as string)
    if (Number.isNaN(id)) {
      router.push('/company/actions')
      return
    }

    await Promise.all([fetchAction(id), fetchUsers({ per_page: 100 })])
  }

  function startEdit () {
    if (!currentAction.value) return
    editForm.value = {
      title: currentAction.value.title,
      description: currentAction.value.description,
      type: currentAction.value.type,
      category: currentAction.value.category,
      priority: currentAction.value.priority,
      responsible_id: currentAction.value.responsible_id,
      site_id: currentAction.value.site_id,
      due_date: currentAction.value.due_date,
      resources_needed: currentAction.value.resources_needed,
      estimated_cost: currentAction.value.estimated_cost,
      actual_cost: currentAction.value.actual_cost,
      effectiveness_criteria: currentAction.value.effectiveness_criteria,
    }
    isEditing.value = true
  }

  function cancelEdit () {
    isEditing.value = false
    editForm.value = {}
  }

  async function saveEdit () {
    if (!currentAction.value) return

    try {
      await updateAction(currentAction.value.id, editForm.value)
      isEditing.value = false
    } catch (error_) {
      console.error('Failed to update action:', error_)
    }
  }

  async function handleUpdateProgress () {
    if (!currentAction.value) return

    try {
      await updateProgress(currentAction.value.id, {
        progress: progressValue.value,
        comments: progressComments.value,
      })
      showProgressDialog.value = false
      progressComments.value = ''
    } catch (error_) {
      console.error('Failed to update progress:', error_)
    }
  }

  async function handleUpdateStatus () {
    if (!currentAction.value) return

    try {
      await updateStatus(currentAction.value.id, {
        status: selectedStatus.value,
        comments: statusComments.value,
      })
      showStatusDialog.value = false
      statusComments.value = ''
    } catch (error_) {
      console.error('Failed to update status:', error_)
    }
  }

  async function handleVerifyEffectiveness () {
    if (!currentAction.value) return

    try {
      await verifyEffectiveness(currentAction.value.id, {
        effectiveness_rating: effectivenessRating.value,
        comments: effectivenessComments.value,
      })
      showEffectivenessDialog.value = false
      effectivenessComments.value = ''
    } catch (error_) {
      console.error('Failed to verify effectiveness:', error_)
    }
  }

  function openProgressDialog () {
    if (!currentAction.value) return
    progressValue.value = currentAction.value.progress
    showProgressDialog.value = true
  }

  function openStatusDialog () {
    if (!currentAction.value) return
    selectedStatus.value = currentAction.value.status
    showStatusDialog.value = true
  }

  function openEffectivenessDialog () {
    showEffectivenessDialog.value = true
  }

  async function handleDelete () {
    if (!currentAction.value) return

    if (
      confirm(
        'Êtes-vous sûr de vouloir supprimer cette action ? Cette action est irréversible.',
      )
    ) {
      try {
        await deleteAction(currentAction.value.id)
        router.push('/company/actions')
      } catch (error_) {
        console.error('Failed to delete action:', error_)
      }
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
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Loading State -->
    <div v-if="loading && !currentAction" class="text-center py-12">
      <div
        class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      />
      <p class="mt-4 text-neutral-600 dark:text-neutral-400">Chargement...</p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="card p-6 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20"
    >
      <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>

    <!-- Content -->
    <div v-else-if="currentAction">
      <!-- Header -->
      <div class="mb-6">
        <button
          class="inline-flex items-center gap-2 text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50 mb-4"
          @click="goBack"
        >
          <ArrowLeft class="w-4 h-4" />
          Retour à la liste
        </button>

        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1
                class="text-2xl font-bold text-neutral-900 dark:text-neutral-50 flex items-center gap-2"
              >
                <CheckSquare class="w-7 h-7" />
                {{ currentAction.reference }}
              </h1>
              <span
                :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium',
                  statusConfigs[currentAction.status].color,
                ]"
              >
                <component
                  :is="statusConfigs[currentAction.status].icon"
                  class="w-4 h-4"
                />
                {{ statusConfigs[currentAction.status].label }}
              </span>
            </div>
            <p class="text-lg text-neutral-700 dark:text-neutral-300">
              {{ currentAction.title }}
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
            <button v-if="canEdit" class="btn-danger" @click="handleDelete">
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <button
          v-if="canUpdateProgress"
          class="card p-4 hover:shadow-md transition-shadow text-left"
          @click="openProgressDialog"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center"
            >
              <TrendingUp class="w-5 h-5 text-yellow-600" />
            </div>
            <div>
              <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Mettre à jour
              </p>
              <p class="font-semibold text-neutral-900 dark:text-neutral-50">
                Progression
              </p>
            </div>
          </div>
        </button>

        <button
          v-if="canEdit"
          class="card p-4 hover:shadow-md transition-shadow text-left"
          @click="openStatusDialog"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center"
            >
              <Activity class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Changer
              </p>
              <p class="font-semibold text-neutral-900 dark:text-neutral-50">
                Statut
              </p>
            </div>
          </div>
        </button>

        <button
          v-if="canVerify"
          class="card p-4 hover:shadow-md transition-shadow text-left"
          @click="openEffectivenessDialog"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center"
            >
              <Star class="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Vérifier
              </p>
              <p class="font-semibold text-neutral-900 dark:text-neutral-50">
                Efficacité
              </p>
            </div>
          </div>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Details Card -->
          <div class="card p-6">
            <h2
              class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
            >
              Détails de l'action
            </h2>

            <div v-if="!isEditing" class="space-y-4">
              <!-- Description -->
              <div>
                <label class="label">Description</label>
                <p
                  class="text-neutral-700 dark:text-neutral-300 whitespace-pre-wrap"
                >
                  {{ currentAction.description }}
                </p>
              </div>

              <!-- Type, Category, Priority -->
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="label">Type</label>
                  <span
                    class="inline-block px-3 py-1 rounded-full text-sm bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-50"
                  >
                    {{ typeLabels[currentAction.type] }}
                  </span>
                </div>
                <div>
                  <label class="label">Catégorie</label>
                  <span
                    class="inline-block px-3 py-1 rounded-full text-sm bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-50"
                  >
                    {{ categoryLabels[currentAction.category] }}
                  </span>
                </div>
                <div>
                  <label class="label">Priorité</label>
                  <span
                    :class="[
                      'inline-block px-3 py-1 rounded-full text-sm',
                      priorityConfigs[currentAction.priority].color,
                    ]"
                  >
                    {{ priorityConfigs[currentAction.priority].label }}
                  </span>
                </div>
              </div>

              <!-- Resources Needed -->
              <div v-if="currentAction.resources_needed">
                <label class="label">Ressources nécessaires</label>
                <p
                  class="text-neutral-700 dark:text-neutral-300 whitespace-pre-wrap"
                >
                  {{ currentAction.resources_needed }}
                </p>
              </div>

              <!-- Costs -->
              <div class="grid grid-cols-2 gap-4">
                <div v-if="currentAction.estimated_cost">
                  <label class="label">Coût estimé</label>
                  <p
                    class="text-neutral-900 dark:text-neutral-50 font-medium flex items-center gap-1"
                  >
                    <DollarSign class="w-4 h-4" />
                    {{ currentAction.estimated_cost.toFixed(2) }} €
                  </p>
                </div>
                <div v-if="currentAction.actual_cost">
                  <label class="label">Coût réel</label>
                  <p
                    class="text-neutral-900 dark:text-neutral-50 font-medium flex items-center gap-1"
                  >
                    <DollarSign class="w-4 h-4" />
                    {{ currentAction.actual_cost.toFixed(2) }} €
                  </p>
                </div>
              </div>

              <!-- Effectiveness Criteria -->
              <div v-if="currentAction.effectiveness_criteria">
                <label class="label">Critères d'efficacité</label>
                <p
                  class="text-neutral-700 dark:text-neutral-300 whitespace-pre-wrap"
                >
                  {{ currentAction.effectiveness_criteria }}
                </p>
              </div>
            </div>

            <!-- Edit Form -->
            <div v-else class="space-y-4">
              <div>
                <label class="label">Titre</label>
                <input
                  v-model="editForm.title"
                  class="input w-full"
                  type="text"
                >
              </div>
              <div>
                <label class="label">Description</label>
                <textarea
                  v-model="editForm.description"
                  class="input w-full"
                  rows="4"
                />
              </div>
              <div>
                <label class="label">Ressources nécessaires</label>
                <textarea
                  v-model="editForm.resources_needed"
                  class="input w-full"
                  rows="3"
                />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="label">Coût estimé (€)</label>
                  <input
                    v-model.number="editForm.estimated_cost"
                    class="input w-full"
                    step="0.01"
                    type="number"
                  >
                </div>
                <div>
                  <label class="label">Coût réel (€)</label>
                  <input
                    v-model.number="editForm.actual_cost"
                    class="input w-full"
                    step="0.01"
                    type="number"
                  >
                </div>
              </div>
              <div>
                <label class="label">Critères d'efficacité</label>
                <textarea
                  v-model="editForm.effectiveness_criteria"
                  class="input w-full"
                  rows="3"
                />
              </div>

              <div class="flex gap-2">
                <button
                  class="btn-primary inline-flex items-center gap-2"
                  @click="saveEdit"
                >
                  <Save class="w-4 h-4" />
                  Enregistrer
                </button>
                <button class="btn-secondary" @click="cancelEdit">
                  Annuler
                </button>
              </div>
            </div>
          </div>

          <!-- Progress Card -->
          <div class="card p-6">
            <h2
              class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
            >
              Progression
            </h2>

            <div class="space-y-4">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm text-neutral-600 dark:text-neutral-400">Avancement</span>
                  <span
                    class="text-lg font-bold text-neutral-900 dark:text-neutral-50"
                  >{{ currentAction.progress }}%</span>
                </div>
                <div
                  class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-4"
                >
                  <div
                    class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all flex items-center justify-end pr-2"
                    :style="{ width: `${currentAction.progress}%` }"
                  >
                    <span
                      v-if="currentAction.progress > 10"
                      class="text-xs text-white font-medium"
                    >
                      {{ currentAction.progress }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Effectiveness Verification -->
          <div v-if="currentAction.effectiveness_rating" class="card p-6">
            <h2
              class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
            >
              Vérification de l'efficacité
            </h2>

            <div class="flex items-center gap-2">
              <Star
                v-for="n in 5"
                :key="n"
                :class="[
                  'w-6 h-6',
                  n <= currentAction.effectiveness_rating
                    ? 'text-yellow-500 fill-yellow-500'
                    : 'text-neutral-300 dark:text-neutral-600',
                ]"
              />
              <span class="ml-2 text-neutral-600 dark:text-neutral-400">
                {{ currentAction.effectiveness_rating }}/5
              </span>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Info Card -->
          <div class="card p-6">
            <h2
              class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
            >
              Informations
            </h2>

            <div class="space-y-4">
              <!-- Responsible -->
              <div class="flex items-start gap-3">
                <User class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Responsable
                  </p>
                  <p
                    class="text-sm font-medium text-neutral-900 dark:text-neutral-50"
                  >
                    {{ currentAction.responsible?.name || "Non assigné" }}
                  </p>
                </div>
              </div>

              <!-- Site -->
              <div class="flex items-start gap-3">
                <MapPin class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Site
                  </p>
                  <p
                    class="text-sm font-medium text-neutral-900 dark:text-neutral-50"
                  >
                    {{ currentAction.site?.name }}
                  </p>
                </div>
              </div>

              <!-- Due Date -->
              <div v-if="currentAction.due_date" class="flex items-start gap-3">
                <Calendar class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Échéance
                  </p>
                  <p
                    :class="[
                      'text-sm font-medium',
                      isOverdue
                        ? 'text-red-600'
                        : 'text-neutral-900 dark:text-neutral-50',
                    ]"
                  >
                    {{ formatDate(currentAction.due_date) }}
                    <span v-if="isOverdue" class="text-xs">(En retard)</span>
                  </p>
                </div>
              </div>

              <!-- Completion Date -->
              <div
                v-if="currentAction.completion_date"
                class="flex items-start gap-3"
              >
                <CheckCircle class="w-5 h-5 text-green-600 mt-0.5" />
                <div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Date de réalisation
                  </p>
                  <p
                    class="text-sm font-medium text-neutral-900 dark:text-neutral-50"
                  >
                    {{ formatDate(currentAction.completion_date) }}
                  </p>
                </div>
              </div>

              <!-- Source Link -->
              <div
                v-if="
                  currentAction.source_type && (currentAction as any).source
                "
                class="flex items-start gap-3"
              >
                <FileText class="w-5 h-5 text-neutral-400 mt-0.5" />
                <div>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Source
                  </p>
                  <p
                    class="text-sm font-medium text-primary-600 dark:text-primary-400"
                  >
                    {{ sourceTypeLabels[currentAction.source_type] }}
                  </p>
                  <p class="text-xs text-neutral-600 dark:text-neutral-400">
                    {{ (currentAction as any).source.reference }} -
                    {{ (currentAction as any).source.title }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Timeline Card -->
          <div class="card p-6">
            <h2
              class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
            >
              Historique
            </h2>

            <div class="space-y-3">
              <div class="flex gap-3">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2" />
                <div class="flex-1">
                  <p
                    class="text-sm font-medium text-neutral-900 dark:text-neutral-50"
                  >
                    Action créée
                  </p>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ formatDateTime(currentAction.created_at) }}
                  </p>
                </div>
              </div>

              <div class="flex gap-3">
                <div class="w-2 h-2 bg-green-500 rounded-full mt-2" />
                <div class="flex-1">
                  <p
                    class="text-sm font-medium text-neutral-900 dark:text-neutral-50"
                  >
                    Dernière mise à jour
                  </p>
                  <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ formatDateTime(currentAction.updated_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Update Progress Dialog -->
    <div
      v-if="showProgressDialog"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showProgressDialog = false"
    >
      <div class="card p-6 max-w-md w-full">
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Mettre à jour la progression
        </h3>

        <div class="space-y-4">
          <div>
            <label class="label">Progression (%)</label>
            <input
              v-model.number="progressValue"
              class="w-full"
              max="100"
              min="0"
              type="range"
            >
            <div class="text-center text-2xl font-bold text-primary-600 mt-2">
              {{ progressValue }}%
            </div>
          </div>

          <div>
            <label class="label">Commentaires</label>
            <textarea
              v-model="progressComments"
              class="input w-full"
              placeholder="Décrivez l'avancement..."
              rows="3"
            />
          </div>

          <div class="flex gap-2 justify-end">
            <button class="btn-secondary" @click="showProgressDialog = false">
              Annuler
            </button>
            <button class="btn-primary" @click="handleUpdateProgress">
              Enregistrer
            </button>
          </div>
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
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Changer le statut
        </h3>

        <div class="space-y-4">
          <div>
            <label class="label">Nouveau statut</label>
            <select v-model="selectedStatus" class="input w-full">
              <option
                v-for="(config, status) in statusConfigs"
                :key="status"
                :value="status"
              >
                {{ config.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="label">Commentaires</label>
            <textarea
              v-model="statusComments"
              class="input w-full"
              placeholder="Ajoutez un commentaire..."
              rows="3"
            />
          </div>

          <div class="flex gap-2 justify-end">
            <button class="btn-secondary" @click="showStatusDialog = false">
              Annuler
            </button>
            <button class="btn-primary" @click="handleUpdateStatus">
              Enregistrer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Verify Effectiveness Dialog -->
    <div
      v-if="showEffectivenessDialog"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showEffectivenessDialog = false"
    >
      <div class="card p-6 max-w-md w-full">
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Vérifier l'efficacité
        </h3>

        <div class="space-y-4">
          <div>
            <label class="label">Note d'efficacité</label>
            <div class="flex items-center gap-2 justify-center py-4">
              <button
                v-for="n in 5"
                :key="n"
                type="button"
                @click="effectivenessRating = n"
              >
                <Star
                  :class="[
                    'w-10 h-10 transition-colors',
                    n <= effectivenessRating
                      ? 'text-yellow-500 fill-yellow-500'
                      : 'text-neutral-300 dark:text-neutral-600 hover:text-yellow-400',
                  ]"
                />
              </button>
            </div>
            <p
              class="text-center text-neutral-600 dark:text-neutral-400 text-sm"
            >
              {{ effectivenessRating }}/5
            </p>
          </div>

          <div>
            <label class="label">Commentaires</label>
            <textarea
              v-model="effectivenessComments"
              class="input w-full"
              placeholder="Évaluez l'efficacité de l'action..."
              rows="3"
            />
          </div>

          <div class="flex gap-2 justify-end">
            <button
              class="btn-secondary"
              @click="showEffectivenessDialog = false"
            >
              Annuler
            </button>
            <button class="btn-primary" @click="handleVerifyEffectiveness">
              Valider
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
