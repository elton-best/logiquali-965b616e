<template>
  <div class="document-detail-page p-6">
    <div v-if="loading" class="flex justify-center h-96 items-center">
      <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <div v-else-if="document" class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button class="p-2 hover:bg-gray-100 rounded-lg" @click="router.push('/documents')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
          <div>
            <h1 class="text-3xl font-bold">{{ document.title }}</h1>
            <p class="text-gray-600 mt-1">{{ document.code }} - v{{ document.current_version }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <StatusBadge module="document" :status="document.status" />
          <DocumentTypeBadge :type="document.type" :name="(document as any).type_configuration_name" />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3">
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
          @click="downloadDocument"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Télécharger
        </button>
        <button
          v-if="document.status === 'draft'"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          @click="submitForReview"
        >
          Soumettre en révision
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Preview -->
        <div class="lg:col-span-2">
          <BaseCard>
            <h2 class="text-lg font-semibold mb-4">Aperçu</h2>
            <DocumentPreview
              :document="document"
              :height="600"
              @download="downloadDocument"
            />
          </BaseCard>

          <!-- Version History -->
          <BaseCard class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Historique des versions</h2>
            <VersionHistory
              :current-version="document.version"
              :versions="document.versions || []"
              @download="handleVersionDownload"
            />
          </BaseCard>
        </div>

        <!-- Metadata -->
        <div class="space-y-6">
          <BaseCard>
            <h2 class="text-lg font-semibold mb-4">Informations</h2>
            <dl class="space-y-3">
              <div>
                <dt class="text-sm text-gray-600">Auteur</dt>
                <dd class="font-medium">{{ document.author?.name || 'N/A' }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-600">Date création</dt>
                <dd class="font-medium">{{ formatDate(document.created_at) }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-600">Dernière modification</dt>
                <dd class="font-medium">{{ formatDate(document.updated_at) }}</dd>
              </div>
              <div v-if="document.review_due_date">
                <dt class="text-sm text-gray-600">Prochaine révision</dt>
                <dd :class="['font-medium', isReviewOverdue ? 'text-red-600' : '']">
                  {{ formatDate(document.review_due_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-600">Confidentialité</dt>
                <dd class="font-medium">{{ getConfidentialityLabel(document.confidentiality_level) }}</dd>
              </div>
            </dl>
          </BaseCard>

          <!-- Workflow -->
          <BaseCard v-if="document.status !== 'draft'">
            <h2 class="text-lg font-semibold mb-4">Workflow de validation</h2>
            <ReviewWorkflow :document="document" />
          </BaseCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { storeToRefs } from 'pinia'
  import { computed, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import DocumentPreview from '@/components/documents/DocumentPreview.vue'
  import DocumentTypeBadge from '@/components/documents/DocumentTypeBadge.vue'
  import ReviewWorkflow from '@/components/documents/ReviewWorkflow.vue'
  import VersionHistory from '@/components/documents/VersionHistory.vue'
  import { useDocumentStore } from '@/stores/documentStore'

  const router = useRouter()
  const route = useRoute()
  const documentStore = useDocumentStore()

  const { currentDocument: document, loading } = storeToRefs(documentStore)

  const docId = computed<number | null>(() => {
    const params = route.params as Record<string, unknown>
    const rawParam = params.id
    const rawId = Array.isArray(rawParam) ? rawParam[0] : rawParam
    const id = Number(rawId)
    return Number.isFinite(id) && id > 0 ? id : null
  })

  const confidentialityLabels = {
    public: 'Public',
    internal: 'Interne',
    confidential: 'Confidentiel',
    restricted: 'Restreint',
  }

  const isReviewOverdue = computed(() => {
    if (!document.value?.review_due_date) return false
    return new Date(document.value.review_due_date) < new Date()
  })

  async function loadDocument () {
    if (docId.value === null) return
    await documentStore.fetchDocumentById(docId.value)
  }

  async function downloadDocument () {
    if (document.value) {
      await documentStore.downloadDocument(document.value.id)
    }
  }

  async function handleVersionDownload (versionId: number, _filename: string) {
    if (!document.value) return
    await documentStore.downloadDocument(document.value.id, versionId)
  }

  async function submitForReview () {
    if (document.value) {
      // Submit workflow logic
      await loadDocument()
    }
  }

  function formatDate (date: string | null): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getConfidentialityLabel (level?: keyof typeof confidentialityLabels): string {
    if (!level) return 'N/A'
    return confidentialityLabels[level] || level
  }

  onMounted(() => {
    loadDocument()
  })
</script>
