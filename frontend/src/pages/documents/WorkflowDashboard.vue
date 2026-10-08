<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Dashboard Workflow Documents</h1>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm text-gray-600 mb-1">En attente de vérification</div>
        <div class="text-3xl font-bold text-orange-600">{{ stats.pending_verification || 0 }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm text-gray-600 mb-1">En attente de validation</div>
        <div class="text-3xl font-bold text-blue-600">{{ stats.pending_approval || 0 }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm text-gray-600 mb-1">Codes actifs</div>
        <div class="text-3xl font-bold text-green-600">{{ stats.active_codes || 0 }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm text-gray-600 mb-1">Codes libérés</div>
        <div class="text-3xl font-bold text-gray-600">{{ stats.released_codes || 0 }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm text-gray-600 mb-1">Total documents</div>
        <div class="text-3xl font-bold text-gray-800">{{ stats.total_documents || 0 }}</div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Vérification -->
      <div v-if="canVerify" class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-lg font-semibold">Documents à vérifier</h2>
          <router-link
            class="text-blue-600 hover:text-blue-800 text-sm"
            to="/documents/workflow/verification"
          >
            Voir tout →
          </router-link>
        </div>
        <div v-if="loadingVerification" class="p-8 text-center text-gray-500">Chargement...</div>
        <div v-else-if="pendingVerification.length === 0" class="p-8 text-center text-gray-500">
          Aucun document en attente
        </div>
        <div v-else class="divide-y max-h-96 overflow-y-auto">
          <div
            v-for="doc in pendingVerification.slice(0, 5)"
            :key="doc.id"
            class="p-4 hover:bg-gray-50 cursor-pointer"
            @click="goToVerification"
          >
            <div class="font-medium">{{ doc.title }}</div>
            <div class="text-sm text-gray-600 mt-1">
              <span class="font-mono">{{ doc.code }}</span> • {{ doc.type }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
              Par {{ doc.author?.name || 'Inconnu' }} • {{ formatDate(doc.created_at) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Approbation -->
      <div v-if="canApprove" class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-lg font-semibold">Documents à approuver</h2>
          <router-link
            class="text-blue-600 hover:text-blue-800 text-sm"
            to="/documents/workflow/approval"
          >
            Voir tout →
          </router-link>
        </div>
        <div v-if="loadingApproval" class="p-8 text-center text-gray-500">Chargement...</div>
        <div v-else-if="pendingApproval.length === 0" class="p-8 text-center text-gray-500">
          Aucun document en attente
        </div>
        <div v-else class="divide-y max-h-96 overflow-y-auto">
          <div
            v-for="doc in pendingApproval.slice(0, 5)"
            :key="doc.id"
            class="p-4 hover:bg-gray-50 cursor-pointer"
            @click="goToApproval"
          >
            <div class="font-medium">{{ doc.title }}</div>
            <div class="text-sm text-gray-600 mt-1">
              <span class="font-mono">{{ doc.code }}</span> • {{ doc.type }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
              Vérifié par {{ doc.verifier?.name || 'Inconnu' }} • {{ formatDate(doc.verified_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Graphique de tendance (optionnel) -->
    <div class="mt-6 bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Activité récente</h2>
      <div class="text-center text-gray-500 py-8">
        Graphique de tendance à venir
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { apiClient } from '@/api/client'
  import { documentWorkflowApi } from '@/api/documentWorkflow'
  import { useAuthStore } from '@/stores/auth'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'

  const router = useRouter()
  const authStore = useAuthStore()

  const stats = ref({
    pending_verification: 0,
    pending_approval: 0,
    active_codes: 0,
    released_codes: 0,
    total_documents: 0,
  })

  const pendingVerification = ref<any[]>([])
  const pendingApproval = ref<any[]>([])
  const loadingVerification = ref(false)
  const loadingApproval = ref(false)

  const normalizedPermissionSet = computed(() => getNavigationPermissionSet(authStore.user))
  const rawPermissionSet = computed(() => {
    const user = authStore.user as any
    const candidates = [
      ...(Array.isArray(user?.active_scoped_permissions) ? user.active_scoped_permissions : []),
      ...(Array.isArray(user?.effective_permissions) ? user.effective_permissions : []),
      ...(Array.isArray(user?.all_permissions) ? user.all_permissions : []),
      ...(Array.isArray(user?.permissions) ? user.permissions : []),
    ]

    return new Set(
      candidates
        .map((entry: any) => {
          if (typeof entry === 'string') return entry
          if (typeof entry?.name === 'string') return entry.name
          if (typeof entry?.attributes?.name === 'string') return entry.attributes.name
          return null
        })
        .filter((value): value is string => typeof value === 'string')
        .map(value => value.trim().toLowerCase()),
    )
  })

  const hasPermission = (permission: string): boolean => {
    const normalized = permission.trim().toLowerCase()
    return normalizedPermissionSet.value.has(normalized) || rawPermissionSet.value.has(normalized)
  }

  const canVerify = computed(() => hasPermission('verify_documents'))
  const canApprove = computed(() => hasPermission('approve_documents'))

  async function loadStats () {
    try {
      const response = await apiClient.get('/document-code-workflow/stats')
      stats.value = response.data.data
    } catch (error) {
      console.error('Error loading stats:', error)
    }
  }

  async function loadPendingVerification () {
    if (!canVerify.value) return

    loadingVerification.value = true
    try {
      const response = await documentWorkflowApi.getPendingVerification()
      pendingVerification.value = response.data
    } catch (error) {
      console.error('Error loading pending verification:', error)
    } finally {
      loadingVerification.value = false
    }
  }

  async function loadPendingApproval () {
    if (!canApprove.value) return

    loadingApproval.value = true
    try {
      const response = await documentWorkflowApi.getPendingApproval()
      pendingApproval.value = response.data
    } catch (error) {
      console.error('Error loading pending approval:', error)
    } finally {
      loadingApproval.value = false
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }

  function goToVerification () {
    router.push('/documents/workflow/verification')
  }

  function goToApproval () {
    router.push('/documents/workflow/approval')
  }

  onMounted(() => {
    loadStats()
    loadPendingVerification()
    loadPendingApproval()
  })
</script>
