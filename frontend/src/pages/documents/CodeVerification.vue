<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Vérification des codes documents</h1>

    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="p-8 text-center text-gray-500">Chargement...</div>
      <div v-else-if="error" class="p-8 text-center text-red-500">{{ error }}</div>
      <div v-else-if="documents.length === 0" class="p-8 text-center text-gray-500">
        Aucun document en attente de vérification
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titre</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auteur</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date création</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="doc in documents" :key="doc.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm">{{ doc.code }}</td>
            <td class="px-6 py-4">{{ doc.title }}</td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                {{ doc.type }}
              </span>
            </td>
            <td class="px-6 py-4">{{ doc.author?.name || '-' }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ formatDate(doc.created_at) }}
            </td>
            <td class="px-6 py-4 text-right">
              <button
                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 text-sm"
                :disabled="verifying === doc.id"
                @click="verify(doc)"
              >
                {{ verifying === doc.id ? 'Vérification...' : 'Vérifier' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
  import { onMounted, ref } from 'vue'
  import { documentWorkflowApi } from '@/api/documentWorkflow'

  interface Document {
    id: number
    code: string
    title: string
    type: string
    author?: { name: string }
    created_at: string
  }

  type WorkflowDocument = UnifiedDocument & {
    title?: string
    nom?: string
    author?: { name?: string }
  }

  const documents = ref<Document[]>([])
  const loading = ref(false)
  const error = ref('')
  const verifying = ref<number | null>(null)

  async function loadDocuments () {
    loading.value = true
    error.value = ''
    try {
      const response = await documentWorkflowApi.getPendingVerification()
      documents.value = (response.data as WorkflowDocument[]).map(doc => ({
        id: doc.id,
        code: doc.code,
        title: doc.title || doc.nom || 'Document sans titre',
        type: doc.type,
        author: doc.author?.name ? { name: doc.author.name } : undefined,
        created_at: doc.created_at,
      }))
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement'
    } finally {
      loading.value = false
    }
  }

  async function verify (doc: Document) {
    if (!confirm(`Vérifier le code "${doc.code}" pour le document "${doc.title}" ?`)) return

    verifying.value = doc.id
    try {
      await documentWorkflowApi.verifyCode(doc.id)
      documents.value = documents.value.filter(d => d.id !== doc.id)
    } catch (error_: any) {
      alert(error_.response?.data?.message || 'Erreur lors de la vérification')
    } finally {
      verifying.value = null
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }

  onMounted(() => loadDocuments())
</script>
