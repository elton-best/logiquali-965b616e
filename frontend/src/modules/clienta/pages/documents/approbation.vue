<template>
  <ClientALayout current-page="approbation">
    <div>
      <div class="d-flex align-center justify-space-between mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold">Validation des documents</h1>
          <p class="text-body-1 text-medium-emphasis mt-1">
            Gérez et validez les documents (validation finale).
          </p>
        </div>
      </div>

      <v-alert
        v-if="!canApproveDocuments"
        class="mb-6"
        type="warning"
        variant="tonal"
      >
        Action non autorisée. Vous n'avez pas les permissions nécessaires.
      </v-alert>

      <v-card class="elevation-1 border rounded-xl" :loading="loading">
        <v-data-table
          class="border-0 bg-transparent"
          :headers="headers"
          :items="documents"
          :loading="loading"
          :disabled="!canApproveDocuments"
          no-data-text="Aucune donnée disponible"
          items-per-page-text="Éléments par page :"
        >
          <template #item.status="{ item }">
              <v-chip :color="getDocumentBusinessStatusConfig(item).color" size="small" variant="tonal">
              {{ getDocumentBusinessStatusLabel(item) }}
            </v-chip>
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex ga-2">
              <v-btn color="success" size="small" variant="tonal" @click="approveDocument(item)">
                <v-icon start>mdi-check-all</v-icon>
                Valider
              </v-btn>
              <v-btn color="primary" size="small" variant="outlined" @click="previewDocument(item)">
                <v-icon start>mdi-eye</v-icon>
                Visualiser
              </v-btn>
              <v-btn color="error" size="small" variant="tonal" @click="openRejectModal(item)">
                <v-icon start>mdi-close</v-icon>
                Rejeter
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card>

      <!-- Modale de Prévisualisation -->
      <DocumentPreviewModal
        v-model="showPreviewModal"
        :document="selectedDocument"
        :loading="previewLoading"
        @download="downloadDocument"
      />

      <!-- Modale de Rejet -->
      <v-dialog v-model="showRejectModal" max-width="500">
        <v-card rounded="xl">
          <v-card-title class="bg-error text-white pa-4">Rejeter le document</v-card-title>
          <v-card-text class="pt-6">
            <v-textarea
              v-model="rejectionReason"
              label="Motif du rejet (obligatoire)"
              required
              rows="3"
              variant="outlined"
            />
            <v-alert class="mt-4" density="comfortable" type="info" variant="tonal">
              Le soumissionnaire recevra le motif et devra choisir : libérer le code ou garder le code pour correction.
            </v-alert>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="showRejectModal = false">Annuler</v-btn>
            <v-btn color="error" :disabled="!rejectionReason" variant="elevated" @click="rejectDocument">
              Confirmer le rejet
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { documentService } from '@/services/documentService'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { getDocumentBusinessStatusConfig, getDocumentBusinessStatusLabel } from '@/modules/clienta/utils/documentStatus'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DocumentPreviewModal from '@/modules/clienta/components/documents/DocumentPreviewModal.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { expandPermissionAliases } from '@/utils/permissions'

  const authStore = useAuthStore()
  const toast = useToast()
  const loading = ref(false)
  const previewLoading = ref(false)
  const documents = ref<any[]>([])
  const showRejectModal = ref(false)
  const showPreviewModal = ref(false)
  const rejectionReason = ref('')
  const selectedDocument = ref<any>(null)

  const normalizedPermissionSet = computed(() => getNavigationPermissionSet(authStore.user))
  const rawPermissionSet = computed(() => {
    const user = authStore.user as any
    const candidates = [
      ...(Array.isArray(user?.active_scoped_permissions) ? user.active_scoped_permissions : []),
      ...(Array.isArray(user?.effective_permissions) ? user.effective_permissions : []),
      ...(Array.isArray(user?.all_permissions) ? user.all_permissions : []),
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

  const canApproveDocuments = computed(() => {
    const normalizedMatches = expandPermissionAliases('approve_documents')
      .some(alias => normalizedPermissionSet.value.has(alias))
    if (normalizedMatches) return true
    return rawPermissionSet.value.has('approve_documents')
  })

  const headers = [
    { title: 'Code', key: 'code' },
    { title: 'Titre', key: 'title' },
    { title: 'Processus', key: 'process_label' },
    { title: 'Source', key: 'source_label' },
    { title: 'Auteur', key: 'author.name' },
    { title: 'Vérificateur', key: 'verifier.name' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  async function fetchPendingApproval () {
    if (!canApproveDocuments.value) {
      documents.value = []
      return
    }

    try {
      loading.value = true
      const response = await api.get('/documents', { params: { workflow_status: 'pending_approval' } })
      const payload = response.data?.data || response.data || []
      documents.value = Array.isArray(payload)
        ? payload.map(normalizeDocument).filter(Boolean)
        : []
    } catch {
      toast.error('Erreur lors du chargement des documents')
    } finally {
      loading.value = false
    }
  }

  function normalizeDocument (item: any) {
    if (!item) return null

    const attributes = item.attributes || item
    const relationships = item.relationships || {}
    const author = relationships.author || attributes.author || null
    const verifier = relationships.verifier || attributes.verifier || null
    const process = relationships.process || attributes.process || null
    const authorName = author?.attributes?.name || author?.name || '—'
    const verifierName = verifier?.attributes?.name || verifier?.name || '—'
    const processCode = process?.attributes?.code || process?.code || null
    const processName = process?.attributes?.name || process?.attributes?.title || process?.name || process?.title || null
    const sourceHierarchy = [
      attributes.source_module,
      attributes.source_submodule,
      attributes.source_section,
    ].filter(Boolean).join(' > ')
    const id = Number(item.id || attributes.id)

    if (!Number.isFinite(id) || id <= 0) return null

    return {
      id,
      code: attributes.code || '—',
      title: attributes.title || attributes.nom || '—',
      process_label: processCode && processName ? `${processCode} - ${processName}` : (processCode || processName || '—'),
      source_label: sourceHierarchy || (attributes.module_type
        ? `${attributes.module_type}${attributes.module_id ? ` #${attributes.module_id}` : ''}`
        : (attributes.source_type || '—')),
      author: { name: authorName },
      verifier: { name: verifierName },
      workflow_status: attributes.workflow_status || attributes.status || null,
      status: attributes.status || null,
      version: attributes.version || '1.0',
      metadata: attributes.metadata || null,
    }
  }

  async function approveDocument (doc: any) {
    try {
      loading.value = true
      await api.post(`/documents/${doc.id}/approve`)
      toast.success('Document validé avec succès')
      fetchPendingApproval()
    } catch {
      toast.error('Erreur lors de la validation')
    } finally {
      loading.value = false
    }
  }

  function openRejectModal (doc: any) {
    selectedDocument.value = doc
    rejectionReason.value = ''
    showRejectModal.value = true
  }

  async function rejectDocument () {
    if (!selectedDocument.value) return

    try {
      loading.value = true
      await api.post(`/documents/${selectedDocument.value.id}/reject`, {
        rejection_reason: rejectionReason.value,
      })
      toast.success('Rejet enregistre. En attente de la confirmation du soumissionnaire.')
      showRejectModal.value = false
      fetchPendingApproval()
    } catch {
      toast.error('Erreur lors du rejet')
    } finally {
      loading.value = false
    }
  }

  function previewDocument (doc: any) {
    selectedDocument.value = doc
    showPreviewModal.value = true
  }

  function downloadDocument () {
    if (!selectedDocument.value) return
    documentService.download(selectedDocument.value.id).then(blob => {
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${selectedDocument.value.code || 'document'}.pdf`
      a.click()
      URL.revokeObjectURL(url)
    }).catch(() => toast.error('Erreur lors du téléchargement'))
  }

  onMounted(() => {
    fetchPendingApproval()
  })
</script>
