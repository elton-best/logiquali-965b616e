<template>
  <v-card-text class="pa-6">
    <v-alert type="info" variant="tonal">
      Les risques et opportunités sont gérés via le module dédié.
      Cette section affiche uniquement les liens existants.
    </v-alert>

    <v-row v-if="hasItems" class="mt-4">
      <v-col v-for="item in process.risks_opportunities" :key="item.id" cols="12" md="6">
        <v-card variant="outlined">
          <v-card-text>
            <div class="d-flex align-center gap-2 mb-2">
              <v-icon :color="item.type === 'risque' ? 'error' : 'success'">
                {{ item.type === 'risque' ? 'mdi-alert' : 'mdi-lightbulb' }}
              </v-icon>
              <span class="font-weight-bold">{{ item.code }}</span>
              <v-chip :color="getCriticalityColor(item.niveau)" size="x-small">
                {{ item.niveau }}
              </v-chip>
            </div>
            <div class="text-body-2">{{ item.title }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-alert v-else class="mt-4" type="info" variant="tonal">
      Aucun risque ou opportunité lié à ce processus
    </v-alert>
  </v-card-text>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  type RiskOpportunity = {
    id: number | string
    type: 'risque' | 'opportunite' | string
    code?: string
    niveau?: string
    title?: string
  }

  type ProcessData = {
    risks_opportunities?: RiskOpportunity[]
  }

  const props = defineProps<{
    process: ProcessData
    getCriticalityColor: (level: string) => string
  }>()

  const hasItems = computed(() =>
    Array.isArray(props.process.risks_opportunities)
    && props.process.risks_opportunities.length > 0,
  )
</script>
