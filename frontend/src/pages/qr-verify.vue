<template>
  <div class="qr-verification pa-8">
    <v-container max-width="800">
      <v-card>
        <v-card-title class="text-center">
          <v-icon color="primary" size="64">mdi-qrcode-scan</v-icon>
          <h1 class="text-h4 mt-4">Vérification de document</h1>
        </v-card-title>

        <v-card-text v-if="loading" class="text-center py-8">
          <v-progress-circular color="primary" indeterminate size="64" />
          <p class="mt-4">Vérification en cours...</p>
        </v-card-text>

        <v-card-text v-else-if="error" class="text-center py-8">
          <v-icon color="error" size="64">mdi-alert-circle</v-icon>
          <h2 class="text-h5 mt-4 text-error">Document invalide</h2>
          <p class="mt-2">{{ error }}</p>
        </v-card-text>

        <v-card-text v-else-if="verification" class="py-8">
          <!-- Bandeau obsolète -->
          <v-alert
            v-if="isObsolete"
            class="mb-4"
            icon="mdi-archive-alert"
            type="warning"
            variant="tonal"
          >
            <strong>Document obsolète</strong> — Ce document a été remplacé par une version plus récente.
          </v-alert>

          <v-alert
            class="mb-4"
            :type="verification.valid ? 'success' : 'warning'"
            variant="tonal"
          >
            <v-icon size="48">{{ verification.valid ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            <h2 class="text-h5 mt-2">{{ verification.message }}</h2>
          </v-alert>

          <v-list>
            <v-list-item>
              <v-list-item-title>Document</v-list-item-title>
              <v-list-item-subtitle>{{ verification.document_title }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item v-if="verification.code">
              <v-list-item-title>Code</v-list-item-title>
              <v-list-item-subtitle>{{ verification.code }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item v-if="verification.version">
              <v-list-item-title>Version approuvée</v-list-item-title>
              <v-list-item-subtitle>v{{ verification.version }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Statut</v-list-item-title>
              <v-list-item-subtitle>
                <v-chip
                  :color="statusColor"
                  size="small"
                  variant="tonal"
                >
                  {{ statusLabel }}
                </v-chip>
              </v-list-item-subtitle>
            </v-list-item>
            <v-list-item v-if="verification.approved_at">
              <v-list-item-title>Date d'approbation</v-list-item-title>
              <v-list-item-subtitle>{{ formatDate(verification.approved_at) }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Entreprise</v-list-item-title>
              <v-list-item-subtitle>{{ verification.enterprise_name }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Nombre de vérifications</v-list-item-title>
              <v-list-item-subtitle>{{ verification.scan_count }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>
    </v-container>
  </div>
</template>

<script setup lang="ts">
  import type { QRCodeVerification } from '@/types/enterprise-config'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { qrCodeService } from '@/services/qrCodeService'

  const route = useRoute()
  const loading = ref(true)
  const error = ref<string | null>(null)
  const verification = ref<QRCodeVerification | null>(null)

  const isObsolete = computed(() => verification.value?.status === 'obsolete')

  const statusLabel = computed(() => {
    const map: Record<string, string> = {
      approved: 'Approuvé',
      obsolete: 'Obsolète',
      draft: 'Brouillon',
      pending_verification: 'En vérification',
      pending_approval: 'En approbation',
      rejected: 'Rejeté',
    }
    return map[verification.value?.workflow_status ?? ''] ?? (verification.value?.workflow_status ?? '—')
  })

  const statusColor = computed(() => {
    const map: Record<string, string> = {
      approved: 'success',
      obsolete: 'warning',
      draft: 'grey',
      pending_verification: 'info',
      pending_approval: 'info',
      rejected: 'error',
    }
    return map[verification.value?.workflow_status ?? ''] ?? 'grey'
  })

  function formatDate (date: string) {
    return new Date(date).toLocaleString('fr-FR')
  }

  async function verifyDocument () {
    const hash = (route.params as Record<string, unknown>).hash
    const hashValue = typeof hash === 'string' ? hash : ''
    if (!hashValue) {
      error.value = 'Code QR invalide'
      loading.value = false
      return
    }

    try {
      verification.value = await qrCodeService.verify(hashValue)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la vérification'
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    verifyDocument()
  })
</script>
