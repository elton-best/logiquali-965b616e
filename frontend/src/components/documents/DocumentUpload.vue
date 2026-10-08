/**
 * DocumentUpload Component
 * Drag & drop file upload with preview
 */

<script setup lang="ts">
  import { File as FileIcon, FileText, Upload, X } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface Props {
    modelValue: File | null
    accept?: string
    maxSize?: number // MB
    disabled?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    accept: '.pdf,.doc,.docx,.xls,.xlsx',
    maxSize: 10,
    disabled: false,
  })

  const emit = defineEmits<{
    'update:modelValue': [file: File | null]
    'error': [message: string]
  }>()

  const isDragging = ref(false)
  const fileInput = ref<HTMLInputElement>()

  const selectedFile = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const filePreview = computed(() => {
    if (!selectedFile.value) return null
    return {
      name: selectedFile.value.name,
      size: formatFileSize(selectedFile.value.size),
      type: selectedFile.value.type,
    }
  })

  function handleDragOver (e: DragEvent) {
    e.preventDefault()
    if (!props.disabled) {
      isDragging.value = true
    }
  }

  function handleDragLeave () {
    isDragging.value = false
  }

  function handleDrop (e: DragEvent) {
    e.preventDefault()
    isDragging.value = false

    if (props.disabled) return

    const files = e.dataTransfer?.files
    const droppedFile = files?.item(0)
    if (droppedFile) {
      handleFile(droppedFile)
    }
  }

  function handleFileSelect (e: Event) {
    const target = e.target as HTMLInputElement
    const selectedFile = target.files?.item(0)
    if (selectedFile) {
      handleFile(selectedFile)
    }
  }

  function handleFile (file: File) {
    const maxSizeBytes = props.maxSize * 1024 * 1024
    if (file.size > maxSizeBytes) {
      emit('error', `Le fichier est trop volumineux. Taille maximale: ${props.maxSize}MB`)
      return
    }

    if (props.accept) {
      const acceptedTypes = props.accept.split(',').map(t => t.trim())
      const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase()
      const isAccepted = acceptedTypes.some(type =>
        type === fileExtension || file.type.includes(type.replace('.', '')),
      )

      if (!isAccepted) {
        emit('error', `Type de fichier non accepté. Types acceptés: ${props.accept}`)
        return
      }
    }

    selectedFile.value = file
  }

  function removeFile () {
    selectedFile.value = null
    if (fileInput.value) {
      fileInput.value.value = ''
    }
  }

  function formatFileSize (bytes: number): string {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
  }

  function getFileIcon (filename: string) {
    const ext = filename.split('.').pop()?.toLowerCase()
    if (ext === 'pdf') return FileText
    return FileIcon
  }
</script>

<template>
  <div class="space-y-4">
    <div
      v-if="!selectedFile"
      :class="[
        'border-2 border-dashed rounded-lg p-8 text-center transition-all cursor-pointer',
        isDragging
          ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/20'
          : 'border-neutral-300 dark:border-neutral-700 hover:border-primary-400 dark:hover:border-primary-600',
        disabled && 'opacity-50 cursor-not-allowed'
      ]"
      @click="!disabled && fileInput?.click()"
      @dragleave="handleDragLeave"
      @dragover="handleDragOver"
      @drop="handleDrop"
    >
      <Upload class="w-12 h-12 mx-auto mb-4 text-neutral-400" />
      <p class="text-lg font-medium text-neutral-900 dark:text-neutral-50 mb-2">
        Glissez-déposez votre fichier ici
      </p>
      <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
        ou cliquez pour parcourir
      </p>
      <p class="text-xs text-neutral-400">
        Types acceptés: {{ accept }}
        <br>
        Taille maximale: {{ maxSize }}MB
      </p>
      <input
        ref="fileInput"
        :accept="accept"
        class="hidden"
        :disabled="disabled"
        type="file"
        @change="handleFileSelect"
      >
    </div>

    <div v-else class="card p-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
          <component :is="getFileIcon(filePreview!.name)" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
            {{ filePreview!.name }}
          </p>
          <p class="text-xs text-neutral-500">
            {{ filePreview!.size }}
          </p>
        </div>
        <button
          v-if="!disabled"
          class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
          type="button"
          @click="removeFile"
        >
          <X class="w-5 h-5 text-red-600 dark:text-red-400" />
        </button>
      </div>
    </div>
  </div>
</template>
