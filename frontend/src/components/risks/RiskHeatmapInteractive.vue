<template>
  <div class="risk-heatmap-interactive bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Cartographie des Risques</h2>
        <p class="text-sm text-gray-600 mt-1">Matrice Probabilité × Gravité</p>
      </div>
      <div class="flex gap-2">
        <v-select
          v-model="selectedProcess"
          density="compact"
          :items="processFilters"
          label="Filtrer par processus"
          style="width: 200px"
          variant="outlined"
        />
        <v-btn
          color="primary"
          prepend-icon="mdi-filter"
          variant="outlined"
          @click="showFilters = !showFilters"
        >
          Filtres
        </v-btn>
      </div>
    </div>

    <!-- Filters Panel -->
    <v-expand-transition>
      <div v-if="showFilters" class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-4 gap-4">
          <v-checkbox
            v-model="filterCritical"
            color="red"
            density="compact"
            label="Risques Critiques"
          />
          <v-checkbox
            v-model="filterHigh"
            color="orange"
            density="compact"
            label="Risques Élevés"
          />
          <v-checkbox
            v-model="filterMedium"
            color="yellow"
            density="compact"
            label="Risques Moyens"
          />
          <v-checkbox
            v-model="filterLow"
            color="green"
            density="compact"
            label="Risques Faibles"
          />
        </div>
      </div>
    </v-expand-transition>

    <!-- Heatmap Matrix 5x5 -->
    <div class="heatmap-container">
      <!-- Y-axis Label (Gravité) -->
      <div class="y-axis-label">
        <div class="text-sm font-semibold text-gray-700 transform -rotate-90 whitespace-nowrap">
          GRAVITÉ
        </div>
      </div>

      <!-- Matrix Grid -->
      <div class="matrix-grid">
        <!-- Y-axis Values -->
        <div class="y-axis-values">
          <div v-for="g in [5, 4, 3, 2, 1]" :key="g" class="axis-value">
            {{ g }}
          </div>
        </div>

        <!-- Cells Grid -->
        <div class="cells-grid">
          <div
            v-for="row in 5"
            :key="row"
            class="row"
          >
            <div
              v-for="col in 5"
              :key="col"
              :class="['cell', getCellClass(6 - row, col)]"
              @click="openCellDetail(6 - row, col)"
            >
              <div class="risk-count">
                {{ getRiskCount(6 - row, col) }}
              </div>
              <div class="risk-badges">
                <v-chip
                  v-for="risk in getCellRisks(6 - row, col).slice(0, 3)"
                  :key="risk.id"
                  class="risk-chip"
                  size="x-small"
                  @click.stop="openRiskDetail(risk)"
                >
                  {{ risk.code }}
                </v-chip>
              </div>
            </div>
          </div>
        </div>

        <!-- X-axis Values -->
        <div class="x-axis-values">
          <div v-for="p in [1, 2, 3, 4, 5]" :key="p" class="axis-value">
            {{ p }}
          </div>
        </div>
      </div>

      <!-- X-axis Label (Probabilité) -->
      <div class="x-axis-label">
        <div class="text-sm font-semibold text-gray-700">PROBABILITÉ</div>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex justify-center gap-6 mt-8">
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded bg-green-200 border border-green-400" />
        <span class="text-sm">Faible (1-4)</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded bg-yellow-200 border border-yellow-400" />
        <span class="text-sm">Moyen (5-9)</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded bg-orange-300 border border-orange-500" />
        <span class="text-sm">Élevé (10-14)</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded bg-red-400 border border-red-600" />
        <span class="text-sm">Critique (15-25)</span>
      </div>
    </div>

    <!-- Risk Detail Dialog -->
    <v-dialog v-model="showRiskDialog" max-width="600">
      <v-card v-if="selectedRisk">
        <v-card-title class="bg-primary text-white">
          <div class="flex justify-between items-center">
            <span>{{ selectedRisk.code }} - {{ selectedRisk.name }}</span>
            <v-btn
              icon="mdi-close"
              variant="text"
              @click="showRiskDialog = false"
            />
          </div>
        </v-card-title>
        <v-card-text class="pt-4">
          <div class="space-y-4">
            <div>
              <h4 class="font-semibold text-gray-700 mb-1">Processus</h4>
              <p>{{ selectedRisk.process }}</p>
            </div>
            <div>
              <h4 class="font-semibold text-gray-700 mb-1">Description</h4>
              <p>{{ selectedRisk.description }}</p>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <h4 class="font-semibold text-gray-700 mb-1">Probabilité</h4>
                <v-chip color="primary">{{ selectedRisk.probability }}/5</v-chip>
              </div>
              <div>
                <h4 class="font-semibold text-gray-700 mb-1">Gravité</h4>
                <v-chip color="orange">{{ selectedRisk.gravity }}/5</v-chip>
              </div>
              <div>
                <h4 class="font-semibold text-gray-700 mb-1">Criticité</h4>
                <v-chip :color="getCriticalityColor(selectedRisk.criticality)">
                  {{ selectedRisk.criticality }}
                </v-chip>
              </div>
            </div>
            <div>
              <h4 class="font-semibold text-gray-700 mb-1">Actions de Maîtrise</h4>
              <ul class="list-disc list-inside space-y-1">
                <li v-for="(action, index) in selectedRisk.actions" :key="index">
                  {{ action }}
                </li>
              </ul>
            </div>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="primary" @click="editRisk(selectedRisk)">
            Modifier
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'

  interface Risk {
    id: number
    code: string
    name: string
    process: string
    description: string
    probability: number
    gravity: number
    criticality: number
    actions: string[]
  }

  const showFilters = ref(false)
  const selectedProcess = ref('Tous')
  const filterCritical = ref(true)
  const filterHigh = ref(true)
  const filterMedium = ref(true)
  const filterLow = ref(true)

  const showRiskDialog = ref(false)
  const selectedRisk = ref<Risk | null>(null)

  const processFilters = ['Tous', 'Production', 'Qualité', 'Achats', 'RH', 'Maintenance', 'Logistique']

  // Sample Risk Data
  const risks = ref<Risk[]>([
    {
      id: 1,
      code: 'R-PROD-001',
      name: 'Panne machine critique',
      process: 'Production',
      description: 'Arrêt de la ligne de production principale',
      probability: 3,
      gravity: 5,
      criticality: 15,
      actions: ['Maintenance préventive mensuelle', 'Stock pièces détachées', 'Formation équipe'],
    },
    {
      id: 2,
      code: 'R-QUAL-002',
      name: 'Non-détection défaut',
      process: 'Qualité',
      description: 'Défaut produit non détecté au contrôle',
      probability: 2,
      gravity: 4,
      criticality: 8,
      actions: ['Calibration instruments', 'Audit contrôle qualité'],
    },
    {
      id: 3,
      code: 'R-ACH-003',
      name: 'Retard livraison fournisseur',
      process: 'Achats',
      description: 'Retard matières premières critiques',
      probability: 4,
      gravity: 3,
      criticality: 12,
      actions: ['Diversification fournisseurs', 'Stock sécurité'],
    },
    {
      id: 4,
      code: 'R-RH-004',
      name: 'Turnover personnel qualifié',
      process: 'RH',
      description: 'Départ employés clés',
      probability: 3,
      gravity: 3,
      criticality: 9,
      actions: ['Plan de rétention', 'Formation croisée'],
    },
    {
      id: 5,
      code: 'R-PROD-005',
      name: 'Erreur paramétrage machine',
      process: 'Production',
      description: 'Mauvais paramétrage ligne production',
      probability: 2,
      gravity: 3,
      criticality: 6,
      actions: ['Procédure validée', 'Double vérification'],
    },
    {
      id: 6,
      code: 'R-MAIN-006',
      name: 'Absence technicien',
      process: 'Maintenance',
      description: 'Indisponibilité expert maintenance',
      probability: 3,
      gravity: 2,
      criticality: 6,
      actions: ['Polyvalence équipe', 'Prestataire externe'],
    },
    {
      id: 7,
      code: 'R-LOG-007',
      name: 'Erreur expédition',
      process: 'Logistique',
      description: 'Livraison mauvais produit client',
      probability: 2,
      gravity: 4,
      criticality: 8,
      actions: ['Scan code-barres', 'Contrôle double'],
    },
    {
      id: 8,
      code: 'R-QUAL-008',
      name: 'Contamination matières',
      process: 'Qualité',
      description: 'Contamination croisée produits',
      probability: 1,
      gravity: 5,
      criticality: 5,
      actions: ['Nettoyage strict', 'Zones séparées'],
    },
  ])

  const filteredRisks = computed(() => {
    let filtered = risks.value

    // Filter by process
    if (selectedProcess.value !== 'Tous') {
      filtered = filtered.filter(r => r.process === selectedProcess.value)
    }

    // Filter by criticality
    filtered = filtered.filter(r => {
      if (r.criticality >= 15 && filterCritical.value) return true
      if (r.criticality >= 10 && r.criticality < 15 && filterHigh.value) return true
      if (r.criticality >= 5 && r.criticality < 10 && filterMedium.value) return true
      if (r.criticality < 5 && filterLow.value) return true
      return false
    })

    return filtered
  })

  function getCellClass (gravity: number, probability: number): string {
    const criticality = gravity * probability
    if (criticality >= 15) return 'critical'
    if (criticality >= 10) return 'high'
    if (criticality >= 5) return 'medium'
    return 'low'
  }

  function getRiskCount (gravity: number, probability: number): number {
    return filteredRisks.value.filter(
      r => r.gravity === gravity && r.probability === probability,
    ).length
  }

  function getCellRisks (gravity: number, probability: number): Risk[] {
    return filteredRisks.value.filter(
      r => r.gravity === gravity && r.probability === probability,
    )
  }

  function openCellDetail (gravity: number, probability: number) {
    const cellRisks = getCellRisks(gravity, probability)
    if (cellRisks.length > 0) {
      selectedRisk.value = cellRisks[0] || null
      showRiskDialog.value = true
    }
  }

  function openRiskDetail (risk: Risk) {
    selectedRisk.value = risk
    showRiskDialog.value = true
  }

  function getCriticalityColor (criticality: number): string {
    if (criticality >= 15) return 'red'
    if (criticality >= 10) return 'orange'
    if (criticality >= 5) return 'yellow'
    return 'green'
  }

  function editRisk (risk: Risk) {
    console.log('Edit risk:', risk)
  // TODO: Implement risk editing
  }
