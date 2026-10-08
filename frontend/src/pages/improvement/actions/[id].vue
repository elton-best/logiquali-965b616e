<template>
  <div class="action-detail-page p-6">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="text-center">
        <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4" />
        <p class="text-gray-600">Chargement...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6">
      <h2 class="text-lg font-semibold text-red-800 mb-2">Erreur</h2>
      <p class="text-red-600">{{ error }}</p>
      <button class="mt-4 text-red-600 hover:underline" @click="router.push('/improvement/actions')">
        ← Retour à la liste
      </button>
    </div>

    <!-- Action Details -->
    <div v-else-if="action">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg"
            @click="router.push('/improvement/actions')"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ action.title }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ action.ref }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <StatusBadge module="action" :status="action.status" />
          <ActionPriorityBadge :priority="action.priority" />
          <ActionTypeBadge :type="action.type" />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3 mb-6">
        <button
          v-if="action.status === 'pending'"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          @click="startAction"
        >
          Démarrer l'action
        </button>
        <button
          v-if="action.status === 'in_progress'"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          @click="showProgressModal = true"
        >
          Mettre à jour la progression
        </button>
        <button
          v-if="action.status === 'in_progress' && action.progress >= 100"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
          @click="completeAction"
        >
          Marquer comme terminée
        </button>
        <button
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          @click="editMode = !editMode"
        >
          {{ editMode ? 'Annuler' : 'Modifier' }}
        </button>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description Card -->
          <BaseCard>
            <h2 class="text-lg font-semibold mb-3">Description</h2>
            <p v-if="!editMode" class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ action.description || 'Aucune description' }}
            </p>
            <textarea
              v-else
              v-model="editedAction.description"
              class="w-full px-3 py-2 border rounded-lg"
              rows="4"
            />
          </BaseCard>

          <!-- Progress Card -->
          <BaseCard v-if="action.status !== 'pending'">
            <h2 class="text-lg font-semibold mb-3">Progression</h2>
            <div class="mb-2 flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ action.progress || 0 }}%</span>
              <span class="text-gray-600">
                {{ action.status === 'completed' ? 'Terminé' : 'En cours' }}
              </span>
            </div>
            <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div
                :class="[
                  'h-full transition-all duration-500',
                  action.progress >= 100 ? 'bg-green-500' : 'bg-blue-500'
                ]"
                :style="{ width: `${Math.min(action.progress || 0, 100)}%` }"
              />
            </div>
          </BaseCard>

          <!-- Analysis Card (if exists) -->
          <BaseCard v-if="action.root_cause_analysis">
            <h2 class="text-lg font-semibold mb-3">Analyse de cause racine</h2>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ action.root_cause_analysis }}
            </p>
          </BaseCard>

          <!-- Expected Results Card -->
          <BaseCard v-if="action.expected_results">
            <h2 class="text-lg font-semibold mb-3">Résultats attendus</h2>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ action.expected_results }}
            </p>
          </BaseCard>

          <!-- Comments/Activity Log -->
          <BaseCard>
            <h2 class="text-lg font-semibold mb-3">Historique</h2>
            <div class="space-y-3">
              <div v-for="log in activityLogs" :key="log.id" class="flex gap-3 pb-3 border-b last:border-0">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-xs font-medium text-blue-600">{{ log.user?.initials }}</span>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium">{{ log.description }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(log.created_at) }}</p>
                </div>
              </div>
              <p v-if="activityLogs.length === 0" class="text-sm text-gray-500 text-center py-4">
                Aucun historique
              </p>
            </div>
          </BaseCard>
        </div>

        <!-- Right Column - Metadata -->
        <div class="space-y-6">
          <!-- Info Card -->
          <BaseCard>
            <h2 class="text-lg font-semibold mb-4">Informations</h2>
            <dl class="space-y-3">
              <div>
                <dt class="text-sm text-gray-600 dark:text-gray-400">Responsable</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                  {{ action.responsible?.name || 'Non assigné' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-600 dark:text-gray-400">Date limite</dt>
                <dd :class="['text-sm font-medium mt-1', isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-white']">
                  {{ formatDate(action.deadline) }}
                  <span v-if="isOverdue" class="text-xs">(En retard)</span>
                </dd>
              </div>
              <div v-if="action.completed_at">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Terminée le</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                  {{ formatDate(action.completed_at) }}
                </dd>
              </div>
              <div v-if="action.processus">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Processus lié</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                  {{ action.processus.name }}
                </dd>
              </div>
              <div v-if="action.non_conformity">
                <dt class="text-sm text-gray-600 dark:text-gray-400">NC liée</dt>
                <dd class="text-sm">
                  <router-link
                    class="text-blue-600 hover:underline"
                    :to="`/improvement/non-conformities/${action.non_conformity_id}`"
                  >
                    {{ action.non_conformity.ref }}
                  </router-link>
                </dd>
              </div>
            </dl>
          </BaseCard>

          <!-- Verification Card -->
          <BaseCard v-if="action.verification_method">
            <h2 class="text-lg font-semibold mb-3">Vérification</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ action.verification_method }}</p>
            <div v-if="action.verified_at" class="mt-3 pt-3 border-t">
              <p class="text-xs text-gray-600">
                Vérifié le {{ formatDate(action.verified_at) }}
                <span v-if="action.verified_by">par {{ action.verified_by.name }}</span>
              </p>
            </div>
          </BaseCard>

          <!-- Save/Cancel Buttons (Edit Mode) -->
          <div v-if="editMode" class="flex flex-col gap-2">
            <button
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              @click="saveChanges"
            >
              Enregistrer
            </button>
            <button
              class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              @click="cancelEdit"
            >
              Annuler
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Modal -->
    <ActionProgressModal
      v-if="showProgressModal && currentAction"
      v-model="showProgressModal"
      :action="currentAction"
      @submit="handleProgressUpdate"
    />
  </div>
