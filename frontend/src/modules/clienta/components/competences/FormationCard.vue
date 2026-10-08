<template>
  <v-card
    class="formation-card"
    :elevation="hover ? 4 : 1"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
  >
    <v-card-text class="pa-4">
      <div class="d-flex align-center justify-space-between mb-3">
        <div class="d-flex align-center gap-2">
          <v-chip color="primary" size="x-small" variant="flat">
            #{{ formation.numero }}
          </v-chip>
          <StatusBadge
            :is-incomplete="!formation.dateDebut || !formation.dateFin"
            :status="formation.status"
          />
        </div>
        <AlertIndicator
          :alert-state="formation.alertState"
          :date-debut="formation.dateDebut"
          :status="formation.status"
        />
      </div>

      <h3 class="text-h6 mb-2 text-primary">
        {{ formation.designation }}
      </h3>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <v-chip
          v-for="target in displayedTargets"
          :key="target.key"
          color="primary"
          size="small"
          variant="outlined"
        >
          {{ target.label }}
        </v-chip>
      </div>

      <v-divider class="my-3" />

      <div class="info-grid">
        <div class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-account-tie</v-icon>
          <span class="text-body-2 text-grey-darken-1">{{ formation.formateur }}</span>
        </div>

        <div class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-calendar-range</v-icon>
          <span class="text-body-2 text-grey-darken-1">
            <span v-if="formation.dateDebut && formation.dateFin">
              {{ formatDate(formation.dateDebut) }} - {{ formatDate(formation.dateFin) }}
            </span>
            <span v-else class="text-grey">À compléter</span>
          </span>
        </div>

        <div class="info-item">
          <v-icon color="grey-darken-1" size="small">mdi-repeat</v-icon>
          <span class="text-body-2 text-grey-darken-1">{{ frequencyLabel }}</span>
        </div>
      </div>

      <v-progress-linear
        v-if="formation.proofs.length > 0"
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
        @click="$emit('view', formation)"
      >
        Détails
      </v-btn>

      <v-btn
        color="info"
        size="small"
        variant="text"
        @click="$emit('track', formation)"
      >
        Suivi
      </v-btn>

      <v-btn
        v-if="canEdit"
        color="primary"
        icon="mdi-pencil"
        size="small"
        variant="text"
        @click="$emit('edit', formation)"
      />

      <v-spacer />

      <v-btn
        v-if="canComplete"
        color="success"
        size="small"
        variant="tonal"
        @click="$emit('complete', formation)"
      >
        Confirmer
      </v-btn>

      <v-btn
        v-if="canReschedule"
        color="warning"
        size="small"
        variant="text"
        @click="$emit('reschedule', formation)"
      >
        Replanifier
      </v-btn>

      <v-btn
        v-if="canDelete"
        color="error"
        size="small"
        variant="text"
        @click="$emit('delete', formation)"
      >
        Supprimer
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import type { Formation } from '../../types/formation.types'
  import { computed, ref } from 'vue'
  import AlertIndicator from './AlertIndicator.vue'
  import StatusBadge from './StatusBadge.vue'

  interface Props {
    formation: Formation
  }

  const props = defineProps<Props>()
  defineEmits<{
    view: [formation: Formation]
    track: [formation: Formation]
    edit: [formation: Formation]
    complete: [formation: Formation]
    reschedule: [formation: Formation]
    delete: [formation: Formation]
  }>()

  const hover = ref(false)

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
    return labels[props.formation.frequency]
  })

  const displayedTargets = computed(() => {
    if (Array.isArray(props.formation.targets) && props.formation.targets.length > 0) {
      return props.formation.targets.map(t => ({ key: `u-${t.id}`, label: t.name }))
    }

    return (props.formation.cibles || []).map(cible => ({ key: `l-${cible}`, label: cible }))
  })

  const canEdit = computed(() => {
    return ['planifiee', 'en_attente'].includes(props.formation.status)
  })

  const canComplete = computed(() => {
    if (!props.formation.dateDebut) return false
    const today = new Date()
    const startDate = new Date(props.formation.dateDebut)
    if (Number.isNaN(startDate.getTime())) return false
    return startDate <= today && ['planifiee', 'en_attente', 'replanifiee'].includes(props.formation.status)
  })

  const canReschedule = computed(() => {
    if (!props.formation.dateDebut && !props.formation.dateFin) return false
    const today = new Date()
    const endDate = new Date(props.formation.dateFin || props.formation.dateDebut || '')
    if (Number.isNaN(endDate.getTime())) return false
    return endDate <= today && ['planifiee', 'en_attente', 'replanifiee'].includes(props.formation.status)
  })

  const canDelete = computed(() => {
    return !['realisee', 'annulee'].includes(props.formation.status)
  })

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }

</script>

<style scoped>
.formation-card {
  border-radius: 16px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.formation-card:hover {
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
