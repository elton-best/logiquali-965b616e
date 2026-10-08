/**
 * FileUpload Component
 * Drag & drop file upload with preview
 */

<template>
  <div class="w-full">
    <div
      class="relative border-2 border-dashed rounded-lg transition-all duration-300"
      :class="[
        isDragging
          ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/20'
          : 'border-neutral-300 dark:border-neutral-700 hover:border-primary-400',
        error ? 'border-red-500' : ''
      ]"
      @dragleave.prevent="isDragging = false"
      @dragover.prevent="isDragging = true"
      @drop.prevent="handleDrop"
    >
      <!-- Upload Area -->
      <label
        v-if="!file"
        class="flex flex-col items-center justify-center px-6 py-8 cursor-pointer"
      >
        <Upload
          class="w-12 h-12 mb-4 transition-colors"
          :class="isDragging ? 'text-primary-500' : 'text-neutral-400'"
        />

        <p class="mb-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
          <span class="text-primary-600 dark:text-primary-400">Cliquez pour sélectionner</span>
          <span class="text-neutral-500"> ou glissez-déposez</span>
        </p>

        <p class="text-xs text-neutral-500 dark:text-neutral-400">
          {{ acceptLabel || `${accept} (Max ${maxSizeMB}MB)` }}
        </p>

        <input
          ref="fileInput"
          :accept="accept"
          class="hidden"
          type="file"
          @change="handleFileSelect"
        >
      </label>

      <!-- File Preview -->
      <div v-else class="p-4">
        <div class="flex items-center justify-between gap-4 p-4 bg-neutral-50 dark:bg-neutral-800 rounded-lg">
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="flex-shrink-0">
              <FileText v-if="isPDF" class="w-8 h-8 text-red-500" />
              <Image v-else-if="isImage" class="w-8 h-8 text-blue-500" />
              <File v-else class="w-8 h-8 text-neutral-500" />
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                {{ file.name }}
              </p>
              <p class="text-xs text-neutral-500">
                {{ formatFileSize(file.size) }}
              </p>
            </div>
          </div>

          <button
            class="flex-shrink-0 p-2 text-neutral-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors"
            type="button"
            @click="removeFile"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Image Preview -->
        <div v-if="isImage && previewUrl" class="mt-4">
          <img
            :alt="file.name"
            class="max-h-48 mx-auto rounded-lg border border-neutral-200 dark:border-neutral-700"
            :src="previewUrl"
          >
        </div>
      </div>

      <!-- Upload Progress -->
      <div v-if="uploading" class="absolute inset-0 bg-white/90 dark:bg-neutral-900/90 flex items-center justify-center rounded-lg">
        <div class="text-center">
          <Loader2 class="w-8 h-8 animate-spin text-primary-500 mx-auto mb-2" />
          <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
            Téléchargement... {{ uploadProgress }}%
          </p>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
      {{ error }}
    </p>

    <!-- Helper Text -->
    <p v-else-if="helperText" class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { File, FileText, Image, Loader2, Upload, X } from 'lucide-vue-next'
  import { computed, ref, watch } from 'vue'

  interface Props {
    modelValue?: File | null
    accept?: string
    maxSizeMB?: number
    acceptLabel?: string
    helperText?: string
    uploading?: boolean
    uploadProgress?: number
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    accept: '*',
    maxSizeMB: 10,
    uploading: false,
    uploadProgress: 0,
  })

  const emit = defineEmits<{
    'update:modelValue': [file: File | null]
    'error': [message: string]
  }>()

  const fileInput = ref<HTMLInputElement>()
  const isDragging = ref(false)
  const file = ref<File | null>(props.modelValue)
  const previewUrl = ref<string>()
  const error = ref<string>()

  const isPDF = computed(() => file.value?.type === 'application/pdf')
  const isImage = computed(() => file.value?.type.startsWith('image/'))

  watch(() => props.modelValue, newValue => {
    file.value = newValue
    if (newValue && isImage.value) {
      createPreview(newValue)
    }
  })

  function handleFileSelect (event: Event) {
    const target = event.target as HTMLInputElement
    const selectedFile = target.files?.[0]
    if (selectedFile) {
      validateAndSetFile(selectedFile)
    }
  }

  function handleDrop (event: DragEvent) {
    isDragging.value = false
    const droppedFile = event.dataTransfer?.files[0]
    if (droppedFile) {
      validateAndSetFile(droppedFile)
    }
  }

  function validateAndSetFile (selectedFile: File) {
    error.value = undefined

    // Check file size
    const maxSizeBytes = props.maxSizeMB * 1024 * 1024
    if (selectedFile.size > maxSizeBytes) {
      error.value = `Le fichier est trop volumineux. Taille maximale : ${props.maxSizeMB}MB`
      emit('error', error.value)
      return
    }

    // Check file type
    if (props.accept !== '*') {
      const acceptedTypes = props.accept.split(',').map(t => t.trim())
      const fileExtension = '.' + selectedFile.name.split('.').pop()?.toLowerCase()
      const isAccepted = acceptedTypes.some(type => {
        if (type.startsWith('.')) {
          return fileExtension === type
        }
        return selectedFile.type.match(type.replace('*', '.*'))
      })

      if (!isAccepted) {
        error.value = `Type de fichier non accepté. Formats acceptés : ${props.accept}`
        emit('error', error.value)
        return
      }
    }

    file.value = selectedFile
    emit('update:modelValue', selectedFile)

    if (isImage.value) {
      createPreview(selectedFile)
    }
  }

  function createPreview (imageFile: File) {
    const reader = new FileReader()
    reader.addEventListener('load', e => {
      previewUrl.value = e.target?.result as string
    })
    reader.readAsDataURL(imageFile)
  }

  function removeFile () {
    file.value = null
    previewUrl.value = undefined
    error.value = undefined
    emit('update:modelValue', null)
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
</script>