</template>

<script setup lang="ts">
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import ActionPriorityBadge from '@/components/actions/ActionPriorityBadge.vue'
  import ActionProgressModal from '@/components/actions/ActionProgressModal.vue'
  import ActionTypeBadge from '@/components/actions/ActionTypeBadge.vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import { useActionStore } from '@/stores/actionStore'

  const router = useRouter()
  const route = useRoute()
  const actionStore = useActionStore()

  const { currentAction, loading, error } = storeToRefs(actionStore)
  const action = computed<any>(() => currentAction.value as any)

  const actionId = computed(() => Number((route.params as any).id ?? 0))
  const editMode = ref(false)
  const editedAction = ref<any>({})
  const showProgressModal = ref(false)
  const activityLogs = ref<any[]>([])

  const isOverdue = computed(() => {
    if (!action.value || action.value.status === 'completed') return false
    return new Date(action.value.deadline) < new Date()
  })

  async function loadAction () {
    await actionStore.fetchActionById(actionId.value)
    if (action.value) {
      editedAction.value = { ...action.value }
    }
  }

  async function startAction () {
    if (action.value) {
      await actionStore.updateAction(action.value.id, { status: 'in_progress' } as any)
      await loadAction()
    }
  }

  async function completeAction () {
    if (action.value) {
      await actionStore.updateAction(action.value.id, {
        status: 'completed',
        progress: 100,
      } as any)
      await loadAction()
    }
  }

  async function handleProgressUpdate (payload: { progress: number, notes?: string }) {
    if (!action.value) return
    await actionStore.updateProgress(action.value.id, payload)
    showProgressModal.value = false
    await loadAction()
  }

  async function saveChanges () {
    if (action.value) {
      await actionStore.updateAction(action.value.id, editedAction.value)
      editMode.value = false
      await loadAction()
    }
  }

  function cancelEdit () {
    editMode.value = false
    if (action.value) {
      editedAction.value = { ...action.value }
    }
  }

  function formatDate (date: string | null | undefined): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  }

  onMounted(() => {
    loadAction()
  })
</script>
