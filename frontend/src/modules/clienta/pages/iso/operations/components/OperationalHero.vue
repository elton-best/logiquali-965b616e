<template>
  <v-card class="hero mb-6" elevation="0" rounded="xl">
    <v-card-text class="pa-6 pa-md-8">
      <div class="d-flex flex-wrap justify-space-between ga-6 align-center">
        <div>
          <div class="text-overline hero-kicker mb-2">Pilotage Opérationnel</div>
          <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
            Tableau de planification intelligent
          </h2>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Vision consolidée des actions du site, puis pilotage des projets en mode opérationnel.
          </p>
        </div>
        <div class="d-flex ga-3 flex-wrap">
          <v-card class="kpi-card" rounded="lg" variant="flat">
            <v-card-text class="px-4 py-3">
              <div class="text-caption text-medium-emphasis">Actions consolidées</div>
              <div class="text-h5 font-weight-bold">{{ consolidatedCount }}</div>
            </v-card-text>
          </v-card>
          <v-card class="kpi-card" rounded="lg" variant="flat">
            <v-card-text class="px-4 py-3">
              <div class="text-caption text-medium-emphasis">Projets actifs</div>
              <div class="text-h5 font-weight-bold">{{ projectCount }}</div>
            </v-card-text>
          </v-card>
          <v-card class="kpi-card" rounded="lg" variant="flat">
            <v-card-text class="px-4 py-3">
              <div class="text-caption text-medium-emphasis">Échéances proches (7j)</div>
              <div class="text-h5 font-weight-bold">{{ nearDueCount }}</div>
            </v-card-text>
          </v-card>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  defineProps<{
    consolidatedCount: number
    projectCount: number
    nearDueCount: number
  }>()
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 420px at 0% -10%, rgba(10, 132, 255, 0.18), transparent 60%),
    radial-gradient(900px 360px at 100% 0%, rgba(0, 184, 148, 0.16), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.6));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-kicker {
  letter-spacing: 0.12em;
  color: rgba(15, 23, 42, 0.68);
}

.kpi-card {
  min-width: 160px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.85);
}
</style>
