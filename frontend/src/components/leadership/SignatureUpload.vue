<template>
  <div class="signature-upload">
    <label class="field-label">{{ label }}</label>

    <div
      :class="['signature-zone', { 'has-signature': signatureUrl }]"
      @click="triggerFileInput"
    >
      <input
        ref="fileInput"
        accept="image/png,image/jpeg,image/jpg"
        class="hidden"
        type="file"
        @change="handleFileChange"
      >

      <div v-if="!signatureUrl" class="signature-empty">
        <v-icon color="#5b8dd9" size="40">mdi-draw</v-icon>
        <div class="signature-text">Cliquez pour uploader votre signature</div>
        <div class="signature-formats">PNG, JPG • Max 2 MB</div>
      </div>

      <div v-else class="signature-preview">
        <img alt="Signature" class="signature-image" :src="signatureUrl">
        <button class="remove-signature" @click.stop="clearSignature">
          <v-icon size="18">mdi-close</v-icon>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'

  withDefaults(defineProps<{
    label?: string
  }>(), {
    label: 'Signature',
  })

  const emit = defineEmits<{
    'signature-uploaded': [file: File, url: string]
  }>()

  const signatureUrl = ref<string>('')
  const fileInput = ref<HTMLInputElement>()

  function triggerFileInput () {
    fileInput.value?.click()
  }

  function handleFileChange (event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
      const reader = new FileReader()
      reader.addEventListener('load', () => {
        signatureUrl.value = reader.result as string
        emit('signature-uploaded', file, signatureUrl.value)
      })
      reader.readAsDataURL(file)
    }
  }

  function clearSignature () {
    signatureUrl.value = ''
    if (fileInput.value) fileInput.value.value = ''
  }
</script>

<style scoped>
.signature-upload {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.signature-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background: #f8fafc;
  min-height: 150px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.signature-zone:hover {
  border-color: #5b8dd9;
  background: rgba(91, 141, 217, 0.05);
}

.signature-zone.has-signature {
  border-style: solid;
  border-color: #e2e8f0;
  padding: 16px;
  background: white;
}

.signature-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.signature-text {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1e293b;
}

.signature-formats {
  font-size: 0.75rem;
  color: #94a3b8;
}

.signature-preview {
  position: relative;
  width: 100%;
}

.signature-image {
  max-width: 100%;
  max-height: 120px;
  object-fit: contain;
}

.remove-signature {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: #ef4444;
  color: white;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.remove-signature:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.hidden {
  display: none;
}
</style>
