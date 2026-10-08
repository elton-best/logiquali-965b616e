<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <div class="p-6 border-b flex justify-between items-center">
        <h2 class="text-xl font-bold">{{ isEdit ? 'Modifier' : 'Créer' }} une configuration</h2>
        <button class="text-gray-500 hover:text-gray-700" @click="$emit('close')">&times;</button>
      </div>

      <div class="flex border-b">
        <button
          v-for="(step, idx) in steps"
          :key="idx"
          :class="[
            'flex-1 py-3 text-sm font-medium border-b-2 transition',
            currentStep === idx ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'
          ]"
          @click="currentStep = idx"
        >
          {{ idx + 1 }}. {{ step }}
        </button>
      </div>

      <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 200px)">
        <div v-show="currentStep === 0">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Code du type *</label>
              <input
                v-model="form.type_code"
                class="w-full px-3 py-2 border rounded"
                maxlength="10"
                placeholder="POL, PRC, FOR..."
                type="text"
              >
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Libellé *</label>
              <input
                v-model="form.type_label"
                class="w-full px-3 py-2 border rounded"
                placeholder="Politique, Procédure..."
                type="text"
              >
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Description</label>
              <textarea
                v-model="form.description"
                class="w-full px-3 py-2 border rounded"
                rows="3"
              />
            </div>
          </div>
        </div>

        <div v-show="currentStep === 1">
          <StructureBuilder v-model="form.code_structure" :type-code="form.type_code" />
        </div>

        <div v-show="currentStep === 2">
          <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded">
              <h3 class="font-medium mb-2">Aperçu du code généré</h3>
              <div v-if="preview" class="font-mono text-2xl text-blue-600">{{ preview.preview }}</div>
              <div v-else class="text-gray-500">Chargement...</div>
            </div>
            <div v-if="preview?.structure_breakdown" class="space-y-2">
              <h4 class="font-medium text-sm">Détail de la structure :</h4>
              <div
                v-for="(part, idx) in preview.structure_breakdown"
                :key="idx"
                class="flex items-center gap-3 text-sm"
              >
                <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ part.value }}</span>
                <span class="text-gray-600">{{ part.description }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <input id="is_active" v-model="form.is_active" class="rounded" type="checkbox">
              <label class="text-sm" for="is_active">Configuration active</label>
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 border-t flex justify-between">
        <button
          v-if="currentStep > 0"
          class="px-4 py-2 border rounded hover:bg-gray-50"
          @click="currentStep--"
        >
          Précédent
        </button>
        <div v-else />
        <div class="flex gap-2">
          <button class="px-4 py-2 border rounded hover:bg-gray-50" @click="$emit('close')">
            Annuler
          </button>
          <button
            v-if="currentStep < 2"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
            :disabled="!canProceed"
            @click="nextStep"
          >
            Suivant
          </button>
          <button
            v-else
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            :disabled="saving"
            @click="save"
          >
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { type CodeStructurePart, type DocumentTypeConfiguration, documentTypeConfigurationApi, type PreviewCodeResponse } from '@/api/documentTypeConfiguration'
  import StructureBuilder from './StructureBuilder.vue'

  const props = defineProps<{
    configuration?: DocumentTypeConfiguration
  }>()

  const emit = defineEmits<{
    close: []
    saved: [payload: { configuration: any, mode: 'create' | 'update' }]
  }>()

  const steps = ['Informations', 'Structure du code', 'Validation']
  const currentStep = ref(0)
  const saving = ref(false)
  const preview = ref<PreviewCodeResponse | null>(null)

  const isEdit = computed(() => !!props.configuration?.id)

  const form = ref<Omit<DocumentTypeConfiguration, 'id' | 'created_at' | 'updated_at'>>({
    type_code: '',
    type_label: '',
    description: '',
    code_structure: [],
    is_active: true,
  })

  const canProceed = computed(() => {
    if (currentStep.value === 0) {
      return form.value.type_code.trim() && form.value.type_label.trim()
    }
    if (currentStep.value === 1) {
      return form.value.code_structure.length > 0
    }
    return true
  })

  async function loadPreview () {
    if (!form.value.type_code || form.value.code_structure.length === 0) return
    try {
      const response = await documentTypeConfigurationApi.previewCode({
        code_structure: form.value.code_structure,
        type_code: form.value.type_code,
      })
      preview.value = response.data
    } catch (error) {
      console.error('Preview error:', error)
    }
  }

  function nextStep () {
    if (currentStep.value === 1) {
      loadPreview()
    }
    currentStep.value++
  }

  async function save () {
    saving.value = true
    try {
      const response = isEdit.value
        ? await documentTypeConfigurationApi.update(props.configuration!.id!, form.value)
        : await documentTypeConfigurationApi.create(form.value)
      const payload = (response.data as any)?.data ?? response.data
      emit('saved', {
        configuration: payload,
        mode: isEdit.value ? 'update' : 'create',
      })
    } catch (error: any) {
      alert(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
    } finally {
      saving.value = false
    }
  }

  watch(() => props.configuration, config => {
    if (config) {
      form.value = {
        type_code: config.type_code,
        type_label: config.type_label,
        description: config.description,
        code_structure: JSON.parse(JSON.stringify(config.code_structure)),
        is_active: config.is_active,
        enterprise_id: config.enterprise_id,
        site_id: config.site_id,
      }
    }
  }, { immediate: true })
</script>
