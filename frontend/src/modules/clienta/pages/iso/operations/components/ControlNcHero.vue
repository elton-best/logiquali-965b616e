<template>
  <v-card class="hero mb-6" elevation="0" rounded="xl">
    <v-card-text class="pa-6 pa-md-8">
      <div class="hero-grid">
        <div>
          <h2 class="text-h4 font-weight-black mb-2">
            Digitalisation des fiches opérationnelles
          </h2>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Fiche de traitement des non-conformités et fiche de traitement des plaintes/réclamations sur une interface unique.
          </p>
          <div class="hero-actions">
            <v-btn
              class="hero-help"
              color="primary"
              prepend-icon="mdi-information-outline"
              size="small"
              variant="tonal"
              @click="emit('help')"
            >
              Comment ça marche
            </v-btn>
          </div>
        </div>
        <div class="hero-kpis">
          <div class="hero-kpi">
            <span>NC ouvertes</span>
            <strong>{{ openNcCount }}</strong>
          </div>
          <div class="hero-kpi">
            <span>Réclamations</span>
            <strong>{{ reclamationCount }}</strong>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  defineProps({
    openNcCount: {
      type: Number,
      required: true,
    },
    reclamationCount: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'help'): void
  }>()
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1100px 420px at 0% -20%, rgba(244, 63, 94, 0.18), transparent 62%),
    radial-gradient(900px 300px at 100% -10%, rgba(14, 165, 233, 0.14), transparent 60%),
    linear-gradient(145deg, rgba(17, 24, 39, 0.03), rgba(255, 255, 255, 0.9));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 20px;
  align-items: center;
}

.hero-kpis {
  display: grid;
  grid-template-columns: repeat(2, minmax(130px, 1fr));
  gap: 10px;
}

.hero-kpi {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}

.hero-kpi span {
  font-size: 12px;
  color: rgba(15, 23, 42, 0.66);
}

.hero-kpi strong {
  font-size: 24px;
  color: #0f172a;
}

.hero-actions {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.hero-help {
  text-transform: none;
  font-weight: 600;
}

@media (max-width: 960px) {
  .hero-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .hero-kpis {
    grid-template-columns: 1fr;
  }
}
</style>
