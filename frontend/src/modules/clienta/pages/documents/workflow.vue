<template>
  <div class="workflow-dashboard-page">
    <div class="page-header mb-6">
      <h1 class="text-2xl font-bold">Tableau de bord Workflow</h1>
      <p class="text-gray-600 mt-2">
        Gérez les documents en attente de vérification et de validation
      </p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div v-if="canVerifyDocuments" class="stat-card bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-blue-600 font-medium">brouillon en cours de vérification</p>
            <p class="text-3xl font-bold text-blue-700 mt-1">
              {{ pendingDocuments.pending_verification?.length || 0 }}
            </p>
          </div>
          <i class="fas fa-clipboard-check text-4xl text-blue-300" />
        </div>
      </div>

      <div v-if="canApproveDocuments" class="stat-card bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-orange-600 font-medium">brouillon - en attente de validation</p>
            <p class="text-3xl font-bold text-orange-700 mt-1">
              {{ pendingDocuments.pending_approval?.length || 0 }}
            </p>
          </div>
          <i class="fas fa-check-double text-4xl text-orange-300" />
        </div>
      </div>

      <div v-if="canVerifyDocuments" class="stat-card bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-green-600 font-medium">Vérifiés (30j)</p>
            <p class="text-3xl font-bold text-green-700 mt-1">{{ stats.verified }}</p>
          </div>
          <i class="fas fa-check-circle text-4xl text-green-300" />
        </div>
      </div>

      <div v-if="canApproveDocuments" class="stat-card bg-purple-50 border border-purple-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-purple-600 font-medium">validé - version 1 (30j)</p>
            <p class="text-3xl font-bold text-purple-700 mt-1">{{ stats.approved }}</p>
          </div>
          <i class="fas fa-award text-4xl text-purple-300" />
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="hasWorkflowPermissions" class="actions mb-6 flex justify-between items-center">
      <div class="tabs">
        <button
          v-if="canVerifyDocuments"
          class="tab-button"
          :class="{ active: activeTab === 'verification' }"
          @click="activeTab = 'verification'"
        >
          <i class="fas fa-clipboard-check mr-2" />
          À vérifier ({{ pendingDocuments.pending_verification?.length || 0 }})
        </button>
        <button
          v-if="canApproveDocuments"
          class="tab-button"
          :class="{ active: activeTab === 'approval' }"
          @click="activeTab = 'approval'"
        >
          <i class="fas fa-check-double mr-2" />
          À valider ({{ pendingDocuments.pending_approval?.length || 0 }})
        </button>
      </div>

      <button class="btn btn-secondary" :disabled="loading" @click="loadData">
        <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }" />
        Actualiser
      </button>
    </div>

    <div v-else class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-amber-800">
      Action non autorisée. Vous n'avez pas les permissions nécessaires.
    </div>

    <!-- Documents List -->
    <div class="documents-container bg-white rounded-lg shadow-md">
      <div v-if="loading && currentDocuments.length === 0" class="text-center py-12">
        <div class="spinner mx-auto mb-3" />
        <p class="text-gray-600">Chargement des documents...</p>
      </div>

      <div v-else-if="currentDocuments.length === 0" class="text-center py-12">
        <i class="fas fa-inbox text-6xl text-gray-400 mb-4" />
        <p class="text-gray-600 text-lg">Aucun document en attente</p>
        <p class="text-gray-500 text-sm mt-2">
          {{ activeTab === 'verification' ? 'Aucun document à vérifier' : 'Aucun document à valider' }}
        </p>
      </div>

      <div v-else class="p-6">
        <div class="documents-grid grid grid-cols-1 gap-4">
          <div
            v-for="document in currentDocuments"
            :key="document.id"
            class="document-card border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center mb-2">
                  <i class="fas fa-file-alt text-blue-600 mr-3 text-xl" />
                  <div>
                    <h3 class="font-semibold text-lg">{{ document.code }}</h3>
                    <p class="text-gray-600 text-sm">{{ document.title }}</p>
                  </div>
                </div>

                <div class="document-meta grid grid-cols-2 gap-3 mt-3 text-sm">
                  <div>
                    <span class="text-gray-500">Auteur:</span>
                    <span class="font-medium ml-2">{{ document.author?.name || 'N/A' }}</span>
                  </div>
                  <div>
                    <span class="text-gray-500">Site:</span>
                    <span class="font-medium ml-2">{{ document.site?.name || 'N/A' }}</span>
                  </div>
                  <div>
                    <span class="text-gray-500">Version:</span>
                    <span class="font-medium ml-2">{{ document.version }}</span>
                  </div>
                  <div>
                    <span class="text-gray-500">Date:</span>
                    <span class="font-medium ml-2">{{ formatDate(document.created_at) }}</span>
                  </div>
                </div>
              </div>

              <div class="actions-column ml-4">
                <button
                  v-if="activeTab === 'verification'"
                  class="btn btn-sm btn-primary mb-2 w-full"
                  @click="openDocument(document, 'verify')"
                >
                  <i class="fas fa-check-circle mr-2" />
                  Vérifier
                </button>
                <button
                  v-if="activeTab === 'approval'"
                  class="btn btn-sm btn-success mb-2 w-full"
                  @click="openDocument(document, 'approve')"
                >
                  <i class="fas fa-check-double mr-2" />
                  Valider
                </button>
                <button
                  class="btn btn-sm btn-secondary w-full"
                  @click="openDocument(document, 'view')"
                >
                  <i class="fas fa-eye mr-2" />
                  Voir
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { PendingDocuments, WorkflowStats } from '../../composables/useDocumentWorkflow'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { expandPermissionAliases } from '@/utils/permissions'
  import { useDocumentWorkflow } from '../../composables/useDocumentWorkflow'

  const authStore = useAuthStore()
  const router = useRouter()
  const { loading, getPendingDocuments, getWorkflowStats } = useDocumentWorkflow()

  const activeTab = ref<'verification' | 'approval'>('verification')
  const pendingDocuments = ref<PendingDocuments>({
    pending_verification: [],
    pending_approval: [],
    total: 0,
  })
  const stats = ref<WorkflowStats>({
    verified: 0,
    approved: 0,
    rejected: 0,
    period_days: 30,
  })

  const normalizedPermissionSet = computed(() => getNavigationPermissionSet(authStore.user))
  const rawPermissionSet = computed(() => {
    const user = authStore.user as any
    const candidates = [
      ...(Array.isArray(user?.active_scoped_permissions) ? user.active_scoped_permissions : []),
      ...(Array.isArray(user?.effective_permissions) ? user.effective_permissions : []),
      ...(Array.isArray(user?.all_permissions) ? user.all_permissions : []),
    ]

    const names = candidates
      .map((entry: any) => {
        if (typeof entry === 'string') {
          return entry
        }
        if (typeof entry?.name === 'string') {
          return entry.name
        }
        if (typeof entry?.attributes?.name === 'string') {
          return entry.attributes.name
        }
        return null
      })
      .filter((value): value is string => typeof value === 'string')
      .map(value => value.trim().toLowerCase())

    return new Set(names)
  })

  const hasPermission = (permission: string): boolean => {
    const normalizedMatches = expandPermissionAliases(permission).some(alias => normalizedPermissionSet.value.has(alias))
    if (normalizedMatches) {
      return true
    }

    const rawKey = permission.trim().toLowerCase()
    return rawPermissionSet.value.has(rawKey)
  }

  const canVerifyDocuments = computed(() => hasPermission('verify_documents'))
  const canApproveDocuments = computed(() => hasPermission('approve_documents'))
  const hasWorkflowPermissions = computed(() => canVerifyDocuments.value || canApproveDocuments.value)

  function syncActiveTab () {
    if (activeTab.value === 'verification' && !canVerifyDocuments.value && canApproveDocuments.value) {
      activeTab.value = 'approval'
    }
    if (activeTab.value === 'approval' && !canApproveDocuments.value && canVerifyDocuments.value) {
      activeTab.value = 'verification'
    }
  }

  const currentDocuments = computed(() => {
    return activeTab.value === 'verification'
      ? pendingDocuments.value.pending_verification
      : pendingDocuments.value.pending_approval
  })

  async function loadData () {
    try {
      const [pending, workflowStats] = await Promise.all([
        getPendingDocuments(),
        getWorkflowStats(30),
      ])

      pendingDocuments.value = pending
      stats.value = workflowStats
    } catch (error) {
      console.error('Erreur chargement données:', error)
    }
  }

  function openDocument (document: any, action: 'verify' | 'approve' | 'view') {
    router.push(`/company/documents/${document.id}?action=${action}`)
  }

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    })
  }

  watch([canVerifyDocuments, canApproveDocuments], () => {
    syncActiveTab()
  }, { immediate: true })

  onMounted(() => {
    if (!hasWorkflowPermissions.value) {
      return
    }
    loadData()
  })
</script>

<style scoped>
.tabs {
  display: flex;
  gap: 0.5rem;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: white;
  color: #6b7280;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-button:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

.tab-button.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.document-card {
  transition: all 0.2s;
}

.document-card:hover {
  border-color: #3b82f6;
}
</style>
