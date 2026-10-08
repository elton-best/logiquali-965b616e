<template>
  <v-row>
    <v-col
      v-for="item in items"
      :key="item.id"
      cols="12"
      lg="4"
      md="6"
    >
      <v-card class="h-100 risk-item-card" :class="viewMode === 'risque' ? 'risk-item-card--risk' : 'risk-item-card--opportunity'" rounded="xl">
        <v-card-text class="pa-5">
          <div class="d-flex justify-space-between align-center mb-3">
            <v-chip :color="viewMode === 'risque' ? 'error' : 'success'" size="small" variant="flat">
              {{ viewMode === 'risque' ? 'RISQUE' : 'OPPORTUNITÉ' }} #{{ item.code }}
            </v-chip>
            <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
              {{ statusItems.find(s => s.value === item.status)?.label || item.status }}
            </v-chip>
          </div>

          <div class="text-caption text-medium-emphasis mb-2">Processus</div>
          <v-chip class="mb-4" color="primary" size="small" variant="tonal">{{ item.process_name }}</v-chip>

          <div class="text-body-1 font-weight-bold mb-3 clamp-two-lines">{{ item.description }}</div>

          <div class="risk-score-panel mb-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis">Score de priorité</span>
              <v-chip :color="scoreColor(item.score, viewMode)" size="small" variant="flat">{{ item.score }} / 25</v-chip>
            </div>
            <v-progress-linear
              :color="scoreColor(item.score, viewMode)"
              height="9"
              :model-value="Math.min(100, Math.round((Number(item.score || 0) / 25) * 100))"
              rounded
            />
          </div>

          <div class="d-flex ga-2 mb-4 flex-wrap">
            <v-chip size="x-small" variant="tonal">Probabilité: {{ item.probabilite }}</v-chip>
            <v-chip size="x-small" variant="tonal">{{ viewMode === 'risque' ? 'Gravité' : 'Pertinence' }}: {{ item.gravite }}</v-chip>
            <v-chip :color="scoreColor(item.score, viewMode)" size="x-small" variant="tonal">{{ priorityLabel(item.score) }}</v-chip>
          </div>

          <v-divider class="mb-3" />

          <div class="text-caption text-medium-emphasis mb-1">Responsable</div>
          <div class="text-body-2 mb-3">{{ item.responsable_name || 'Non assigné' }}</div>

          <div class="text-caption text-medium-emphasis mb-1">Action prévue</div>
          <div class="text-body-2 mb-4 clamp-two-lines">{{ item.actions_prevues || 'Aucune action précisée.' }}</div>

          <div class="d-flex justify-space-between align-center">
            <span class="text-caption text-medium-emphasis">{{ item.delai_frequence || 'Délai non défini' }}</span>
            <div class="d-flex">
              <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="emit('view', item.id)" />
              <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="emit('edit', item)" />
              <v-btn
                color="error"
                icon="mdi-delete-outline"
                size="small"
                variant="text"
                @click="emit('delete', item)"
              />
            </div>
          </div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    items: {
      type: Array as PropType<any[]>,
      required: true,
    },
    viewMode: {
      type: String as PropType<'risque' | 'opportunite'>,
      required: true,
    },
    statusItems: {
      type: Array as PropType<Array<{ label: string, value: string }>>,
      required: true,
    },
    statusColor: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
    scoreColor: {
      type: Function as PropType<(score: number, type?: 'risque' | 'opportunite') => string>,
      required: true,
    },
    priorityLabel: {
      type: Function as PropType<(score: number) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'view', id: number): void
    (event: 'edit', item: any): void
    (event: 'delete', item: any): void
  }>()
</script>
