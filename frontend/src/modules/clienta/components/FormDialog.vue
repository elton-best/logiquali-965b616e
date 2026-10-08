<template>
  <AppModal
    :max-width="maxWidth"
    :model-value="modelValue"
    :title="title"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #default>
      <form ref="formRef" @submit.prevent="submit">
        <slot :errors="errors" :form-data="formData" />
      </form>
    </template>

    <template #footer>
      <div class="flex justify-end gap-3">
        <AppButton variant="outline" @click="cancel">
          {{ cancelLabel }}
        </AppButton>
        <AppButton
          :disabled="!valid || loading"
          :loading="loading"
          :variant="submitVariant"
          @click="submit"
        >
          {{ submitLabel }}
        </AppButton>
      </div>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import AppButton from '@/components/common/AppButton.vue'
  import AppModal from '@/components/common/AppModal.vue'

  interface Props {
    modelValue: boolean
    title?: string
    formData?: any
    errors?: Record<string, string>
    loading?: boolean
    maxWidth?: string | number
    editMode?: boolean
    headerClass?: string
    contentClass?: string
    actionsClass?: string
    submitColor?: string
    cancelLabel?: string
    createLabel?: string
    editLabel?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    title: 'Formulaire',
    formData: () => ({}),
    errors: () => ({}),
    loading: false,
    maxWidth: '700',
    editMode: false,
    headerClass: 'bg-primary text-white pa-4',
    contentClass: 'pa-6',
    actionsClass: 'pa-4',
    submitColor: 'primary',
    cancelLabel: 'Annuler',
    createLabel: 'Créer',
    editLabel: 'Modifier',
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', data: any): void
    (e: 'cancel'): void
  }>()

  const formRef = ref<HTMLFormElement>()
  const valid = ref(true) // Simplified validation - can be enhanced

  const submitLabel = computed(() =>
    props.editMode ? props.editLabel : props.createLabel,
  )

  const submitVariant = computed<'primary' | 'secondary'>(() =>
    props.submitColor === 'primary' ? 'primary' : 'secondary',
  )

  function submit () {
    if (formRef.value?.checkValidity()) {
      emit('submit', props.formData)
    }
  }

  function cancel () {
    formRef.value?.reset()
    emit('cancel')
  }

  // Reset form when dialog closes
  watch(() => props.modelValue, newVal => {
    if (!newVal) {
      formRef.value?.reset()
    }
  })
</script>
