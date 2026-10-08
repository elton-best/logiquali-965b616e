/**
 * useDocumentFlow
 *
 * Encapsule le flow commun : générer brouillon → prévisualiser → télécharger → soumettre en vérification.
 *
 * @param generateDraftFn  Fonction qui appelle l'endpoint generate-draft spécifique à la page.
 *                         Doit retourner { id, code } du document créé, ou null en cas d'échec.
 * @param downloadFilename Nom de fichier suggéré pour le téléchargement.
 */

import { ref, watch } from 'vue'
import api from '@/api/client'
import documentService from '@/services/documentService'
import { useToast } from '@/modules/shared/composables/useToast'

export type GenerateDraftFn = () => Promise<{ id: number, code: string } | null>

export function useDocumentFlow (
  generateDraftFn: GenerateDraftFn,
  downloadFilename: string | (() => string) = 'document.pdf',
) {
  const toast = useToast()

  // ── État ──────────────────────────────────────────────────────────────────
  const exporting = ref(false)
  const submittingForVerification = ref(false)
  const verificationSent = ref(false)
  const lastExportedDocumentId = ref<number | null>(null)
  const exportedDocumentCode = ref('')

  // Dialog prévisualisation
  const previewDialog = ref(false)
  const previewBlobUrl = ref<string | null>(null)
  const previewFilename = ref('')

  // Dialog vérification
  const verifyDialog = ref(false)

  // ── Helpers ───────────────────────────────────────────────────────────────
  function resetDraft () {
    lastExportedDocumentId.value = null
    exportedDocumentCode.value = ''
    verificationSent.value = false
  }

  async function ensureDraftReady (): Promise<boolean> {
    if (lastExportedDocumentId.value && exportedDocumentCode.value) return true

    exporting.value = true
    try {
      const result = await generateDraftFn()
      if (!result?.id) {
        toast.error('Impossible de générer le brouillon.')
        return false
      }
      lastExportedDocumentId.value = result.id
      exportedDocumentCode.value = result.code
      return true
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors de la génération du brouillon.')
      return false
    } finally {
      exporting.value = false
    }
  }

  function downloadBlob (blob: Blob, filename: string) {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.append(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  }

  // ── Actions publiques ─────────────────────────────────────────────────────
  async function handlePreviewDraft () {
    const ready = await ensureDraftReady()
    if (!ready || !lastExportedDocumentId.value) return

    exporting.value = true
    try {
      const blob = await documentService.preview(lastExportedDocumentId.value)
      if (previewBlobUrl.value) URL.revokeObjectURL(previewBlobUrl.value)
      previewBlobUrl.value = URL.createObjectURL(blob)
      previewFilename.value = typeof downloadFilename === 'function' ? downloadFilename() : downloadFilename
      previewDialog.value = true
    } catch {
      toast.error('Erreur lors de la prévisualisation.')
    } finally {
      exporting.value = false
    }
  }

  function closePreview () {
    previewDialog.value = false
    if (previewBlobUrl.value) {
      URL.revokeObjectURL(previewBlobUrl.value)
      previewBlobUrl.value = null
    }
  }

  async function handleDownloadDraft () {
    const ready = await ensureDraftReady()
    if (!ready || !lastExportedDocumentId.value) return

    exporting.value = true
    try {
      const blob = await documentService.download(lastExportedDocumentId.value)
      const filename = typeof downloadFilename === 'function' ? downloadFilename() : downloadFilename
      downloadBlob(blob, filename)
      toast.success('Téléchargement prêt')
    } catch {
      toast.error('Erreur lors du téléchargement.')
    } finally {
      exporting.value = false
    }
  }

  function openVerifyDialog () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      // Déclencher la génération puis ouvrir le dialog
      ensureDraftReady().then(ready => { if (ready) verifyDialog.value = true })
      return
    }
    verifyDialog.value = true
  }

  async function submitForVerification () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      const ready = await ensureDraftReady()
      if (!ready) return
    }
    submittingForVerification.value = true
    try {
      await api.post(`/documents/${lastExportedDocumentId.value}/confirm-code`, {
        needs_verification: true,
        confirmed_code: exportedDocumentCode.value,
      })
      verifyDialog.value = false
      toast.success('Document envoyé pour vérification.')
      verificationSent.value = true
      lastExportedDocumentId.value = null
      exportedDocumentCode.value = ''
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Échec de la soumission pour vérification.')
    } finally {
      submittingForVerification.value = false
    }
  }

  return {
    // État
    exporting,
    submittingForVerification,
    verificationSent,
    lastExportedDocumentId,
    exportedDocumentCode,
    // Dialog prévisualisation
    previewDialog,
    previewBlobUrl,
    previewFilename,
    // Dialog vérification
    verifyDialog,
    // Actions
    ensureDraftReady,
    handlePreviewDraft,
    closePreview,
    handleDownloadDraft,
    openVerifyDialog,
    submitForVerification,
    resetDraft,
  }
}
