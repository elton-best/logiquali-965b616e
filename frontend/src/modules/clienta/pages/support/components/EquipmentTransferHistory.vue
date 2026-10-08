<template>
  <div>
    <v-alert
      v-if="items.length === 0 && !loading"
      color="info"
      icon="mdi-information"
      variant="tonal"
    >
      Aucun transfert enregistré pour cet équipement.
    </v-alert>

    <div v-if="loading" class="text-center py-6">
      <v-progress-circular indeterminate />
    </div>

    <v-timeline v-else-if="items.length > 0" align="start" fill-dot side="end">
      <v-timeline-item
        v-for="transfer in items"
        :key="transfer.id"
        :dot-color="transfer.is_verified ? 'success' : 'warning'"
        size="small"
      >
        <template #icon>
          <v-icon v-if="transfer.is_verified" color="white" size="small">
            mdi-check-circle
          </v-icon>
          <v-icon v-else color="white" size="small"> mdi-clock-outline </v-icon>
        </template>

        <div class="timeline-content">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="font-weight-medium">
              <v-icon size="x-small">mdi-map-marker</v-icon>
              {{ transfer.previousSite?.name || "(inconnu)" }} →
              <span class="text-primary">{{ transfer.newSite?.name }}</span>
            </span>
            <v-chip
              :color="transfer.is_verified ? 'success' : 'warning'"
              size="small"
              variant="tonal"
            >
              {{ transfer.is_verified ? "Vérifié" : "En attente" }}
            </v-chip>
          </div>

          <div class="text-caption text-medium-emphasis mb-1">
            <v-icon size="x-small">mdi-calendar</v-icon>
            {{ formatDate(transfer.transferred_at) }}
          </div>

          <div class="text-caption mb-2">
            <strong>Raison :</strong>
            {{ transfer.transferReason?.name || transfer.transfer_reason_code }}
          </div>

          <div v-if="transfer.transfer_notes" class="text-caption mb-2">
            <strong>Notes :</strong> {{ transfer.transfer_notes }}
          </div>

          <div class="text-caption text-medium-emphasis">
            <v-icon size="x-small">mdi-account</v-icon>
            Transféré par {{ transfer.transferredByUser?.name || "(admin)" }}
          </div>

          <div
            v-if="transfer.is_verified"
            class="text-caption text-success mt-1"
          >
            <v-icon size="x-small">mdi-check</v-icon>
            Vérifié par {{ transfer.verifiedByUser?.name || "(admin)" }}
            <span v-if="transfer.verified_at">
              le {{ formatDate(transfer.verified_at) }}
            </span>
          </div>

          <div
            v-if="transfer.verification_notes"
            class="text-caption text-success mt-1"
          >
            <strong>Vérification :</strong> {{ transfer.verification_notes }}
          </div>
        </div>
      </v-timeline-item>
    </v-timeline>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, ref, watch } from 'vue'
  import {
    equipmentTransferService,
    type TransferHistoryEntry,
  } from '@/services/equipmentTransferService'

  const props = defineProps<{
    equipementId: number
  }>()

  const items = ref<TransferHistoryEntry[]>([])
  const loading = ref(true)

  async function loadTransfers () {
    loading.value = true
    try {
      const data = await equipmentTransferService.getEquipmentTransfers(
        props.equipementId,
      )
      items.value = data
    } catch (error) {
      console.error('Error loading transfer history:', error)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    void loadTransfers()
  })

  watch(
    () => props.equipementId,
    () => {
      void loadTransfers()
    },
  )

  function formatDate (dateString: string): string {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date)
  }
</script>

<style scoped lang="scss">
.timeline-content {
  padding: 12px;
  border-radius: 4px;
  background-color: rgba(0, 0, 0, 0.02);
  border-left: 3px solid rgba(0, 0, 0, 0.08);
}
</style>
