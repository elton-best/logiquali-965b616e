<template>
  <v-card
    class="communication-card"
    :elevation="hover ? 4 : 1"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
  >
    <v-card-text class="pa-4">
      <div class="d-flex align-center justify-space-between mb-3">
        <div class="d-flex align-center gap-2">
          <v-chip color="primary" size="x-small" variant="flat">
            #{{ communication.numero }}
          </v-chip>
          <v-chip :color="typeColor" size="x-small" variant="tonal">
            {{ typeLabel }}
          </v-chip>
          <StatusBadge
            :is-incomplete="!communication.dateDebut || !communication.dateFin"
            :status="communication.status"
          />
        </div>
        <AlertIndicator :date-debut="communication.dateDebut" />
      </div>

      <h3 class="text-h6 mb-2 text-primary">
        {{ communication.designation }}
      </h3>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <v-chip
          v-for="cible in communication.cibles"
          :key="cible"
          color="primary"
          size="small"
          variant="outlined"
        >
          {{ cible }}
        </v-chip>
      </div>

      <v-divider class="my-3" />

      <div class="info-grid">
        <div class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-account-tie</v-icon>
          <span class="text-body-2 text-grey-darken-1">{{ communication.responsable }}</span>
        </div>

        <div class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-calendar-range</v-icon>
          <span class="text-body-2 text-grey-darken-1">
            <span v-if="communication.dateDebut && communication.dateFin">
              {{ formatDate(communication.dateDebut) }} - {{ formatDate(communication.dateFin) }}
            </span>
            <span v-else class="text-grey">À compléter</span>
          </span>
        </div>

        <div v-if="communication.cout" class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-wallet</v-icon>
          <span class="text-body-2 text-grey-darken-1">{{ formatCurrency(communication.cout) }}</span>
        </div>

        <div class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-repeat</v-icon>
          <span class="text-body-2 text-grey-darken-1">{{ frequencyLabel }}</span>
        </div>
      </div>

      <v-progress-linear
        v-if="communication.proofs.length > 0"
        class="mt-3"
        color="success"
        height="4"
        :model-value="100"
        rounded
      />
    </v-card-text>

    <v-card-actions class="px-4 pb-4 pt-0">
      <v-btn
        color="primary"
        size="small"
        variant="tonal"
        @click="$emit('view', communication)"
      >
        Détails
      </v-btn>

      <v-btn
        v-if="canEdit"
        color="primary"
        icon="mdi-pencil"
        size="small"
        variant="text"
        @click="$emit('edit', communication)"
      />

      <v-spacer />

      <v-btn
        v-if="canComplete"
        color="success"
        size="small"
        variant="tonal"
        @click="$emit('complete', communication)"
      >
        Confirmer
      </v-btn>

      <v-btn
        v-if="canReschedule"
        color="warning"
        size="small"
        variant="text"
        @click="$emit('reschedule', communication)"
      >
        Replanifier
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import type { Communication } from '../../types/communication.types'
  import { computed, ref } from 'vue'
  import AlertIndicator from './AlertIndicator.vue'
  import StatusBadge from './StatusBadge.vue'

  interface Props {
    communication: Communication
  }

  const props = defineProps<Props>()
  defineEmits<{
    view: [communication: Communication]
    edit: [communication: Communication]
    complete: [communication: Communication]
    reschedule: [communication: Communication]
  }>()

  const hover = ref(false)

  const typeLabel = computed(() => props.communication.type === 'communication' ? 'Communication' : 'Sensibilisation')
  const typeColor = computed(() => props.communication.type === 'communication' ? 'info' : 'purple')

  const frequencyLabel = computed(() => {
    const labels: Record<string, string> = {
      ponctuelle: 'Ponctuelle',
      annuelle: 'Annuelle',
      semestrielle: 'Semestrielle',
      trimestrielle: 'Trimestrielle',
      mensuelle: 'Mensuelle',
      biennale: 'Biennale',
      sur_demande: 'Sur demande',
    }
    return labels[props.communication.frequency]
  })

  const canEdit = computed(() => {
    return ['planifiee', 'en_attente'].includes(props.communication.status)
  })

  const canComplete = computed(() => {
    if (!props.communication.dateDebut) return false
    const today = new Date()
    const startDate = new Date(props.communication.dateDebut)
    if (Number.isNaN(startDate.getTime())) return false
    return startDate <= today && props.communication.status === 'en_attente'
  })

  const canReschedule = computed(() => {
    return props.communication.status === 'en_attente' && !!props.communication.dateDebut
  })

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }

  function formatCurrency (amount: number) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
    }).format(amount)
  }
</script>

<style scoped>
.communication-card {
  border-radius: 16px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.communication-card:hover {
  transform: translateY(-2px);
}

.info-grid {
  display: grid;
  gap: 12px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.gap-2 {
  gap: 8px;
}
</style>
