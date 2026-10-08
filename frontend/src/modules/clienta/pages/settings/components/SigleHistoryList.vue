<template>
  <div>
    <v-alert
      v-if="items.length === 0 && !loading"
      color="info"
      icon="mdi-information"
      variant="tonal"
    >
      Aucun historique de changement de sigle pour le moment.
    </v-alert>

    <div v-if="loading" class="text-center py-6">
      <v-progress-circular indeterminate />
    </div>

    <v-timeline v-else-if="items.length > 0" align="start" fill-dot side="end">
      <v-timeline-item
        v-for="entry in items"
        :key="entry.id"
        dot-color="primary"
        size="small"
      >
        <template #icon>
          <v-icon v-if="entry.is_reverted" color="warning" size="small">
            mdi-undo
          </v-icon>
          <v-icon v-else color="success" size="small">
            mdi-check-circle
          </v-icon>
        </template>

        <div class="timeline-content">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="font-weight-medium">
              {{ entry.old_sigle || "(aucun)" }} →
              <span class="text-primary">{{ entry.new_sigle }}</span>
            </span>
            <v-chip
              :color="getModeColor(entry.recoding_mode)"
              size="small"
              variant="tonal"
            >
              {{ getModeLabel(entry.recoding_mode) }}
            </v-chip>
          </div>

          <div class="text-caption text-medium-emphasis mb-2">
            <v-icon size="x-small">mdi-calendar</v-icon>
            {{ formatDate(entry.created_at) }}
          </div>

          <div v-if="entry.reason" class="text-caption mb-2">
            <strong>Raison :</strong> {{ entry.reason }}
          </div>

          <div class="text-caption mb-2">
            <strong>Équipements affectés :</strong>
            {{ entry.equipements_affected }}
          </div>

          <div v-if="entry.is_reverted" class="text-caption text-warning">
            <v-icon size="x-small">mdi-alert</v-icon>
            Annulé{{ entry.reverted_by ? " par admin" : "" }}
          </div>
        </div>
      </v-timeline-item>
    </v-timeline>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import {
    enterpriseSigleService,
    type SigleHistoryEntry,
  } from '@/services/enterpriseSigleService'

  const props = defineProps<{
    enterpriseId: number
  }>()

  const items = ref<SigleHistoryEntry[]>([])
  const loading = ref(true)

  async function loadSigleHistory () {
    loading.value = true
    try {
      const data = await enterpriseSigleService.getSigleHistory(
        props.enterpriseId,
      )
      items.value = data
    } catch (error) {
      console.error('Error loading sigle history:', error)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    void loadSigleHistory()
  })

  function formatDate (dateString: string): string {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date)
  }

  function getModeLabel (mode: string): string {
    return mode === 'auto' ? 'Recodifié' : 'Changement uniquement'
  }

  function getModeColor (mode: string): string {
    return mode === 'auto' ? 'warning' : 'info'
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
