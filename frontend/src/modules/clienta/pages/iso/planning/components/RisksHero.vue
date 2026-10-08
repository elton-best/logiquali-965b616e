<template>
  <v-card class="hero-card mb-6" elevation="0" rounded="xl">
    <v-card-text class="pa-5">
      <div class="d-flex flex-wrap ga-3 justify-space-between align-start">
        <div class="hero-main">
          <div class="text-overline mb-2 hero-kicker">Planification ISO</div>
          <h2 class="text-h5 text-md-h4 font-weight-bold mb-2 hero-title">
            {{ viewMode === 'risque' ? 'Cartographie des risques' : 'Cartographie des opportunités' }}
          </h2>
          <p class="text-body-2 mb-4 hero-subtitle">
            Visualisez les priorités, suivez les responsables et pilotez l'état d'avancement en un coup d'oeil.
          </p>
          <div class="d-flex flex-wrap ga-2 mb-3">
            <v-chip
              class="font-weight-medium"
              color="white"
              prepend-icon="mdi-counter"
              size="small"
              variant="flat"
            >
              {{ totalItems }} élément(s)
            </v-chip>
            <v-chip
              class="font-weight-medium"
              color="white"
              prepend-icon="mdi-alert-octagon-outline"
              size="small"
              variant="flat"
            >
              {{ highPriorityCount }} priorité(s) élevée(s)
            </v-chip>
            <v-chip
              class="font-weight-medium"
              color="white"
              prepend-icon="mdi-check-circle-outline"
              size="small"
              variant="flat"
            >
              Traitement {{ completionRate }}%
            </v-chip>
          </div>
          <div class="d-flex flex-wrap ga-2">
            <v-btn
              class="hero-action"
              color="white"
              prepend-icon="mdi-plus"
              size="small"
              @click="emit('add')"
            >
              Ajouter {{ viewMode === 'risque' ? 'un risque' : 'une opportunité' }}
            </v-btn>
            <v-btn
              class="hero-action"
              color="white"
              prepend-icon="mdi-file-excel"
              size="small"
              variant="outlined"
              @click="emit('export')"
            >
              Export Excel
            </v-btn>
          </div>
        </div>
        <div class="hero-side">
          <div class="hero-side-label mb-2">Vue active</div>
          <v-chip
            class="mb-3 font-weight-medium"
            :color="viewMode === 'risque' ? 'error' : 'success'"
            size="small"
            variant="flat"
          >
            {{ viewMode === 'risque' ? 'Mode risques actif' : 'Mode opportunités actif' }}
          </v-chip>
          <v-sheet class="hero-score pa-3" rounded="lg">
            <div class="d-flex justify-space-between align-center mb-1">
              <span class="text-caption text-medium-emphasis">{{ viewMode === 'risque' ? 'Criticité moyenne' : 'Priorité moyenne' }}</span>
              <v-chip :color="scoreColor(Number(avgCriticite))" size="x-small" variant="tonal">{{ averagePriorityLabel }}</v-chip>
            </div>
            <div class="text-h5 font-weight-bold mb-2">{{ avgCriticite }}</div>
            <v-progress-linear
              :color="scoreColor(Number(avgCriticite))"
              height="8"
              :model-value="Math.min(100, Math.round((Number(avgCriticite) / 25) * 100))"
              rounded
            />
          </v-sheet>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    viewMode: {
      type: String as PropType<'risque' | 'opportunite'>,
      required: true,
    },
    totalItems: {
      type: Number,
      required: true,
    },
    highPriorityCount: {
      type: Number,
      required: true,
    },
    completionRate: {
      type: Number,
      required: true,
    },
    avgCriticite: {
      type: Number,
      required: true,
    },
    averagePriorityLabel: {
      type: String,
      required: true,
    },
    scoreColor: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
    (event: 'export'): void
  }>()
</script>

<style scoped>
.hero-card {
  background: rgba(147, 197, 253, 0.35);
  backdrop-filter: blur(12px);
  color: rgb(var(--v-theme-on-surface));
  border: 1px solid rgba(59, 130, 246, 0.2);
  box-shadow: 0 4px 24px rgba(59, 130, 246, 0.12);
}

.hero-main {
  max-width: 720px;
}

.hero-kicker {
  opacity: 0.75;
  letter-spacing: 0.08em;
  color: rgb(var(--v-theme-on-surface));
  font-size: 0.7rem;
}

.hero-title {
  line-height: 1.15;
  color: rgb(var(--v-theme-on-surface));
}

.hero-subtitle {
  opacity: 0.8;
  max-width: 620px;
  color: rgb(var(--v-theme-on-surface));
}

.hero-action {
  font-weight: 700;
}

.hero-side {
  width: min(100%, 280px);
}

.hero-side-label {
  font-size: 0.75rem;
  opacity: 0.9;
  font-weight: 600;
}

.hero-score {
  background: rgba(255, 255, 255, 0.96);
  color: rgb(var(--v-theme-on-surface));
}
</style>
