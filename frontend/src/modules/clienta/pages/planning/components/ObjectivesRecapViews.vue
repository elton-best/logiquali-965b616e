<template>
  <div class="objectives-recap-container mb-6">
    <!-- (c) Récapitulatif du système -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 system-kpi-card" elevation="1" rounded="lg" variant="outlined">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-grey-darken-1 font-weight-medium">Taux global du système</div>
              <div
                class="text-h4 font-weight-bold mt-1"
                :style="{ color: getRateColor(summary.systemAverageRate) }"
              >
                {{ summary.systemAverageRate !== null ? `${summary.systemAverageRate}%` : '—' }}
              </div>
            </div>
            <v-avatar color="primary-lighten-4" rounded="lg" size="48">
              <v-icon color="primary" size="28">mdi-chart-bell-curve</v-icon>
            </v-avatar>
          </div>
          <v-progress-linear
            class="mt-3"
            :color="getRateColor(summary.systemAverageRate)"
            height="8"
            :model-value="summary.systemAverageRate || 0"
            rounded
          />
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4" elevation="1" rounded="lg" variant="outlined">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-grey-darken-1 font-weight-medium">Objectifs suivis</div>
              <div class="text-h4 font-weight-bold mt-1 text-primary">{{ summary.totalObjectives }}</div>
            </div>
            <v-avatar color="blue-lighten-4" rounded="lg" size="48">
              <v-icon color="blue" size="28">mdi-target</v-icon>
            </v-avatar>
          </div>
          <div class="text-caption text-grey mt-3">Calculés au mois en cours (M{{ currentMonthNumber }})</div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4" elevation="1" rounded="lg" variant="outlined">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-grey-darken-1 font-weight-medium">Objectifs conformes (≥80%)</div>
              <div class="text-h4 font-weight-bold mt-1 text-success">{{ summary.achievedCount }}</div>
            </div>
            <v-avatar color="green-lighten-4" rounded="lg" size="48">
              <v-icon color="success" size="28">mdi-check-decagram</v-icon>
            </v-avatar>
          </div>
          <div class="text-caption text-success font-weight-medium mt-3">
            {{ percentOf(summary.achievedCount, summary.totalObjectives) }}% du portefeuille
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4" elevation="1" rounded="lg" variant="outlined">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-grey-darken-1 font-weight-medium">En vigilance (&lt;50%)</div>
              <div class="text-h4 font-weight-bold mt-1 text-error">{{ summary.warningCount }}</div>
            </div>
            <v-avatar color="red-lighten-4" rounded="lg" size="48">
              <v-icon color="error" size="28">mdi-alert-octagon-outline</v-icon>
            </v-avatar>
          </div>
          <div class="text-caption text-error font-weight-medium mt-3">
            {{ percentOf(summary.warningCount, summary.totalObjectives) }}% requièrent des actions
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- (a) & (b) Récapitulatifs détaillés avec onglets -->
    <v-card elevation="1" rounded="lg" variant="outlined">
      <v-tabs v-model="recapTab" color="primary">
        <v-tab value="processes">
          <v-icon start>mdi-sitemap</v-icon>
          (a) Récapitulatif par processus
        </v-tab>
        <v-tab value="axes">
          <v-icon start>mdi-compass-outline</v-icon>
          (b) Récapitulatif par axe stratégique
        </v-tab>
        <v-tab value="norms">
          <v-icon start>mdi-shield-check-outline</v-icon>
          (b) Récapitulatif par norme
        </v-tab>
      </v-tabs>

      <v-divider />

      <v-window v-model="recapTab" class="pa-4">
        <!-- Onglet (a) Par Processus -->
        <v-window-item value="processes">
          <v-table density="comfortable">
            <thead>
              <tr>
                <th class="font-weight-bold">Processus</th>
                <th class="font-weight-bold text-center">Nombre d'objectifs</th>
                <th class="font-weight-bold">Taux d'atteinte moyen actuel (§8)</th>
                <th class="font-weight-bold text-center">Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in summary.byProcess" :key="p.processId">
                <td class="font-weight-medium">
                  <v-icon class="mr-2" color="primary" size="small">mdi-circle-medium</v-icon>
                  {{ p.processName }}
                </td>
                <td class="text-center font-weight-bold">{{ p.objectivesCount }}</td>
                <td style="min-width: 220px;">
                  <div class="d-flex align-center">
                    <v-progress-linear
                      class="mr-3"
                      :color="getRateColor(p.averageRate)"
                      height="8"
                      :model-value="p.averageRate || 0"
                      rounded
                    />
                    <span
                      class="font-weight-bold text-body-2"
                      :style="{ color: getRateColor(p.averageRate) }"
                    >
                      {{ p.averageRate !== null ? `${p.averageRate}%` : '—' }}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <v-chip
                    density="compact"
                    size="small"
                    :color="getRateColor(p.averageRate)"
                    variant="tonal"
                  >
                    {{ getRateLabel(p.averageRate) }}
                  </v-chip>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-window-item>

        <!-- Onglet (b.1) Par Axe Stratégique -->
        <v-window-item value="axes">
          <v-table density="comfortable">
            <thead>
              <tr>
                <th class="font-weight-bold">Axe stratégique (issu de la Politique)</th>
                <th class="font-weight-bold text-center">Nombre d'objectifs rattachés</th>
                <th class="font-weight-bold">Taux d'atteinte moyen de l'axe (§8)</th>
                <th class="font-weight-bold text-center">Niveau d'avancement</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in summary.byAxis" :key="a.axisName">
                <td class="font-weight-medium">
                  <v-icon class="mr-2" color="info" size="small">mdi-flag-triangle</v-icon>
                  {{ a.axisName }}
                </td>
                <td class="text-center font-weight-bold">{{ a.objectivesCount }}</td>
                <td style="min-width: 220px;">
                  <div class="d-flex align-center">
                    <v-progress-linear
                      class="mr-3"
                      :color="getRateColor(a.averageRate)"
                      height="8"
                      :model-value="a.averageRate || 0"
                      rounded
                    />
                    <span
                      class="font-weight-bold text-body-2"
                      :style="{ color: getRateColor(a.averageRate) }"
                    >
                      {{ a.averageRate !== null ? `${a.averageRate}%` : '—' }}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <v-chip
                    density="compact"
                    size="small"
                    :color="getRateColor(a.averageRate)"
                    variant="tonal"
                  >
                    {{ getRateLabel(a.averageRate) }}
                  </v-chip>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-window-item>

        <!-- Onglet (b.2) Par Norme -->
        <v-window-item value="norms">
          <v-table density="comfortable">
            <thead>
              <tr>
                <th class="font-weight-bold">Norme concernée</th>
                <th class="font-weight-bold text-center">Nombre d'objectifs rattachés</th>
                <th class="font-weight-bold">Taux d'atteinte moyen de la norme (§8)</th>
                <th class="font-weight-bold text-center">Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="n in summary.byNorm" :key="n.normName">
                <td class="font-weight-medium">
                  <v-chip class="mr-2" color="primary" density="compact" size="small" variant="flat">
                    {{ n.normName }}
                  </v-chip>
                </td>
                <td class="text-center font-weight-bold">{{ n.objectivesCount }}</td>
                <td style="min-width: 220px;">
                  <div class="d-flex align-center">
                    <v-progress-linear
                      class="mr-3"
                      :color="getRateColor(n.averageRate)"
                      height="8"
                      :model-value="n.averageRate || 0"
                      rounded
                    />
                    <span
                      class="font-weight-bold text-body-2"
                      :style="{ color: getRateColor(n.averageRate) }"
                    >
                      {{ n.averageRate !== null ? `${n.averageRate}%` : '—' }}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <v-chip
                    density="compact"
                    size="small"
                    :color="getRateColor(n.averageRate)"
                    variant="tonal"
                  >
                    {{ getRateLabel(n.averageRate) }}
                  </v-chip>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-window-item>
      </v-window>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { SystemRecapSummary } from '@/modules/clienta/utils/objectiveCalculations'

const props = defineProps<{
  summary: SystemRecapSummary
}>()

const recapTab = ref<'processes' | 'axes' | 'norms'>('processes')
const currentMonthNumber = computed(() => new Date().getMonth() + 1)

function percentOf(val: number, total: number): number {
  if (!total) return 0
  return Math.round((val / total) * 100)
}

function getRateColor(rate: number | null): string {
  if (rate === null) return '#9e9e9e'
  if (rate >= 80) return '#2e7d32' // Vert ISO
  if (rate >= 50) return '#f57c00' // Orange
  return '#d32f2f' // Rouge
}

function getRateLabel(rate: number | null): string {
  if (rate === null) return 'Non renseigné'
  if (rate >= 80) return 'Conforme'
  if (rate >= 50) return 'En progrès'
  return 'En retard'
}
</script>

<style scoped>
.system-kpi-card {
  background: linear-gradient(180deg, rgba(var(--v-theme-primary), 0.03) 0%, rgba(255, 255, 255, 1) 100%);
}
</style>

