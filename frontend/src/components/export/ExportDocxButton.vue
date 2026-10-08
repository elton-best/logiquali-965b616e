<template>
  <v-btn
    :color="color"
    :disabled="disabled"
    :loading="loading"
    :size="size"
    :variant="variant"
    @click="handleExport"
  >
    <v-icon left>mdi-file-word</v-icon>
    {{ label }}
  </v-btn>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import api from '@/api/client'

  interface Props {
    endpoint: string
    filename?: string
    params?: Record<string, any>
    label?: string
    color?: string
    variant?: 'flat' | 'text' | 'elevated' | 'outlined' | 'plain' | 'tonal'
    size?: 'x-small' | 'small' | 'default' | 'large' | 'x-large'
    disabled?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    filename: 'export.docx',
    label: 'Exporter DOCX',
    color: 'primary',
    variant: 'elevated',
    size: 'default',
    disabled: false,
    params: () => ({}),
  })

  const emit = defineEmits<{
    success: []
    error: [error: Error]
  }>()

  const loading = ref(false)

  async function handleExport () {
    loading.value = true

    try {
      const response = await api.get(props.endpoint, {
        params: props.params,
        responseType: 'blob',
      })

      // Create download link
      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      })

      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url

      // Extract filename from Content-Disposition header if available
      const contentDisposition = response.headers['content-disposition']
      let filename = props.filename

      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1]
        }
      }

      link.setAttribute('download', filename)
      document.body.append(link)
      link.click()

      // Cleanup
      link.remove()
      window.URL.revokeObjectURL(url)

      emit('success')
    } catch (error: any) {
      console.error('Export failed:', error)
      emit('error', error)
    } finally {
      loading.value = false
    }
  }
</script>
