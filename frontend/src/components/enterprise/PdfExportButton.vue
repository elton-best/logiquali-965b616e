<template>
  <div>
    <v-btn
      :color="color"
      :loading="loading"
      :size="size"
      @click="openDialog"
    >
      <v-icon start>mdi-file-pdf-box</v-icon>
      {{ label }}
    </v-btn>

    <v-dialog v-model="dialog" max-width="800">
      <v-card>
        <v-card-title>Exporter en PDF</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedLayout"
            item-title="label"
            item-value="value"
            :items="layouts"
            label="Modèle"
          />

          <v-card class="mt-4" variant="outlined">
            <v-card-subtitle>Aperçu</v-card-subtitle>
            <v-card-text>
              <v-progress-linear v-if="previewLoading" color="primary" indeterminate />
              <iframe
                v-else
                class="preview-content preview-frame"
                sandbox=""
                :srcdoc="safePreviewHtml"
                title="Aperçu PDF sécurisé"
              />
            </v-card-text>
          </v-card>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialog = false">Annuler</v-btn>
          <v-btn color="primary" :loading="exporting" @click="exportPdf()">
            Télécharger
          </v-btn>
          <v-btn
            color="warning"
            :disabled="submittingForVerification"
            :loading="submittingForVerification"
            prepend-icon="mdi-shield-check"
            variant="outlined"
            @click="submitForVerification"
          >
            Vérifier document
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import api from '@/api/client'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { type PdfExportPayload, pdfExportService } from '@/services/pdfExportService'

  interface Props {
    documentType: string
    documentId: number
    content: string
    title: string
    label?: string
    color?: string
    size?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    label: 'Exporter PDF',
    color: 'primary',
    size: 'default',
  })

  const { showSuccess, showError } = useSnackbar()
  const loading = ref(false)
  const exporting = ref(false)
  const dialog = ref(false)
  const selectedLayout = ref<string>('professional')
  const previewHtml = ref('')
  const previewLoading = ref(false)
  const lastExportedDocumentId = ref<number | null>(null)
  const exportedDocumentCode = ref('')
  const submittingForVerification = ref(false)

  const layouts = [
    { value: 'professional', label: 'Professionnel' },
    { value: 'minimal', label: 'Minimal' },
  ]

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

    return sanitized
  }

  const safePreviewHtml = computed(() => {
    const html = sanitizeHtmlForPreview(previewHtml.value || props.content)

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
<body>${html}</body>
</html>`
  })

  async function openDialog () {
    dialog.value = true
    await loadPreview()
  }

  async function loadPreview () {
    previewLoading.value = true
    try {
      const payload: PdfExportPayload = {
        document_type: props.documentType,
        document_id: props.documentId,
        content: {
          html: props.content,
          title: props.title,
        },
        layout: selectedLayout.value as any,
      }

      previewHtml.value = await pdfExportService.previewDocument(payload)
    } catch {
      previewHtml.value = props.content
      showError('Erreur lors du chargement de l’aperçu')
    } finally {
      previewLoading.value = false
    }
  }

  async function exportPdf (download = true): Promise<boolean> {
    exporting.value = true
    try {
      const payload: PdfExportPayload = {
        document_type: props.documentType,
        document_id: props.documentId,
        content: {
          html: props.content,
          title: props.title,
        },
        layout: selectedLayout.value as any,
      }

      const { blob, generatedDocumentId } = await pdfExportService.exportDocumentWithMeta(payload)
      if (generatedDocumentId) {
        lastExportedDocumentId.value = generatedDocumentId
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        exportedDocumentCode.value = String(docResponse.data?.data?.code || docResponse.data?.code || '')
      }
      if (download) {
        const filename = `${props.title.replace(/[^a-z0-9]/gi, '_')}_${new Date().toISOString().split('T')[0]}.pdf`
        pdfExportService.downloadPdf(blob, filename)

        showSuccess('PDF téléchargé avec succès')
      }

      return Boolean(generatedDocumentId)
    } catch {
      showError('Erreur lors de l\'export PDF')
      return false
    } finally {
      exporting.value = false
    }
  }

  async function submitForVerification () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      const exported = await exportPdf(false)
      if (!exported || !lastExportedDocumentId.value || !exportedDocumentCode.value) {
        showError('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }

    try {
      submittingForVerification.value = true
      await api.post(`/documents/${lastExportedDocumentId.value}/confirm-code`, {
        needs_verification: true,
        confirmed_code: exportedDocumentCode.value,
      })
      showSuccess('Document envoyé pour vérification.')
      lastExportedDocumentId.value = null
      exportedDocumentCode.value = ''
      dialog.value = false
    } catch {
      showError('Échec de la soumission pour vérification.')
    } finally {
      submittingForVerification.value = false
    }
  }

  watch(selectedLayout, () => {
    if (dialog.value) {
      loadPreview()
    }
  })
</script>

<style scoped>
.preview-content {
  max-height: 400px;
  background: #f5f5f5;
  border-radius: 4px;
}

.preview-frame {
  width: 100%;
  min-height: 400px;
  border: 0;
}
</style>