</script>

<style scoped>
.heatmap-container {
  display: grid;
  grid-template-columns: 30px 1fr;
  grid-template-rows: 1fr 30px;
  gap: 10px;
  max-width: 800px;
  margin: 0 auto;
}

.y-axis-label {
  grid-column: 1;
  grid-row: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.matrix-grid {
  grid-column: 2;
  grid-row: 1;
  display: grid;
  grid-template-columns: 40px 1fr;
  grid-template-rows: 1fr auto;
  gap: 8px;
}

.y-axis-values {
  grid-column: 1;
  grid-row: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  align-items: center;
}

.cells-grid {
  grid-column: 2;
  grid-row: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
  flex: 1;
}

.cell {
  aspect-ratio: 1;
  border: 2px solid;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  padding: 4px;
  position: relative;
}

.cell:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.cell.low {
  background-color: rgb(187, 247, 208);
  border-color: rgb(74, 222, 128);
}

.cell.medium {
  background-color: rgb(254, 240, 138);
  border-color: rgb(250, 204, 21);
}

.cell.high {
  background-color: rgb(253, 186, 116);
  border-color: rgb(249, 115, 22);
}

.cell.critical {
  background-color: rgb(252, 165, 165);
  border-color: rgb(239, 68, 68);
}

.risk-count {
  font-size: 24px;
  font-weight: bold;
  color: rgba(0, 0, 0, 0.8);
}

.risk-badges {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 4px;
}

.risk-chip {
  font-size: 8px !important;
  height: 16px !important;
}

.x-axis-values {
  grid-column: 2;
  grid-row: 2;
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.x-axis-label {
  grid-column: 2;
  grid-row: 2;
  display: flex;
  justify-content: center;
  align-items: flex-end;
  margin-top: 20px;
}

.axis-value {
  font-weight: 600;
  color: #374151;
}
</style>
