<template>
  <BaseModal
    :model-value="show"
    size="lg"
    :title="isEdit ? 'Modifier le constat' : 'Nouveau constat'"
    @close="$emit('close')"
    @update:model-value="$emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <!-- Severity & Category -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Gravité <span class="text-red-500">*</span>
          </label>
          <select
            v-model="formData.severity"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            required
          >
            <option value="majeur">Majeur</option>
            <option value="mineur">Mineur</option>
            <option value="observation">Observation</option>
            <option value="opportunite">Opportunité</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Catégorie <span class="text-red-500">*</span>
          </label>
          <select
            v-model="formData.category"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            required
          >
            <option value="non_conformite">Non-conformité</option>
            <option value="observation">Observation</option>
            <option value="bonne_pratique">Bonne pratique</option>
            <option value="opportunite">Opportunité</option>
          </select>
        </div>
      </div>

      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Titre <span class="text-red-500">*</span>
        </label>
        <input
          v-model="formData.title"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
          placeholder="Titre court du constat"
          required
          type="text"
        >
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Description <span class="text-red-500">*</span>
        </label>
        <textarea
          v-model="formData.description"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"
          placeholder="Description détaillée du constat..."
          required
          rows="4"
        />
      </div>

      <!-- Evidence & Requirement -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Preuves objectives
          </label>
          <textarea
            v-model="formData.evidence"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"
            placeholder="Preuves constatées..."
            rows="3"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Exigence applicable
          </label>
          <textarea
            v-model="formData.requirement"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"
            placeholder="Référence à la norme, procédure..."
            rows="3"
          />
        </div>
      </div>

      <!-- Clause ISO & Location -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Clause ISO
          </label>
          <input
            v-model="formData.clause_iso"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            placeholder="Ex: 7.1.6, 8.5.1"
            type="text"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Localisation
          </label>
          <input
            v-model="formData.location"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            placeholder="Atelier, bureau..."
            type="text"
          >
        </div>
      </div>

      <!-- Process & Responsible -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-if="processes && processes.length > 0">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Processus concerné
          </label>
          <select
            v-model="formData.process_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
          >
            <option :value="undefined">-- Sélectionner --</option>
            <option v-for="process in processes" :key="process.id" :value="process.id">
              {{ process.name }}
            </option>
          </select>
        </div>

        <div v-if="users && users.length > 0">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Responsable
          </label>
          <select
            v-model="formData.responsible_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
          >
            <option :value="undefined">-- Sélectionner --</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Axes QHSE -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Axes QHSE
        </label>
        <div class="flex gap-3">
          <label v-for="axis in ['Q', 'H', 'S', 'E']" :key="axis" class="flex items-center">
            <input
              v-model="formData.axes_qhse"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              type="checkbox"
              :value="axis"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ axis }}</span>
          </label>
        </div>
      </div>

      <!-- Action Required -->
      <div class="flex items-center gap-4">
        <label class="flex items-center">
          <input
            v-model="formData.action_required"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            type="checkbox"
          >
          <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Action corrective requise
          </span>
        </label>

        <div v-if="formData.action_required" class="flex-1">
          <input
            v-model="formData.action_deadline"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            type="date"
          >
        </div>
      </div>

      <!-- Recommendation -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Recommandations
        </label>
        <textarea
          v-model="formData.recommendation"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"
          placeholder="Recommandations pour résoudre le constat..."
          rows="3"
        />
      </div>

      <!-- Photo Upload (Mobile-friendly) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Photos / Documents
        </label>
        <input
          ref="fileInput"
          accept="image/*,.pdf"
          class="hidden"
          multiple
          type="file"
          @change="handleFileSelect"
        >
        <button
          class="w-full px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-600 transition-colors"
          type="button"
          @click="fileInput?.click()"
        >
          <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          <span class="text-sm">Ajouter des fichiers</span>
        </button>

        <!-- Selected Files Preview -->
        <div v-if="selectedFiles.length > 0" class="mt-2 space-y-2">
          <div
            v-for="(file, index) in selectedFiles"
            :key="index"
            class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded"
          >
            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ file.name }}</span>
            <button
              class="text-red-600 hover:text-red-800"
              type="button"
              @click="removeFile(index)"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <button
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          type="button"
          @click="$emit('close')"
        >
          Annuler
        </button>
        <button
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="loading"
          type="submit"
        >
          {{ loading ? 'Enregistrement...' : (isEdit ? 'Modifier' : 'Créer') }}
        </button>
      </div>
    </form>
  </BaseModal>
</template>

<script setup lang="ts">
  import type { AuditFinding, CreateAuditFindingPayload } from '@/types/audit'
  import type { ProcessReference, UserReference } from '@/types/shared'
  import { reactive, ref, watch } from 'vue'
  import BaseModal from '@/components/common/BaseModal.vue'

  interface Props {
    show: boolean
    auditId: number
    finding?: AuditFinding
    processes?: ProcessReference[]
    users?: UserReference[]
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    close: []
    submit: [data: CreateAuditFindingPayload, files: File[]]
  }>()

  const isEdit = ref(!!props.finding)
  const loading = ref(false)
  const selectedFiles = ref<File[]>([])
  const fileInput = ref<HTMLInputElement | null>(null)

  const formData = reactive<Partial<CreateAuditFindingPayload>>({
    audit_id: props.auditId,
    severity: 'mineur',
    category: 'non_conformite',
    title: '',
    description: '',
    evidence: '',
    requirement: '',
    recommendation: '',
    clause_iso: '',
    location: '',
    process_id: undefined,
    responsible_id: undefined,
    axes_qhse: [],
    action_required: false,
    action_deadline: undefined,
    detected_at: new Date().toISOString().split('T')[0],
  })

  // Populate form if editing
  watch(() => props.finding, finding => {
    if (finding) {
      isEdit.value = true
      Object.assign(formData, {
        severity: finding.severity,
        category: finding.category,
        title: finding.title,
        description: finding.description,
        evidence: finding.evidence,
        requirement: finding.requirement,
        recommendation: finding.recommendation,
        clause_iso: finding.clause_iso,
        location: finding.location,
        process_id: finding.process_id,
        responsible_id: finding.responsible_id,
        axes_qhse: finding.axes_qhse || [],
        action_required: finding.action_required,
        action_deadline: finding.action_deadline,
        detected_at: finding.detected_at,
      })
    }
  }, { immediate: true })

  function handleFileSelect (event: Event) {
    const target = event.target as HTMLInputElement
    if (target.files) {
      selectedFiles.value.push(...Array.from(target.files))
    }
  }

  function removeFile (index: number) {
    selectedFiles.value.splice(index, 1)
  }

  async function handleSubmit () {
    loading.value = true
    try {
      emit('submit', formData as CreateAuditFindingPayload, selectedFiles.value)
    } finally {
      loading.value = false
    }
  }
</script>
