<template>
  <div class="habilitations-view">
    <v-breadcrumbs :items="breadcrumbs" />

    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Habilitations</h1>
        <p class="text-body-1 text-medium-emphasis">Gestion des certifications et habilitations employés</p>
      </div>
      <div class="d-flex align-center gap-2">
        <v-chip :color="wsConnected ? 'success' : 'error'" size="small">
          <v-icon size="16">{{ wsConnected ? 'mdi-wifi' : 'mdi-wifi-off' }}</v-icon>
          {{ wsConnected ? 'Connecté' : 'Déconnecté' }}
        </v-chip>
      </div>
    </div>

    <!-- Stats Cards -->
    <v-row class="mb-6">
      <v-col cols="12" md="3">
        <v-card color="success" variant="tonal">
          <v-card-text class="text-center">
            <v-icon class="mb-2" size="40">mdi-check-circle</v-icon>
            <div class="text-h4">{{ stats.active }}</div>
            <div class="text-caption">Actives</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card color="warning" variant="tonal">
          <v-card-text class="text-center">
            <v-icon class="mb-2" size="40">mdi-clock-alert</v-icon>
            <div class="text-h4">{{ stats.expiring }}</div>
            <div class="text-caption">Expirent bientôt</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card color="error" variant="tonal">
          <v-card-text class="text-center">
            <v-icon class="mb-2" size="40">mdi-alert-circle</v-icon>
            <div class="text-h4">{{ stats.expired }}</div>
            <div class="text-caption">Expirées</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card color="info" variant="tonal">
          <v-card-text class="text-center">
            <v-icon class="mb-2" size="40">mdi-certificate</v-icon>
            <div class="text-h4">{{ stats.total }}</div>
            <div class="text-caption">Total</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Alerts -->
    <v-alert
      v-if="stats.expiring > 0"
      class="mb-4"
      closable
      :text="`${stats.expiring} habilitation(s) expirent dans les 30 prochains jours`"
      type="warning"
      variant="tonal"
    />

    <!-- Main Content -->
    <HabilitationList />
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import HabilitationList from '@/components/competences/HabilitationList.vue'
  import { useWebSocket } from '@/composables/useWebSocket'
  import { useHabilitationStore } from '@/stores/habilitationStore'

  const habilitationStore = useHabilitationStore()
  const { connected: wsConnected } = useWebSocket()

  const breadcrumbs = [
    { title: 'Accueil', to: '/' },
    { title: 'Compétences', disabled: true },
    { title: 'Habilitations', disabled: true },
  ]

  const stats = computed(() => {
    const habilitations = habilitationStore.habilitations
    return {
      total: habilitations.length,
      active: habilitations.filter(h => h.status === 'active').length,
      expiring: habilitationStore.expiring.length,
      expired: habilitationStore.expired.length,
    }
  })

  onMounted(() => {
    habilitationStore.fetchAll()
  })
</script>

<style scoped>
.habilitations-view {
  padding: 24px;
}
</style>
