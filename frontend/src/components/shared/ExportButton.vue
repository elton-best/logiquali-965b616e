<template>
  <v-btn
    :color="color"
    :disabled="disabled"
    :loading="loading"
    :prepend-icon="icon"
    :variant="variant"
    @click="handleExport"
  >
    {{ label }}
  </v-btn>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import apiClient from '@/api/client'

  type ButtonVariant = 'elevated' | 'flat' | 'tonal' | 'outlined' | 'text' | 'plain'

  interface Props {
    endpoint: string
    filename?: string
    label?: string
    color?: string
    variant?: ButtonVariant
    icon?: string
    disabled?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    filename: 'export.xlsx',
    label: 'Exporter',
    color: 'primary',
    variant: 'elevated',
    icon: 'mdi-download',
    disabled: false,
  })

  const emit = defineEmits<{
    success: []
    error: [error: Error]
  }>()

  const loading = ref(false)

  async function handleExport () {
    loading.value = true

    try {
      const response = await apiClient.get(props.endpoint, {
        responseType: 'blob',
      })

      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', props.filename)
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      emit('success')
    } catch (error) {
      console.error('Export failed:', error)
      emit('error', error as Error)
    } finally {
      loading.value = false
    }
  }
</script>
