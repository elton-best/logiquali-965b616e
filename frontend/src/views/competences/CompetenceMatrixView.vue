<template>
  <div class="competence-matrix-view">
    <v-breadcrumbs :items="breadcrumbs" />

    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Matrice de Compétences</h1>
        <p class="text-body-1 text-medium-emphasis">Vue d'ensemble des compétences par employé et poste</p>
      </div>

      <div class="d-flex gap-2">
        <v-btn color="primary" variant="outlined" @click="exportMatrix">
          <v-icon>mdi-download</v-icon>
          Exporter
        </v-btn>
        <v-btn color="success" @click="showTrainingPlans = true">
          <v-icon>mdi-school</v-icon>
          Plans de formation
        </v-btn>
      </div>
    </div>

    <!-- Quick Stats -->
    <v-row class="mb-6">
      <v-col cols="12" md="4">
        <v-card variant="outlined">
          <v-card-text class="text-center">
            <div class="text-h5 text-success">{{ matrixStats.compliant }}%</div>
            <div class="text-caption">Conformité moyenne</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card variant="outlined">
          <v-card-text class="text-center">
            <div class="text-h5 text-warning">{{ matrixStats.gaps }}</div>
            <div class="text-caption">Écarts identifiés</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card variant="outlined">
          <v-card-text class="text-center">
            <div class="text-h5 text-info">{{ matrixStats.employees }}</div>
            <div class="text-caption">Employés évalués</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Matrix Component -->
    <CompetenceMatrix />

    <!-- Training Plans Dialog -->
    <v-dialog v-model="showTrainingPlans" max-width="1200px">
      <v-card>
        <v-card-title>Plans de Formation Générés</v-card-title>
        <v-card-text>
          <TrainingPlansList />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showTrainingPlans = false">Fermer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import CompetenceMatrix from '@/components/competences/CompetenceMatrix.vue'
  import TrainingPlansList from '@/components/competences/TrainingPlansList.vue'
  import { useCompetenceStore } from '@/stores/competenceStore'

  const competenceStore = useCompetenceStore()
  const showTrainingPlans = ref(false)

  const breadcrumbs = [
    { title: 'Accueil', to: '/' },
    { title: 'Compétences', disabled: true },
    { title: 'Matrice', disabled: true },
  ]

  const matrixStats = computed(() => {
    const matrix = competenceStore.matrixData
    if (!matrix?.users) return { compliant: 0, gaps: 0, employees: 0 }

    const users = matrix.users
    const totalCompletionRate = users.reduce((sum, user) => sum + (user.completion_rate || 0), 0)
    const avgCompliance = users.length > 0 ? Math.round(totalCompletionRate / users.length) : 0

    const totalGaps = users.reduce((sum, user) => {
      return sum + (user.competences?.filter(c => !c.meets_requirement).length || 0)
    }, 0)

    return {
      compliant: avgCompliance,
      gaps: totalGaps,
      employees: users.length,
    }
  })

  function exportMatrix () {
    // Implementation for matrix export
    console.log('Exporting matrix...')
  }

  onMounted(() => {
    competenceStore.fetchMatrix()
  })
</script>

<style scoped>
.competence-matrix-view {
  padding: 24px;
}
</style>
