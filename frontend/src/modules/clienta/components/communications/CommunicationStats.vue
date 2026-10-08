<template>
  <v-row dense>
    <v-col cols="12" lg="3" md="6">
      <AppWidget
        clickable
        :icon="Megaphone"
        title="Total communications"
        :value="stats.total"
        variant="primary"
      />
    </v-col>
    <v-col cols="12" lg="3" md="6">
      <AppWidget
        clickable
        :icon="CheckCircle"
        title="Réalisées"
        :value="stats.realisees"
        variant="success"
      >
        <template #subtitle>
          <div class="text-xs text-success-600 dark:text-success-400 font-medium mt-1">
            {{ stats.tauxRealisation }}% de réalisation
          </div>
        </template>
      </AppWidget>
    </v-col>
    <v-col cols="12" lg="3" md="6">
      <AppWidget
        clickable
        :icon="Clock"
        title="En retard"
        :value="stats.enRetard"
        variant="warning"
      />
    </v-col>
    <v-col cols="12" lg="3" md="6">
      <AppWidget
        clickable
        :icon="Wallet"
        title="Budget consommé"
        :value="formatCurrency(stats.budgetConsomme)"
        variant="info"
      >
        <template #subtitle>
          <div class="mt-2">
            <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-1">
              <div
                class="bg-info-600 h-1 rounded-full transition-all"
                :style="{ width: `${budgetPercentage}%` }"
              />
            </div>
          </div>
        </template>
      </AppWidget>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { CommunicationStats } from '../../types/communication.types'
  import { CheckCircle, Clock, Megaphone, Wallet } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'

  interface Props {
    stats: CommunicationStats
  }

  const props = defineProps<Props>()

  const budgetPercentage = computed(() => {
    if (props.stats.budgetTotal === 0) return 0
    return (props.stats.budgetConsomme / props.stats.budgetTotal) * 100
  })

  function formatCurrency (amount: number) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
      maximumFractionDigits: 0,
    }).format(amount)
  }
</script>
