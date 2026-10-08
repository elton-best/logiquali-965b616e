<template>
  <div class="nomenclature-stepper">
    <!-- Progress Bar -->
    <v-card class="mb-4" elevation="0">
      <v-progress-linear
        color="primary"
        height="4"
        :model-value="(currentStep / 4) * 100"
      />
      <v-card-text class="pa-2 text-center text-caption text-medium-emphasis">
        Étape {{ currentStep }} sur 4
      </v-card-text>
    </v-card>

    <!-- Stepper -->
    <v-stepper
      v-model="currentStep"
      editable
      :items="stepTitles"
      non-linear
    >
      <!-- Step 1: Document Types -->
      <template #[`item.1`]>
        <Step1DocumentTypes
          :document-types="documentTypes"
          :is-valid="step1Valid"
          @update:document-types="documentTypes = $event"
        />
      </template>

      <!-- Step 2: Process Mapping -->
      <template #[`item.2`]>
        <Step2ProcessMapping
          :is-valid="step2Valid"
          :process-mapping="processMapping"
          :processes="processes"
          :selected-process="selectedProcess"
          @update:process-mapping="processMapping = $event"
          @update:selected-process="selectedProcess = $event"
        />
      </template>

      <!-- Step 3: Naming Pattern -->
      <template #[`item.3`]>
        <Step3NamingPattern
          :document-types="documentTypes"
          :is-valid="step3Valid"
          :naming-pattern="namingPattern"
          :selected-doc-type="selectedDocType"
          @update:naming-pattern="namingPattern = $event"
          @update:selected-doc-type="selectedDocType = $event"
        />
      </template>

      <!-- Step 4: Review & Publish -->
      <template #[`item.4`]>
        <Step4ReviewPublish
          :document-types="documentTypes"
          :is-submitting="isSubmitting"
          :naming-pattern="namingPattern"
          :process-mapping="processMapping"
          :selected-doc-type="selectedDocType"
          :selected-process="selectedProcess"
          @publish="handlePublish"
        />
      </template>
    </v-stepper>

    <!-- Navigation Buttons -->
    <v-card class="mt-6" elevation="0">
      <v-card-text class="d-flex gap-3 justify-end pa-4">
        <v-btn
          :disabled="currentStep === 1"
          variant="tonal"
          @click="previousStep"
        >
          <v-icon start>mdi-arrow-left</v-icon>
          Précédent
        </v-btn>

        <v-btn
          v-if="currentStep < 4"
          color="primary"
          :disabled="!isCurrentStepValid"
          @click="nextStep"
        >
          Suivant
          <v-icon end>mdi-arrow-right</v-icon>
        </v-btn>

        <v-btn
          v-else
          color="success"
          :disabled="!isCurrentStepValid || isSubmitting"
          :loading="isSubmitting"
          @click="handlePublish"
        >
          <v-icon start>mdi-check-circle-outline</v-icon>
          Publier
        </v-btn>

        <v-btn
          variant="text"
          @click="emit('cancel')"
        >
          Annuler
        </v-btn>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentTypeCatalog, Nomenclature } from '../../types/document.types'
  import { computed, onMounted, ref, watch } from 'vue'
  import Step1DocumentTypes from './steps/Step1DocumentTypes.vue'
  import Step2ProcessMapping from './steps/Step2ProcessMapping.vue'
  import Step3NamingPattern from './steps/Step3NamingPattern.vue'
  import Step4ReviewPublish from './steps/Step4ReviewPublish.vue'

  interface StepperProps {
    nomenclatures?: Nomenclature[]
    processes?: any[]
    documentTypeCatalogs?: DocumentTypeCatalog[]
    onSave?: (data: any) => Promise<void>
    onCancel?: () => void
  }

  const props = withDefaults(defineProps<StepperProps>(), {
    nomenclatures: () => [],
    processes: () => [],
    documentTypeCatalogs: () => [],
  })

  const emit = defineEmits<{
    save: [data: any]
    cancel: []
  }>()

  // State
  const currentStep = ref(1)
  const isSubmitting = ref(false)

  const documentTypes = ref<DocumentTypeCatalog[]>([])
  const selectedProcess = ref<any>(null)
  const selectedDocType = ref<any>(null)
  const processMapping = ref<Map<number, any>>(new Map())
  const namingPattern = ref<{
    parts: Array<{ type: string, token?: string, value?: string, label?: string }>
    format: string
    preview: string
  }>({
    parts: [],
    format: '',
    preview: '',
  })

  // Step titles
  const stepTitles = [
    '📄 Types documentaires',
    '⚙️ Processus & Mapping',
    '🔤 Format de codification',
    '✅ Résumé & Publication',
  ]

  // Validations
  const step1Valid = computed(() => documentTypes.value.length > 0)
  const step2Valid = computed(() => selectedProcess.value || processMapping.value.size > 0)
  const step3Valid = computed(() => namingPattern.value.parts.length > 0)
  const step4Valid = computed(() => step1Valid.value && step2Valid.value && step3Valid.value)

  const isCurrentStepValid = computed(() => {
    switch (currentStep.value) {
      case 1: {
        return step1Valid.value
      }
      case 2: {
        return step2Valid.value
      }
      case 3: {
        return step3Valid.value
      }
      case 4: {
        return step4Valid.value
      }
      default: {
        return false
      }
    }
  })

  // Methods
  function nextStep () {
    if (isCurrentStepValid.value && currentStep.value < 4) {
      currentStep.value++
      saveProgressToLocalStorage()
    }
  }

  function previousStep () {
    if (currentStep.value > 1) {
      currentStep.value--
    }
  }

  async function handlePublish () {
    if (!isCurrentStepValid.value) return

    isSubmitting.value = true
    try {
      const payload = {
        documentTypes: documentTypes.value,
        selectedProcess: selectedProcess.value,
        processMapping: Array.from(processMapping.value.entries()),
        namingPattern: namingPattern.value,
        selectedDocType: selectedDocType.value,
      }

      if (props.onSave) {
        await props.onSave(payload)
      }

      emit('save', payload)
      clearProgressFromLocalStorage()
    } catch (error) {
      console.error('Erreur lors de la publication:', error)
    } finally {
      isSubmitting.value = false
    }
  }

  // LocalStorage persistence
  function saveProgressToLocalStorage () {
    const progress = {
      currentStep: currentStep.value,
      documentTypes: documentTypes.value,
      selectedProcess: selectedProcess.value,
      namingPattern: namingPattern.value,
    }
    localStorage.setItem('nomenclature-stepper-progress', JSON.stringify(progress))
  }

  function loadProgressFromLocalStorage () {
    const saved = localStorage.getItem('nomenclature-stepper-progress')
    if (saved) {
      try {
        const progress = JSON.parse(saved)
        currentStep.value = progress.currentStep ?? 1
        documentTypes.value = progress.documentTypes ?? []
        selectedProcess.value = progress.selectedProcess ?? null
        namingPattern.value = progress.namingPattern ?? { parts: [], format: '', preview: '' }
      } catch (error) {
        console.warn('Erreur lors du chargement du progress:', error)
      }
    }
  }

  function clearProgressFromLocalStorage () {
    localStorage.removeItem('nomenclature-stepper-progress')
  }

  // Initialize
  onMounted(() => {
    documentTypes.value = props.documentTypeCatalogs
    loadProgressFromLocalStorage()
  })

  // Watch for external changes
  watch(() => props.documentTypeCatalogs, newVal => {
    if (newVal && newVal.length > 0) {
      documentTypes.value = newVal
    }
  }, { deep: true })
</script>

<style scoped lang="scss">
.nomenclature-stepper {
  position: relative;

  :deep(.v-stepper) {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    border-radius: 8px;
  }

  :deep(.v-stepper-item__title) {
    font-weight: 500;
  }
}
</style>
