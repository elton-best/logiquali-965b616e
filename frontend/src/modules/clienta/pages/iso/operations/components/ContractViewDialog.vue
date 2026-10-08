<template>
  <v-dialog v-model="dialogValue" max-width="1100">
    <v-card rounded="xl">
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Contrat prestataire: {{ provider?.designation }}</span>
        <v-btn variant="text" @click="emit('close')">Fermer</v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text v-if="url" class="pdf-viewer-wrapper">
        <iframe class="pdf-viewer" :src="url" title="Visualisation contrat prestataire" />
      </v-card-text>
      <v-card-text v-else class="preview-panel">
        <iframe
          v-if="safePreviewHtml"
          class="preview-frame"
          sandbox=""
          :srcdoc="safePreviewHtml"
          title="Aperçu contrat sécurisé"
        />
        <div v-else class="text-body-2 text-medium-emphasis">
          Aucun contrat disponible.
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn
          v-if="url"
          color="primary"
          :href="url"
          prepend-icon="mdi-download"
          target="_blank"
          variant="outlined"
        >
          Télécharger PDF
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { ProviderPartner } from '@/api/services/providerPartners.service'
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    provider: {
      type: Object as PropType<ProviderPartner | null>,
      default: null,
    },
    html: {
      type: String,
      required: true,
    },
    url: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'close'): void
  }>()

  const dialogValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  function sanitizeHtmlForPreview (rawHtml: string): string {
    let sanitized = String(rawHtml || '')

    sanitized = sanitized
      .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
      .replace(/<iframe[\s\S]*?>[\s\S]*?<\/iframe>/gi, '')
      .replace(/<object[\s\S]*?>[\s\S]*?<\/object>/gi, '')
      .replace(/<embed[\s\S]*?>[\s\S]*?<\/embed>/gi, '')
      .replace(/<form[\s\S]*?>[\s\S]*?<\/form>/gi, '')
      .replace(/\son\w+\s*=\s*(['"]).*?\1/gi, '')
      .replace(/\son\w+\s*=\s*[^\s>]+/gi, '')
      .replace(/\s(href|src)\s*=\s*(['"])\s*javascript:[\s\S]*?\2/gi, ' $1="#"')
      .replace(/<meta[\s\S]*?>/gi, '')
      .replace(/<base[\s\S]*?>/gi, '')
      .replace(/<link[\s\S]*?>/gi, '')

    return sanitized.trim()
  }

  const safePreviewHtml = computed(() => {
    const htmlContent = sanitizeHtmlForPreview(props.html || '')
    if (!htmlContent) {
      return ''
    }

    return `<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      margin: 0;
      padding: 16px;
      font-family: Arial, sans-serif;
      line-height: 1.5;
      color: #111827;
      background: #ffffff;
    }
  </style>
</head>
<body>${htmlContent}</body>
</html>`
  })
</script>

<style scoped>
.preview-panel {
  min-height: 280px;
  padding: 0;
}

.preview-frame {
  width: 100%;
  min-height: 340px;
  border: 0;
}
</style>
