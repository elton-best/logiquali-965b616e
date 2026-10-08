<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Approbation des codes documents</h1>

    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="p-8 text-center text-gray-500">Chargement...</div>
      <div v-else-if="error" class="p-8 text-center text-red-500">{{ error }}</div>
      <div v-else-if="documents.length === 0" class="p-8 text-center text-gray-500">
        Aucun document en attente de validation
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titre</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vérifié par</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date vérification</th>
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
            <td class="px-6 py-4">{{ doc.verifier?.name || '-' }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ formatDate(doc.verified_at) }}
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <button
                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 text-sm"
                :disabled="activating === doc.id"
                @click="activate(doc)"
              >
                {{ activating === doc.id ? 'Activation...' : 'Valider' }}
              </button>
              <button
                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 text-sm"
                :disabled="releasing === doc.id"
                @click="release(doc)"
              >
                {{ releasing === doc.id ? 'Libération...' : 'Rejeter' }}
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
    verifier?: { name: string }
    verified_at: string
  }

  type WorkflowDocument = UnifiedDocument & {
    title?: string
    nom?: string
    verifier?: { name?: string }
  }

  const documents = ref<Document[]>([])
  const loading = ref(false)
  const error = ref('')
  const activating = ref<number | null>(null)
  const releasing = ref<number | null>(null)

  async function loadDocuments () {
    loading.value = true
    error.value = ''
    try {
      const response = await documentWorkflowApi.getPendingApproval()
      documents.value = (response.data as WorkflowDocument[]).map(doc => ({
        id: doc.id,
        code: doc.code,
        title: doc.title || doc.nom || 'Document sans titre',
        type: doc.type,
        verifier: doc.verifier?.name ? { name: doc.verifier.name } : undefined,
        verified_at: doc.validated_at || doc.updated_at || doc.created_at,
      }))
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement'
    } finally {
      loading.value = false
    }
  }

  async function activate (doc: Document) {
    if (!confirm(`Valider et activer le code "${doc.code}" pour le document "${doc.title}" ?`)) return

    activating.value = doc.id
    try {
      await documentWorkflowApi.activateCode(doc.id)
      documents.value = documents.value.filter(d => d.id !== doc.id)
    } catch (error_: any) {
      alert(error_.response?.data?.message || 'Erreur lors de l\'activation')
    } finally {
      activating.value = null
    }
  }

  async function release (doc: Document) {
    if (!confirm(`Rejeter et libérer le code "${doc.code}" ? Le numéro sera recyclé.`)) return

    releasing.value = doc.id
    try {
      await documentWorkflowApi.releaseCode(doc.id)
      documents.value = documents.value.filter(d => d.id !== doc.id)
    } catch (error_: any) {
      alert(error_.response?.data?.message || 'Erreur lors de la libération')
    } finally {
      releasing.value = null
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  onMounted(() => loadDocuments())
</script>
