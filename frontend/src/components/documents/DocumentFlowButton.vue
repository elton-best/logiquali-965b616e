<template>
  <div class="d-inline-flex ga-2 align-center flex-wrap">
    <!-- Générer brouillon -->
    <v-btn
      color="primary"
      :loading="exporting"
      prepend-icon="mdi-file-pdf-box"
      size="small"
      variant="outlined"
      @click="handlePreviewDraft"
    >
      Prévisualiser
    </v-btn>

    <v-btn
      color="primary"
      :disabled="!lastExportedDocumentId"
      :loading="exporting"
      prepend-icon="mdi-download"
      size="small"
      variant="outlined"
      @click="handleDownloadDraft"
    >
      Télécharger
    </v-btn>

    <!-- Soumettre (uniquement si document non approuvé/rejeté) -->
    <v-btn
      v-if="showSubmit"
      color="success"
      :disabled="verificationSent"
      :loading="submittingForVerification"
      prepend-icon="mdi-send"
      size="small"
      variant="flat"
      @click="openVerifyDialog"
    >
      {{ verificationSent ? 'Envoyé' : 'Soumettre' }}
    </v-btn>

    <!-- QR code — uniquement si document approuvé -->
    <v-btn
      v-if="approvedQrHash"
      color="grey"
      prepend-icon="mdi-qrcode"
      :to="`/qr-verify/${approvedQrHash}`"
      size="small"
      target="_blank"
      variant="text"
    >
      Vérifier
    </v-btn>

    <!-- Dialog prévisualisation -->
    <v-dialog v-model="previewDialog" max-width="900">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>{{ previewFilename }}</span>
          <v-btn icon size="small" variant="text" @click="closePreview">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text class="pa-0">
          <iframe
            v-if="previewBlobUrl"
            class="w-100 border-0"
            :src="previewBlobUrl"
            style="height:70vh"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Dialog confirmation code -->
    <v-dialog v-model="verifyDialog" max-width="480">
      <v-card>
        <v-card-title>Confirmer la soumission</v-card-title>
        <v-card-text>
          <p class="mb-3">Le document sera soumis à vérification avec le code :</p>
          <v-chip color="primary" size="large" variant="outlined">
            {{ exportedDocumentCode }}
          </v-chip>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="verifyDialog = false">Annuler</v-btn>
          <v-btn color="success" :loading="submittingForVerification" variant="flat" @click="submitForVerification">
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { type GenerateDraftFn, useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'

  const props = defineProps<{
    generateDraftFn: GenerateDraftFn
    downloadFilename?: string
    /** metadata.approved_qr_hash du document, pour afficher le bouton QR */
    approvedQrHash?: string | null
    /** Cacher le bouton Soumettre si document déjà approuvé */
    workflowStatus?: string | null
  }>()

  const showSubmit = computed(() =>
    !props.workflowStatus || !['approved', 'pending_verification', 'pending_approval'].includes(props.workflowStatus),
  )

  const {
    exporting,
    submittingForVerification,
    verificationSent,
    lastExportedDocumentId,
    exportedDocumentCode,
    previewDialog,
    previewBlobUrl,
    previewFilename,
    verifyDialog,
    handlePreviewDraft,
    closePreview,
    handleDownloadDraft,
    openVerifyDialog,
    submitForVerification,
  } = useDocumentFlow(props.generateDraftFn, props.downloadFilename ?? 'document.pdf')
</script>
